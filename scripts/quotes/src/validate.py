#!/usr/bin/env python3
"""Validate quote corpus integrity."""
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import WORD_CAP, connect  # noqa: E402


def main() -> int:
    conn = connect()
    errors: list[str] = []

    n = conn.execute("SELECT COUNT(*) AS c FROM quotes").fetchone()["c"]
    if n == 0:
        errors.append("no quotes in database")

    bad_len = conn.execute(
        "SELECT id, word_count, substr(quote_text,1,80) AS t FROM quotes WHERE word_count > ? OR word_count < 3",
        (WORD_CAP,),
    ).fetchall()
    for r in bad_len:
        errors.append(f"length id={r['id']} wc={r['word_count']} :: {r['t']}")

    missing = conn.execute(
        """
        SELECT id FROM quotes
        WHERE source_url IS NULL OR trim(source_url)=''
           OR source_name IS NULL OR trim(source_name)=''
           OR license_note IS NULL OR trim(license_note)=''
        """
    ).fetchall()
    for r in missing:
        errors.append(f"missing provenance id={r['id']}")

    untagged = conn.execute(
        """
        SELECT q.id FROM quotes q
        LEFT JOIN quote_tags qt ON qt.quote_id = q.id
        WHERE qt.quote_id IS NULL
        """
    ).fetchall()
    for r in untagged:
        errors.append(f"untagged id={r['id']}")

    by_country = conn.execute(
        """
        SELECT w.country, COUNT(*) AS c
        FROM quotes q JOIN works w ON w.id=q.work_id
        GROUP BY w.country ORDER BY w.country
        """
    ).fetchall()

    conn.close()
    print("counts_by_country:")
    for r in by_country:
        print(f"  {r['country']}: {r['c']}")
    print(f"total_quotes: {n}")
    print(f"errors: {len(errors)}")
    for e in errors[:30]:
        print(f"  - {e}")
    if len(errors) > 30:
        print(f"  ... {len(errors) - 30} more")
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
