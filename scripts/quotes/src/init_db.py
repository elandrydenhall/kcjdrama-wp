#!/usr/bin/env python3
"""Create/reset the KCJ quotes SQLite database from schema.sql."""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import DB_PATH, EXPORT_DIR, RAW_DIR, connect, init_schema  # noqa: E402


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--reset", action="store_true", help="Delete existing DB first")
    args = parser.parse_args()

    EXPORT_DIR.mkdir(parents=True, exist_ok=True)
    RAW_DIR.mkdir(parents=True, exist_ok=True)

    if args.reset and DB_PATH.exists():
        DB_PATH.unlink()
        print(f"removed {DB_PATH}")

    conn = connect()
    init_schema(conn)
    conn.close()
    print(f"ready {DB_PATH}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
