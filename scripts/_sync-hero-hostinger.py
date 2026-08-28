#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Sync local home-01.webp + Heroes hotspot meta to Hostinger, then purge caches.

Does not print secrets. Does not touch Woo products.
"""

from __future__ import annotations

import json
import os
import sys
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")
LOCAL_WEBP = ROOT / "wordpress" / "wp-content" / "uploads" / "2026" / "08" / "home-01.webp"
# Laragon may store uploads under wordpress/ or site root — probe both.
ALT_WEBP = Path(r"C:\Scripts\wp-dev\sites\kcjdrama") / ".."  # placeholder


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
    raise SystemExit("Set KCJ_SSH_PASSWORD in site .env")

# Local hotspot JSON from BeeLink Heroes CPT (kcj_hero Soft | Mirror 01)
HOTSPOTS = [
    {"id": "logo", "x": 40.62, "y": 0, "w": 20, "h": 7.5, "href": "/", "label": "kcjdrama", "role": "logo"},
    {"id": "soft", "x": 4.89, "y": 49.26, "w": 16.2, "h": 6.8, "href": "/soft/", "label": "Enter Soft", "role": "soft"},
    {
        "id": "mirror",
        "x": 61.26,
        "y": 84.2,
        "w": 21.6,
        "h": 7.6,
        "href": "/mirror/",
        "label": "Enter Mirror",
        "role": "mirror",
    },
]


def find_local_webp() -> Path:
    candidates = [
        ROOT / "wordpress" / "wp-content" / "uploads" / "2026" / "08" / "home-01.webp",
        Path(r"C:\laragon\www") / "kcjdrama" / "wp-content" / "uploads" / "2026" / "08" / "home-01.webp",
        ROOT / "uploads" / "2026" / "08" / "home-01.webp",
        Path(r"C:\scripts\_local-home-01.webp"),
    ]
    for p in candidates:
        if p.is_file() and p.stat().st_size > 1000:
            return p
    raise SystemExit("Local home-01.webp not found")


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


def main() -> int:
    local = find_local_webp()
    print(f"Local webp: {local} ({local.stat().st_size} bytes)")

    client = ssh_connect()
    out, _, _ = run(
        client,
        "find $HOME -maxdepth 8 -type f -path '*/uploads/2026/08/home-01.webp' 2>/dev/null | head -5",
    )
    paths = [ln.strip() for ln in out.splitlines() if ln.strip()]
    if not paths:
        # fallback
        cand = f"/home/{USER}/domains/kcjdrama.com/public_html/wp-content/uploads/2026/08/home-01.webp"
        out2, _, code2 = run(client, f"test -f '{cand}' && echo '{cand}'")
        if code2 == 0 and out2.strip():
            paths = [out2.strip()]
    if not paths:
        client.close()
        raise SystemExit("Remote home-01.webp not found")
    remote_webp = paths[0]
    for p in paths:
        if "kcjdrama.com" in p:
            remote_webp = p
            break
    print("Remote webp:", remote_webp)

    wp_root = remote_webp.split("/wp-content/")[0]
    print("WP root:", wp_root)

    # Backup remote image
    stamp_cmd = "date +%Y%m%d_%H%M%S"
    stamp_out, _, _ = run(client, stamp_cmd)
    stamp = stamp_out.strip() or "sync"
    bak = f"{remote_webp}.bak-{stamp}"
    out, err, code = run(client, f"cp -p '{remote_webp}' '{bak}' && ls -la '{bak}'")
    print(out.strip() or "(backup)")
    if code != 0:
        print("BACKUP ERR", err[:500])
        client.close()
        return code

    # Upload new image
    sftp = client.open_sftp()
    print(f"Uploading {local.stat().st_size} bytes …")
    sftp.put(str(local), remote_webp)
    sftp.close()
    out, _, _ = run(client, f"ls -la '{remote_webp}'")
    print("After upload:", out.strip())

    # Apply hotspot meta via remote PHP (no wp-cli required)
    spots_json = json.dumps(HOTSPOTS, separators=(",", ":"))
    php = f"""<?php
require '{wp_root}/wp-load.php';
$posts = get_posts(array('post_type'=>'kcj_hero','numberposts'=>20,'post_status'=>'any'));
if (!$posts) {{ echo "NO_HERO\\n"; exit(1); }}
$spots = json_decode('{spots_json}', true);
$n = 0;
foreach ($posts as $p) {{
  update_post_meta($p->ID, '_kcj_hotspots', $spots);
  clean_post_cache($p->ID);
  echo "updated hero ID={{$p->ID}} title={{$p->post_title}}\\n";
  $n++;
}}
echo "hotspots_ok n=$n\\n";
"""
    remote_php_file = f"/tmp/kcj-sync-hero-{stamp}.php"
    # write via sftp
    sftp = client.open_sftp()
    with sftp.file(remote_php_file, "w") as f:
        f.write(php)
    sftp.close()

    php_bin_out, _, _ = run(
        client,
        "command -v php || ls /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /usr/bin/php 2>/dev/null | head -1",
    )
    php_bin = (php_bin_out or "").strip().splitlines()[0] if php_bin_out.strip() else "php"
    out, err, code = run(client, f"{php_bin} '{remote_php_file}'", timeout=120)
    print(out.strip())
    if code != 0:
        print("HOTSPOT ERR", err[:800])
    run(client, f"rm -f '{remote_php_file}'")

    # Purge LiteSpeed / WP caches (same as ship helper)
    out, err, code = run(
        client,
        f"rm -rf '{wp_root}/wp-content/cache' '{wp_root}/wp-content/litespeed' 2>/dev/null || true; "
        f"find '{wp_root}/wp-content' -maxdepth 2 -type d -iname '*cache*' 2>/dev/null | head -20; "
        f"rm -f '{wp_root}/wp-content/uploads/litespeed/**' 2>/dev/null || true",
        timeout=60,
    )
    print("Cache dirs probe:", (out or "").strip()[:500] or "(cleared)")

    # Touch style to help some CDNs; optional LiteSpeed CLI if present
    run(client, f"command -v wp >/dev/null && wp --path='{wp_root}' litespeed-purge all 2>/dev/null || true")
    run(
        client,
        f"{php_bin} -r \"define('ABSPATH','{wp_root}/'); "
        f"if (file_exists('{wp_root}/wp-content/plugins/litespeed-cache/litespeed-cache.php')) {{ "
        f"echo 'lsc_present'; }} else {{ echo 'lsc_absent'; }}\"",
    )

    client.close()
    print("Synced image + hotspots. Hard-refresh https://kcjdrama.com/ (Ctrl+F5).")
    print("If still stale, purge LiteSpeed in hPanel once.")
    return 0 if code == 0 else code


if __name__ == "__main__":
    raise SystemExit(main())
