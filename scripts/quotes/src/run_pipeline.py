#!/usr/bin/env python3
"""Reset DB → curated ingest → optional Wikiquote → enrich → validate → export."""
from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

SRC = Path(__file__).resolve().parent


def run(args: list[str]) -> None:
    print(">>", " ".join(args))
    subprocess.check_call([sys.executable, *args], cwd=str(SRC.parent))


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--with-wikiquote", action="store_true", help="Also run Wikiquote ingest (slow)")
    parser.add_argument("--check-urls", action="store_true")
    parser.add_argument("--wikiquote-limit", type=int, default=0)
    args = parser.parse_args()

    run([str(SRC / "init_db.py"), "--reset"])
    curated = [str(SRC / "ingest_curated.py")]
    if args.check_urls:
        curated.append("--check-urls")
    run(curated)
    if args.with_wikiquote:
        wq = [str(SRC / "ingest_wikiquote.py")]
        if args.wikiquote_limit:
            wq += ["--limit", str(args.wikiquote_limit)]
        run(wq)
    run([str(SRC / "enrich_tags.py")])
    run([str(SRC / "validate.py")])
    run([str(SRC / "export_csv.py")])
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
