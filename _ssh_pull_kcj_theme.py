#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""One-shot: SSH pull live kcjdrama theme from Hostinger → local WordPress."""

from __future__ import annotations

import shutil
import tarfile
from datetime import datetime
from pathlib import Path

import paramiko

# Prefer env (do not commit secrets):
#   set KCJ_SSH_HOST / KCJ_SSH_PORT / KCJ_SSH_USER / KCJ_SSH_PASSWORD
import os

HOST = os.environ.get("KCJ_SSH_HOST", "82.29.86.108")
PORT = int(os.environ.get("KCJ_SSH_PORT", "65002"))
USER = os.environ.get("KCJ_SSH_USER", "u628528567")
PASSWORD = os.environ.get("KCJ_SSH_PASSWORD", "")
if not PASSWORD:
    raise SystemExit("Set env KCJ_SSH_PASSWORD before running")

LOCAL_WP = Path(r"C:\Scripts\wordpress")
LOCAL_THEME = LOCAL_WP / "kcjdrama"
BACKUP_ROOT = LOCAL_WP / "_theme_backups"


def ssh_connect() -> paramiko.SSHClient:
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
        banner_timeout=45,
    )
    return client


def run(client: paramiko.SSHClient, cmd: str, timeout: int = 120) -> tuple[str, str, int]:
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    code = stdout.channel.recv_exit_status()
    return out, err, code


def find_theme_dir(client: paramiko.SSHClient) -> str:
    probes = [
        "ls -la ~",
        "ls -la ~/domains 2>/dev/null || true",
        "ls -la ~/public_html 2>/dev/null || true",
        "find $HOME -maxdepth 5 -type d -name public_html 2>/dev/null | head -20",
        "find $HOME -maxdepth 8 -type d -path '*/themes/kcjdrama' 2>/dev/null | head -20",
    ]
    for cmd in probes:
        out, err, code = run(client, cmd)
        print(f"==== {cmd} (exit {code}) ====")
        print(out[:3000] if out else "(empty)")
        if err.strip():
            print("ERR:", err[:800])

    out, _, _ = run(
        client,
        "find $HOME -maxdepth 8 -type d -path '*/themes/kcjdrama' 2>/dev/null | head -5",
    )
    paths = [ln.strip() for ln in out.splitlines() if ln.strip()]
    if not paths:
        # fallback common Hostinger layout
        candidates = [
            f"$HOME/domains/kcjdrama.com/public_html/wp-content/themes/kcjdrama",
            f"$HOME/public_html/wp-content/themes/kcjdrama",
        ]
        for c in candidates:
            out2, _, code2 = run(client, f"test -d {c} && echo {c}")
            if code2 == 0 and out2.strip():
                paths.append(out2.strip())
    if not paths:
        raise SystemExit("Could not find themes/kcjdrama on remote")
    # Prefer kcjdrama.com domain path
    for p in paths:
        if "kcjdrama.com" in p:
            return p
    return paths[0]


def main() -> int:
    print("Connecting…")
    client = ssh_connect()
    print("Connected.")
    theme_remote = find_theme_dir(client)
    print("Theme remote:", theme_remote)

    # Read version
    out, _, _ = run(client, f"head -20 {theme_remote}/style.css")
    print("==== remote style.css head ====")
    print(out)

    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    remote_tar = f"/tmp/kcjdrama-theme-{stamp}.tar.gz"
    parent = str(Path(theme_remote).as_posix().rsplit("/", 1)[0])
    # tar from themes/ parent so archive contains kcjdrama/
    out, err, code = run(
        client,
        f"tar -C '{parent}' -czf '{remote_tar}' kcjdrama && ls -la '{remote_tar}'",
        timeout=180,
    )
    print(out)
    if code != 0:
        print("TAR ERR", err)
        raise SystemExit(code)

    local_tar = LOCAL_WP / f"kcjdrama-live-{stamp}.tar.gz"
    print("Downloading", remote_tar, "→", local_tar)
    sftp = client.open_sftp()
    sftp.get(remote_tar, str(local_tar))
    sftp.close()
    run(client, f"rm -f '{remote_tar}'")
    client.close()
    print("Downloaded bytes:", local_tar.stat().st_size)

    # Backup local theme
    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    if LOCAL_THEME.is_dir():
        backup = BACKUP_ROOT / f"kcjdrama-1.0-hotspot-{stamp}"
        print("Backing up", LOCAL_THEME, "→", backup)
        if backup.exists():
            shutil.rmtree(backup)
        shutil.move(str(LOCAL_THEME), str(backup))

    # Extract
    print("Extracting into", LOCAL_WP)
    with tarfile.open(local_tar, "r:gz") as tar:
        tar.extractall(LOCAL_WP)

    style = LOCAL_THEME / "style.css"
    print("==== installed style.css head ====")
    print(style.read_text(encoding="utf-8", errors="replace")[:500])
    print("DONE. Soft refresh http://localhost:8080/")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
