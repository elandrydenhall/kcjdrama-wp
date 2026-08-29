#!/usr/bin/env python3
"""
Load hand-curated short quotes from seeds/seed_quotes.csv.

Each row must include a real source_url (article that publishes the line).
Does not invent dialogue. verified=0 for review.
Optionally HEAD/GET-checks URLs (--check-urls).
"""
from __future__ import annotations

import argparse
import csv
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

import requests

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import (  # noqa: E402
    ROOT,
    USER_AGENT,
    acceptable_length,
    connect,
    init_schema,
    normalize_quote,
    word_count,
)

SEED = ROOT / "seeds" / "seed_quotes.csv"


def upsert_work(conn, title: str, country: str, year: str | None) -> int:
    conn.execute(
        """
        INSERT INTO works (title, country, year, medium)
        VALUES (?, ?, ?, 'tv_drama')
        ON CONFLICT(title, country) DO UPDATE SET year=COALESCE(excluded.year, works.year)
        """,
        (title, country, int(year) if (year or "").isdigit() else None),
    )
    row = conn.execute(
        "SELECT id FROM works WHERE title=? AND country=?",
        (title, country),
    ).fetchone()
    return int(row[0])


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check-urls", action="store_true", help="HTTP check source URLs")
    args = parser.parse_args()

    conn = connect()
    init_schema(conn)
    retrieved_at = datetime.now(timezone.utc).isoformat()
    s = requests.Session()
    s.headers.update({"User-Agent": USER_AGENT})

    with SEED.open(encoding="utf-8", newline="") as f:
        rows = list(csv.DictReader(f))

    added = 0
    skipped = 0
    for r in rows:
        title = (r.get("work_title") or "").strip()
        country = (r.get("country") or "").strip().upper()
        text = (r.get("quote_text") or "").strip().strip('"')
        url = (r.get("source_url") or "").strip()
        source_name = (r.get("source_name") or "curated").strip()
        speaker = (r.get("speaker") or "").strip() or None
        if not title or country not in {"K", "C", "J"} or not text or not url:
            skipped += 1
            continue
        if not acceptable_length(text):
            print(f"skip length: {text[:60]}")
            skipped += 1
            continue
        if args.check_urls:
            try:
                resp = s.head(url, timeout=20, allow_redirects=True)
                if resp.status_code >= 400:
                    resp = s.get(url, timeout=25, allow_redirects=True)
                if resp.status_code >= 400:
                    print(f"skip bad url {resp.status_code}: {url}")
                    skipped += 1
                    continue
                time.sleep(0.4)
            except Exception as exc:  # noqa: BLE001
                print(f"skip url error {exc}: {url}")
                skipped += 1
                continue

        work_id = upsert_work(conn, title, country, r.get("year"))
        section = (r.get("episode_hint") or "").strip() or "curated"
        try:
            conn.execute(
                """
                INSERT INTO quotes (
                    work_id, quote_text, quote_text_norm, speaker, word_count, lang,
                    source_url, source_name, retrieved_at, license_note, verified, section_hint
                ) VALUES (?, ?, ?, ?, ?, 'en', ?, ?, ?, 'quoted_for_commentary_candidate', 0, ?)
                """,
                (
                    work_id,
                    text,
                    normalize_quote(text),
                    speaker,
                    word_count(text),
                    url,
                    source_name,
                    retrieved_at,
                    section,
                ),
            )
            added += 1
        except Exception:
            skipped += 1

    conn.execute(
        "INSERT INTO ingest_log (url, status, rows_added, notes) VALUES (?, ?, ?, ?)",
        (str(SEED), "curated", added, f"skipped={skipped}"),
    )
    conn.commit()
    conn.close()
    print(f"curated added={added} skipped={skipped}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
