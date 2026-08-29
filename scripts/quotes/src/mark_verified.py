#!/usr/bin/env python3
"""Mark quotes verified=1 by id list, country, or --all."""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import connect  # noqa: E402


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--all", action="store_true", help="Verify every quote")
    p.add_argument("--country", choices=["K", "C", "J"], help="Verify one country")
    p.add_argument("--ids", default="", help="Comma/range list e.g. 1,2,5-9")
    args = p.parse_args()

    ids: set[int] = set()
    for part in args.ids.replace(" ", "").split(","):
        if not part:
            continue
        if "-" in part:
            a, b = part.split("-", 1)
            ids.update(range(int(a), int(b) + 1))
        else:
            ids.add(int(part))

    conn = connect()
    if args.all:
        cur = conn.execute(
            "UPDATE quotes SET verified=1, updated_at=datetime('now') WHERE verified=0"
        )
        print(f"verified_all rows={cur.rowcount}")
    elif args.country:
        cur = conn.execute(
            """
            UPDATE quotes SET verified=1, updated_at=datetime('now')
            WHERE verified=0 AND work_id IN (
                SELECT id FROM works WHERE country=?
            )
            """,
            (args.country,),
        )
        print(f"verified_country={args.country} rows={cur.rowcount}")
    elif ids:
        qmarks = ",".join("?" * len(ids))
        cur = conn.execute(
            f"UPDATE quotes SET verified=1, updated_at=datetime('now') WHERE id IN ({qmarks})",
            tuple(sorted(ids)),
        )
        print(f"verified_ids rows={cur.rowcount}")
    else:
        print("pass --all, --country, or --ids")
        return 1

    n = conn.execute("SELECT COUNT(*) AS c FROM quotes WHERE verified=1").fetchone()["c"]
    conn.commit()
    conn.close()
    print(f"verified_total={n}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
