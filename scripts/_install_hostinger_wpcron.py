#!/usr/bin/env python3
"""Install a Hostinger user crontab line that pings WP-Cron every 15 minutes."""
from __future__ import annotations

import os
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")
CRON_LINE = (
    "*/15 * * * * curl -fsS -o /dev/null "
    "'https://kcjdrama.com/wp-cron.php?doing_wp_cron' "
    ">/dev/null 2>&1"
)
MARKER = "kcjdrama.com/wp-cron.php?doing_wp_cron"


def load_dotenv(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        os.environ.setdefault(key.strip(), val.strip().strip('"').strip("'"))


def main() -> int:
    load_dotenv(ROOT / ".env")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        os.environ["KCJ_SSH_HOST"],
        port=int(os.environ.get("KCJ_SSH_PORT", "65002")),
        username=os.environ["KCJ_SSH_USER"],
        password=os.environ["KCJ_SSH_PASSWORD"],
        timeout=45,
        allow_agent=False,
        look_for_keys=False,
    )

    # Read existing crontab (ok if empty / missing).
    _, stdout, stderr = client.exec_command("crontab -l 2>/dev/null || true", timeout=30)
    existing = stdout.read().decode("utf-8", "replace")
    _ = stderr.read()

    lines = [ln.rstrip("\n") for ln in existing.splitlines()]
    # Drop prior kcjdrama wp-cron lines, keep everything else.
    kept = [ln for ln in lines if MARKER not in ln]
    # Preserve trailing blank style lightly.
    while kept and kept[-1].strip() == "":
        kept.pop()
    kept.append(CRON_LINE)
    kept.append("")
    new_cron = "\n".join(kept)

    remote = "/tmp/kcj-crontab.txt"
    sftp = client.open_sftp()
    with sftp.open(remote, "w") as fh:
        fh.write(new_cron)
    sftp.close()

    _, stdout, stderr = client.exec_command(f"crontab '{remote}' && rm -f '{remote}' && crontab -l", timeout=30)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(out)
    if err.strip():
        print("ERR", err[:800])
        client.close()
        return 1

    if MARKER not in out:
        print("FAILED: marker missing after install")
        client.close()
        return 1

    print("Installed Hostinger crontab for WP-Cron every 15 minutes.")
    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
