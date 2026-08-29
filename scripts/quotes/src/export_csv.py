#!/usr/bin/env python3
"""Export quotes (+ tag join) to CSV for Excel review."""
from __future__ import annotations

import csv
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import EXPORT_DIR, connect  # noqa: E402


def main() -> int:
    EXPORT_DIR.mkdir(parents=True, exist_ok=True)
    conn = connect()

    quotes_path = EXPORT_DIR / "quotes.csv"
    tags_path = EXPORT_DIR / "quotes_by_tag.csv"

    rows = conn.execute(
        """
        SELECT
            q.id,
            w.title AS work_title,
            w.country,
            w.year,
            w.original_title,
            q.speaker,
            q.quote_text,
            q.word_count,
            q.lang,
            q.source_name,
            q.source_url,
            q.retrieved_at,
            q.license_note,
            q.verified,
            q.popularity_score,
            q.emotion_valence,
            q.emotion_arousal,
            q.emotion_labels,
            q.semantic_tokens,
            q.section_hint,
            GROUP_CONCAT(t.slug, '|') AS tags
        FROM quotes q
        JOIN works w ON w.id = q.work_id
        LEFT JOIN quote_tags qt ON qt.quote_id = q.id
        LEFT JOIN tags t ON t.id = qt.tag_id
        GROUP BY q.id
        ORDER BY w.country, q.popularity_score DESC, w.title, q.id
        """
    ).fetchall()

    fieldnames = list(rows[0].keys()) if rows else [
        "id", "work_title", "country", "quote_text", "source_url", "verified"
    ]
    with quotes_path.open("w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(f, fieldnames=fieldnames)
        w.writeheader()
        for r in rows:
            w.writerow(dict(r))

    tag_rows = conn.execute(
        """
        SELECT q.id AS quote_id, t.slug AS tag_slug, t.kind, t.label, w.title AS work_title, w.country
        FROM quote_tags qt
        JOIN quotes q ON q.id = qt.quote_id
        JOIN tags t ON t.id = qt.tag_id
        JOIN works w ON w.id = q.work_id
        ORDER BY t.kind, t.slug, q.id
        """
    ).fetchall()
    with tags_path.open("w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(
            f,
            fieldnames=["quote_id", "tag_slug", "kind", "label", "work_title", "country"],
        )
        w.writeheader()
        for r in tag_rows:
            w.writerow(dict(r))

    conn.close()
    print(f"wrote {quotes_path} ({len(rows)} rows)")
    print(f"wrote {tags_path} ({len(tag_rows)} rows)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
