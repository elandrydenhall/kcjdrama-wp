#!/usr/bin/env python3
"""Place XAI_API_KEY on Hostinger outside the web root. Never prints the key."""
from __future__ import annotations

import os
import stat
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")
REMOTE_WP = "/home/u628528567/domains/kcjdrama.com/public_html"
REMOTE_ENV = "/home/u628528567/domains/kcjdrama.com/.env"


def load_dotenv(path: Path) -> dict[str, str]:
    out: dict[str, str] = {}
    if not path.is_file():
        return out
    for raw in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        out[key.strip()] = val.strip().strip('"').strip("'")
    return out


def main() -> int:
    local_env = load_dotenv(ROOT / ".env")
    for k, v in local_env.items():
        os.environ.setdefault(k, v)

    key = local_env.get("XAI_API_KEY") or os.environ.get("XAI_API_KEY", "")
    print("local_key_present", bool(key), "len", len(key))
    if not key:
        raise SystemExit("Missing XAI_API_KEY in local .env")

    host = os.environ["KCJ_SSH_HOST"]
    port = int(os.environ.get("KCJ_SSH_PORT", "65002"))
    user = os.environ["KCJ_SSH_USER"]
    password = os.environ["KCJ_SSH_PASSWORD"]

    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        host,
        port=port,
        username=user,
        password=password,
        timeout=45,
        allow_agent=False,
        look_for_keys=False,
    )

    sftp = client.open_sftp()
    existing = ""
    try:
        with sftp.open(REMOTE_ENV, "r") as fh:
            existing = fh.read().decode("utf-8", "replace")
    except OSError:
        existing = ""

    lines = []
    found = False
    for raw in existing.splitlines():
        if raw.strip().startswith("XAI_API_KEY="):
            lines.append(f"XAI_API_KEY={key}")
            found = True
        else:
            lines.append(raw)
    if not found:
        if lines and lines[-1].strip() != "":
            lines.append("")
        lines.append("# SpaceXAI / xAI — Soft desk story precheck (server-side only)")
        lines.append(f"XAI_API_KEY={key}")
        lines.append("")

    tmp = REMOTE_ENV + ".tmp"
    with sftp.open(tmp, "w") as fh:
        fh.write("\n".join(lines).rstrip() + "\n")
    sftp.chmod(tmp, stat.S_IRUSR | stat.S_IWUSR)  # 0600
    # atomic replace
    try:
        sftp.remove(REMOTE_ENV)
    except OSError:
        pass
    sftp.rename(tmp, REMOTE_ENV)
    sftp.chmod(REMOTE_ENV, stat.S_IRUSR | stat.S_IWUSR)
    sftp.close()
    print("wrote", REMOTE_ENV, "mode=0600")

    php = (
        "<?php\n"
        "define('WP_USE_THEMES', false);\n"
        f"require '{REMOTE_WP}/wp-load.php';\n"
        f"echo 'parent_env_readable=' . (is_readable('{REMOTE_ENV}') ? '1' : '0') . PHP_EOL;\n"
        "echo 'fn=' . (function_exists('kcj_xai_api_key') ? '1' : '0') . PHP_EOL;\n"
        "$k = function_exists('kcj_xai_api_key') ? kcj_xai_api_key() : '';\n"
        "echo 'key_len=' . strlen($k) . PHP_EOL;\n"
        "$r = function_exists('kcj_ai_review_story')\n"
        "  ? kcj_ai_review_story('Umbrella test', 'Heavy rain blurred the neon. A black umbrella opened over her. He did not explain.')\n"
        "  : ['ok'=>false,'pass'=>false,'error'=>'missing','reasons'=>['no fn']];\n"
        "echo 'review_ok=' . (!empty($r['ok']) ? '1' : '0') . PHP_EOL;\n"
        "echo 'review_pass=' . (!empty($r['pass']) ? '1' : '0') . PHP_EOL;\n"
        "echo 'review_error=' . (string)($r['error'] ?? '') . PHP_EOL;\n"
        "$reasons = $r['reasons'] ?? [];\n"
        "echo 'review_reasons=' . implode(' | ', array_map('strval', $reasons)) . PHP_EOL;\n"
    )

    remote_php = "/tmp/kcj-xai-smoke.php"
    sftp = client.open_sftp()
    with sftp.open(remote_php, "w") as fh:
        fh.write(php)
    sftp.close()
    _, stdout, stderr = client.exec_command(f"php '{remote_php}'; rm -f '{remote_php}'", timeout=120)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(out.strip())
    if err.strip():
        print("ERR", err[:800])
    client.close()

    if "key_len=0" in out or "review_error=missing_key" in out:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
