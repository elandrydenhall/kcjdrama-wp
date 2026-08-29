#!/usr/bin/env python3
"""
Ingest short quotes from Wikiquote for seeded K/C/J romance dramas.

Uses the MediaWiki API (parse + search). Saves raw JSON under raw/.
Does not invent quotes. Marks verified=0 for human review.
Strict title matching — never accept unrelated search hits.
"""
from __future__ import annotations

import argparse
import csv
import json
import re
import sys
import time
from datetime import datetime, timezone
from difflib import SequenceMatcher
from html import unescape
from pathlib import Path
from typing import Any
from urllib.parse import quote as urlquote

import requests
from bs4 import BeautifulSoup

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import (  # noqa: E402
    RAW_DIR,
    SEEDS_PATH,
    USER_AGENT,
    WORD_CAP,
    acceptable_length,
    connect,
    init_schema,
    normalize_quote,
    word_count,
)

API = "https://en.wikiquote.org/w/api.php"
SLEEP_S = 3.5


def session() -> requests.Session:
    s = requests.Session()
    s.headers.update({"User-Agent": USER_AGENT})
    return s


def api_get(s: requests.Session, params: dict[str, Any], tries: int = 6) -> dict[str, Any]:
    params = dict(params)
    params.setdefault("format", "json")
    last_exc: Exception | None = None
    for i in range(tries):
        try:
            r = s.get(API, params=params, timeout=45)
            if r.status_code == 429:
                wait = SLEEP_S * (i + 2)
                print(f"  429 backoff {wait:.1f}s")
                time.sleep(wait)
                continue
            r.raise_for_status()
            time.sleep(SLEEP_S)
            return r.json()
        except Exception as exc:  # noqa: BLE001
            last_exc = exc
            time.sleep(SLEEP_S * (i + 1))
    raise RuntimeError(last_exc)


def title_similar(a: str, b: str) -> float:
    def norm(t: str) -> str:
        t = t.lower()
        t = re.sub(r"\([^)]*\)", " ", t)
        t = re.sub(r"[^a-z0-9]+", " ", t)
        return re.sub(r"\s+", " ", t).strip()

    na, nb = norm(a), norm(b)
    if not na or not nb:
        return 0.0
    if na in nb or nb in na:
        return 0.9
    return SequenceMatcher(None, na, nb).ratio()


def search_titles(s: requests.Session, query: str, limit: int = 8) -> list[str]:
    data = api_get(
        s,
        {
            "action": "query",
            "list": "search",
            "srsearch": query,
            "srlimit": limit,
            "srnamespace": 0,
        },
    )
    return [hit["title"] for hit in data.get("query", {}).get("search", [])]


def page_exists(s: requests.Session, title: str) -> bool:
    data = api_get(
        s,
        {"action": "query", "titles": title, "prop": "info"},
    )
    pages = data.get("query", {}).get("pages", {})
    for p in pages.values():
        if int(p.get("pageid", -1)) > 0 and "missing" not in p:
            return True
    return False


def fetch_parse_html(s: requests.Session, title: str) -> tuple[str, str]:
    data = api_get(
        s,
        {"action": "parse", "page": title, "prop": "text", "disableeditsection": 1},
    )
    html = data.get("parse", {}).get("text", {}).get("*", "")
    resolved = data.get("parse", {}).get("title", title)
    return resolved, html


def clean_line(text: str) -> str:
    text = unescape(text)
    text = re.sub(r"\[[^\]]*\]", "", text)
    text = re.sub(r"\s+", " ", text).strip()
    text = text.strip("\"'“”‘’")
    text = re.sub(r"\s*[—–-]\s*[A-Z].{0,40}$", "", text).strip()
    return text


def extract_quotes(html: str) -> list[dict[str, str]]:
    soup = BeautifulSoup(html, "lxml")
    for bad in soup.select("table, style, script, .mw-editsection, .reference"):
        bad.decompose()

    out: list[dict[str, str]] = []
    seen: set[str] = set()
    speaker = ""

    def add(text: str, section: str, who: str) -> None:
        text = clean_line(text)
        if not text or not acceptable_length(text):
            return
        if text.endswith(":") and word_count(text) <= 4:
            return
        norm = normalize_quote(text)
        if norm in seen:
            return
        seen.add(norm)
        out.append({"quote_text": text, "speaker": who or "", "section_hint": section})

    section = "main"
    for el in soup.select(".mw-parser-output > *"):
        name = el.name or ""
        if name in {"h2", "h3", "h4"}:
            section = el.get_text(" ", strip=True)
            section = re.sub(r"\[edit\]", "", section, flags=re.I).strip()
            speaker = ""
            continue
        if name == "dl":
            for dt in el.find_all("dt", recursive=False):
                speaker = clean_line(dt.get_text(" ", strip=True))
                dd = dt.find_next_sibling("dd")
                if dd:
                    add(dd.get_text(" ", strip=True), section, speaker)
            continue
        if name == "ul":
            for li in el.find_all("li", recursive=False):
                raw = li.get_text(" ", strip=True)
                who = speaker
                m = re.match(r"^([^:]{2,40}):\s+(.+)$", raw)
                if m and word_count(m.group(1)) <= 5:
                    who = clean_line(m.group(1))
                    raw = m.group(2)
                add(raw, section, who)
            continue
        if name == "p":
            raw = el.get_text(" ", strip=True)
            if raw.startswith('"') or raw.startswith("“"):
                add(raw, section, speaker)
    return out


