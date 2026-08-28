#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from __future__ import annotations

import os
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")


def load_dotenv(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        key = key.strip()
        val = val.strip().strip('"').strip("'")
        if key and key not in os.environ:
            os.environ[key] = val


load_dotenv(ROOT / ".env")

HOST = os.environ.get("KCJ_SSH_HOST", "82.29.86.108")
PORT = int(os.environ.get("KCJ_SSH_PORT", "65002"))
USER = os.environ.get("KCJ_SSH_USER", "u628528567")
PASSWORD = os.environ.get("KCJ_SSH_PASSWORD", "")
if not PASSWORD:
    raise SystemExit("missing KCJ_SSH_PASSWORD")

WP = f"/home/{USER}/domains/kcjdrama.com/public_html"


def main() -> int:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        HOST,
        port=PORT,
        username=USER,
        password=PASSWORD,
        timeout=45,
        allow_agent=False,
        look_for_keys=False,
    )
    cmds = [
        f"rm -rf '{WP}/wp-content/litespeed' '{WP}/wp-content/cache' && echo removed_cache_dirs",
        f"wp --path='{WP}' litespeed-purge all",
        f"wp --path='{WP}' cache flush",
        f"ls -la '{WP}/wp-content/uploads/2026/08/home-01.webp'",
        f"wp --path='{WP}' post meta get 692 _kcj_hotspots",
    ]
    for cmd in cmds:
        print("====", cmd)
        stdin, stdout, stderr = client.exec_command(cmd, timeout=120)
        out = stdout.read().decode("utf-8", "replace")
        err = stderr.read().decode("utf-8", "replace")
        print(out[:1200] if out else "(empty)")
        if err.strip():
            print("ERR:", err[:500])
    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
