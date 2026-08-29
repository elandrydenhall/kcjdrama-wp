#!/usr/bin/env python3
"""
Scrape short quoted lines from a curated list of reputable article URLs.

Strict extraction: blockquotes and clearly quoted strings only.
"""
from __future__ import annotations

import csv
import re
import sys
import time
from datetime import datetime, timezone
from html import unescape
from pathlib import Path
from urllib.parse import urlparse

import requests
from bs4 import BeautifulSoup

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import (  # noqa: E402
    RAW_DIR,
    ROOT,
    USER_AGENT,
    WORD_CAP,
    acceptable_length,
    connect,
    init_schema,
    normalize_quote,
    word_count,
)

SOURCES = ROOT / "seeds" / "scrape_sources.csv"

NOISE = re.compile(
    r"(cookie|subscribe|newsletter|privacy|copyright|share this|related posts|"
    r"leave a reply|facebook|twitter|instagram|click here|read more|"
    r"all rights reserved|home\s*>|menu)",
    re.I,
)


def upsert_work(conn, title: str, country: str, year: str | None) -> int:
    conn.execute(
        """
        INSERT INTO works (title, country, year, medium)
        VALUES (?, ?, ?, 'tv_drama')
        ON CONFLICT(title, country) DO UPDATE SET year=COALESCE(excluded.year, works.year)
        """,
        (title, country, int(year) if (year or "").isdigit() else None),
    )
    return int(
        conn.execute(
            "SELECT id FROM works WHERE title=? AND country=?",
            (title, country),
        ).fetchone()[0]
    )


def clean(text: str) -> str:
    text = unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    text = text.strip("\"'“”‘’")
    text = re.sub(r"^[A-Za-z][A-Za-z0-9 .'-]{1,40}:\s+", "", text)
    text = re.sub(r"\s*[—–-]\s*[A-Z][A-Za-z .'-]{1,40}\s*$", "", text).strip()
    return text


def ok_line(text: str) -> bool:
    if not text or not acceptable_length(text):
        return False
    if NOISE.search(text):
        return False
    if text.lower().startswith("http"):
        return False
    # Avoid pure navigation crumbs
    if text.count(">") >= 2:
        return False
    return True


def extract_candidate_lines(html: str) -> list[str]:
    soup = BeautifulSoup(html, "lxml")
    for bad in soup.select("script, style, nav, footer, header, noscript, form, aside"):
        bad.decompose()

    lines: list[str] = []
    seen: set[str] = set()

    def add(raw: str) -> None:
        t = clean(raw)
        if not ok_line(t):
            return
        norm = normalize_quote(t)
        if norm in seen:
            return
        seen.add(norm)
        lines.append(t)

    for el in soup.select("blockquote, q"):
        add(el.get_text(" ", strip=True))

    # Explicit quoted strings in page text / list items
    for el in soup.select("li, p, h2, h3, td"):
        raw = el.get_text(" ", strip=True)
        for m in re.finditer(r"[“\"]([^”\"\n]{8,160})[”\"]", raw):
            add(m.group(1))
        # Episode-style lines often italic/bold alone in short paragraphs
        if el.name == "li" and 3 <= word_count(raw) <= WORD_CAP and (
            raw.startswith(("\u201c", '"'))
            or "\u2014" in raw
            or " - " in raw
            or re.search(r"\u2014\s*[A-Z]", raw)
        ):
            add(raw)

    return lines


def main() -> int:
    RAW_DIR.mkdir(parents=True, exist_ok=True)
    conn = connect()
    init_schema(conn)
    s = requests.Session()
    s.headers.update({"User-Agent": USER_AGENT})
    retrieved_at = datetime.now(timezone.utc).isoformat()

    with SOURCES.open(encoding="utf-8", newline="") as f:
        rows = list(csv.DictReader(f))

    total = 0
    for r in rows:
        title = r["work_title"].strip()
        country = r["country"].strip().upper()
        url = r["source_url"].strip()
        source_name = (r.get("source_name") or urlparse(url).netloc).strip()
        print(f"== scrape {country} | {title} | {url}")
        try:
            resp = s.get(url, timeout=40)
            resp.raise_for_status()
            time.sleep(1.2)
        except Exception as exc:  # noqa: BLE001
            print(f"  fetch fail: {exc}")
            conn.execute(
                "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, 0, ?)",
                (url, "scrape_fail", str(exc)),
            )
            conn.commit()
            continue

        safe = re.sub(r"[^\w\-]+", "_", urlparse(url).path)[:80] or "page"
        (RAW_DIR / f"scrape_{safe}.html").write_text(resp.text, encoding="utf-8", errors="ignore")
        cands = extract_candidate_lines(resp.text)
        work_id = upsert_work(conn, title, country, r.get("year"))
        added = 0
        for text in cands:
            try:
                conn.execute(
                    """
                    INSERT INTO quotes (
                        work_id, quote_text, quote_text_norm, speaker, word_count, lang,
                        source_url, source_name, retrieved_at, license_note, verified, section_hint
                    ) VALUES (?, ?, ?, NULL, ?, 'en', ?, ?, ?, 'quoted_for_commentary_candidate', 0, 'html_scrape')
                    """,
                    (
                        work_id,
                        text,
                        normalize_quote(text),
                        word_count(text),
                        url,
                        source_name,
                        retrieved_at,
                    ),
                )
                added += 1
            except Exception:
                pass
        conn.execute(
            "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, ?, ?)",
            (url, "scrape_ok", added, f"candidates={len(cands)}; cap={WORD_CAP}"),
        )
        conn.commit()
        total += added
        print(f"  candidates={len(cands)} added={added}")

    conn.close()
    print(f"done scrape_added={total}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