def upsert_work(conn, row: dict[str, str]) -> int:
    conn.execute(
        """
        INSERT INTO works (title, country, original_title, year, aliases, medium)
        VALUES (?, ?, ?, ?, ?, 'tv_drama')
        ON CONFLICT(title, country) DO UPDATE SET
            original_title=excluded.original_title,
            year=excluded.year,
            aliases=excluded.aliases
        """,
        (
            row["title"].strip(),
            row["country"].strip().upper(),
            (row.get("original_title") or "").strip() or None,
            int(row["year"]) if (row.get("year") or "").strip().isdigit() else None,
            (row.get("aliases") or "").strip() or None,
        ),
    )
    cur = conn.execute(
        "SELECT id FROM works WHERE title=? AND country=?",
        (row["title"].strip(), row["country"].strip().upper()),
    )
    return int(cur.fetchone()[0])


def insert_quote(conn, work_id: int, q: dict[str, str], source_url: str, retrieved_at: str) -> bool:
    text = q["quote_text"]
    norm = normalize_quote(text)
    try:
        conn.execute(
            """
            INSERT INTO quotes (
                work_id, quote_text, quote_text_norm, speaker, word_count, lang,
                source_url, source_name, retrieved_at, license_note, verified,
                section_hint
            ) VALUES (?, ?, ?, ?, ?, 'en', ?, 'Wikiquote', ?, 'quoted_for_commentary_candidate', 0, ?)
            """,
            (
                work_id,
                text,
                norm,
                q.get("speaker") or None,
                word_count(text),
                source_url,
                retrieved_at,
                q.get("section_hint") or None,
            ),
        )
        return True
    except Exception:
        return False


def resolve_page(s: requests.Session, preferred: str, title: str, aliases: str) -> str | None:
    candidates: list[str] = []
    for c in [preferred, title, *[a.strip() for a in (aliases or "").split("|") if a.strip()]]:
        if c and c not in candidates:
            candidates.append(c)

    for c in candidates:
        if page_exists(s, c):
            return c

    best: tuple[float, str] | None = None
    for q in candidates[:3]:
        for h in search_titles(s, q):
            score = max(title_similar(h, x) for x in candidates)
            if score < 0.55:
                continue
            if best is None or score > best[0]:
                best = (score, h)
    return best[1] if best else None


def load_seeds(path: Path) -> list[dict[str, str]]:
    with path.open(encoding="utf-8", newline="") as f:
        rows = list(csv.DictReader(f))
    out = []
    for r in rows:
        if not (r.get("title") or "").strip() or not (r.get("country") or "").strip():
            continue
        if r["country"].strip().upper() not in {"K", "C", "J"}:
            continue
        out.append(r)
    return out


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--limit", type=int, default=0, help="Max works to process (0=all)")
    parser.add_argument("--country", choices=["K", "C", "J"], help="Only one country")
    args = parser.parse_args()

    RAW_DIR.mkdir(parents=True, exist_ok=True)
    conn = connect()
    init_schema(conn)
    seeds = load_seeds(SEEDS_PATH)
    if args.country:
        seeds = [r for r in seeds if r["country"].strip().upper() == args.country]
    if args.limit:
        seeds = seeds[: args.limit]

    s = session()
    retrieved_at = datetime.now(timezone.utc).isoformat()
    total_added = 0

    for row in seeds:
        title = row["title"].strip()
        country = row["country"].strip().upper()
        preferred = (row.get("wikiquote_title") or title).strip()
        print(f"== {country} | {title}")
        try:
            page = resolve_page(s, preferred, title, row.get("aliases") or "")
            if not page:
                conn.execute(
                    "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, 0, ?)",
                    ("", "miss", f"no wikiquote page for {title}"),
                )
                conn.commit()
                print("  miss: no page")
                continue
            resolved, html = fetch_parse_html(s, page)
            source_url = "https://en.wikiquote.org/wiki/" + urlquote(resolved.replace(" ", "_"))
            safe = re.sub(r"[^\w\-]+", "_", resolved)[:80]
            (RAW_DIR / f"{safe}.json").write_text(
                json.dumps({"title": resolved, "url": source_url, "html_len": len(html)}, indent=2),
                encoding="utf-8",
            )
            (RAW_DIR / f"{safe}.html").write_text(html, encoding="utf-8")

            quotes = extract_quotes(html)
            work_id = upsert_work(conn, row)
            added = 0
            for q in quotes:
                if insert_quote(conn, work_id, q, source_url, retrieved_at):
                    added += 1
            conn.execute(
                "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, ?, ?)",
                (source_url, "ok", added, f"page={resolved}; extracted={len(quotes)}; cap={WORD_CAP}"),
            )
            conn.commit()
            total_added += added
            print(f"  page={resolved} extracted={len(quotes)} added={added}")
        except Exception as exc:  # noqa: BLE001
            conn.execute(
                "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, 0, ?)",
                ("", "error", f"{title}: {exc}"),
            )
            conn.commit()
            print(f"  error: {exc}")

    conn.close()
    print(f"done total_added={total_added}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
