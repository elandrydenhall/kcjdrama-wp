#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""One-shot: SSH pull live kcjdrama theme from Hostinger → local WordPress."""

from __future__ import annotations

import shutil
import tarfile
from datetime import datetime
from pathlib import Path
import subprocess

import paramiko

# Secrets from site .env (gitignored) or process env.
# Never commit or print KCJ_SSH_PASSWORD.
import os

LOCAL_WP = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")


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


load_dotenv(LOCAL_WP / ".env")

HOST = os.environ.get("KCJ_SSH_HOST", "82.29.86.108")
PORT = int(os.environ.get("KCJ_SSH_PORT", "65002"))
USER = os.environ.get("KCJ_SSH_USER", "u628528567")
PASSWORD = os.environ.get("KCJ_SSH_PASSWORD", "")
if not PASSWORD:
    raise SystemExit("Set KCJ_SSH_PASSWORD in C:\\Scripts\\wp-dev\\sites\\kcjdrama\\.env")
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


def find_theme_dir(client: paramiko.SSHClient, verbose: bool = True) -> str:
    probes = [
        "ls -la ~",
        "ls -la ~/domains 2>/dev/null || true",
        "ls -la ~/public_html 2>/dev/null || true",
        "find $HOME -maxdepth 5 -type d -name public_html 2>/dev/null | head -20",
        "find $HOME -maxdepth 8 -type d -path '*/themes/kcjdrama' 2>/dev/null | head -20",
    ]
    if verbose:
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


def _tar_filter(ti: tarfile.TarInfo) -> tarfile.TarInfo | None:
    name = ti.name.replace("\\", "/")
    parts = name.split("/")
    if ".git" in parts or name.endswith(".bak"):
        return None
    return ti


def backup_local(stamp: str) -> Path:
    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    dest = BACKUP_ROOT / f"kcjdrama-local-{stamp}"
    if dest.exists():
        shutil.rmtree(dest)
    shutil.copytree(
        LOCAL_THEME,
        dest,
        ignore=shutil.ignore_patterns(".git", "*.bak", "__pycache__"),
    )
    print("Local backup:", dest)
    return dest


def backup_remote(client: paramiko.SSHClient, theme_remote: str, stamp: str) -> Path:
    BACKUP_ROOT.mkdir(parents=True, exist_ok=True)
    remote_tar = f"/tmp/kcjdrama-live-{stamp}.tar.gz"
    parent = str(Path(theme_remote).as_posix().rsplit("/", 1)[0])
    out, err, code = run(
        client,
        f"tar -C '{parent}' -czf '{remote_tar}' kcjdrama && ls -la '{remote_tar}'",
        timeout=180,
    )
    print(out.strip())
    if code != 0:
        print("REMOTE TAR ERR", err[:800])
        raise SystemExit(code)
    local_tar = BACKUP_ROOT / f"kcjdrama-live-{stamp}.tar.gz"
    sftp = client.open_sftp()
    sftp.get(remote_tar, str(local_tar))
    sftp.close()
    run(client, f"rm -f '{remote_tar}'")
    print("Live backup:", local_tar, "bytes", local_tar.stat().st_size)
    return local_tar


def push_local(client: paramiko.SSHClient, theme_remote: str, stamp: str) -> None:
    pack = LOCAL_WP / f"_push-kcjdrama-{stamp}.tar.gz"
    with tarfile.open(pack, "w:gz") as tar:
        tar.add(LOCAL_THEME, arcname="kcjdrama", filter=_tar_filter)
    print("Push pack bytes:", pack.stat().st_size)
    remote_pack = f"/tmp/kcjdrama-push-{stamp}.tar.gz"
    sftp = client.open_sftp()
    sftp.put(str(pack), remote_pack)
    sftp.close()
    parent = str(Path(theme_remote).as_posix().rsplit("/", 1)[0])
    prev = f"{parent}/kcjdrama.prev-{stamp}"
    cmd = (
        f"set -e; "
        f"tar -tzf '{remote_pack}' | head -5; "
        f"test -f <(tar -xOf '{remote_pack}' kcjdrama/style.css) || tar -xOf '{remote_pack}' kcjdrama/style.css >/dev/null; "
        f"mv '{theme_remote}' '{prev}'; "
        f"tar -C '{parent}' -xzf '{remote_pack}'; "
        f"test -f '{theme_remote}/style.css'; "
        f"rm -rf '{prev}'; "
        f"rm -f '{remote_pack}'; "
        f"grep -m1 Version '{theme_remote}/style.css'"
    )
    # Hostinger bash may lack process substitution; keep it simple.
    cmd = (
        f"set -e; "
        f"mv '{theme_remote}' '{prev}'; "
        f"tar -C '{parent}' -xzf '{remote_pack}'; "
        f"test -f '{theme_remote}/style.css'; "
        f"rm -rf '{prev}'; "
        f"rm -f '{remote_pack}'; "
        f"grep -m1 Version '{theme_remote}/style.css'"
    )
    out, err, code = run(client, cmd, timeout=180)
    print(out.strip())
    if code != 0:
        print("PUSH ERR", err[:1200])
        # try restore
        run(client, f"test -d '{theme_remote}' || mv '{prev}' '{theme_remote}'")
        raise SystemExit(code)
    pack.unlink(missing_ok=True)


def push_main() -> int:
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_local(stamp)
    print(f"Connecting {USER}@{HOST}:{PORT} …")
    client = ssh_connect()
    theme_remote = find_theme_dir(client, verbose=False)
    print("Theme remote:", theme_remote)
    out, _, _ = run(client, f"grep -m1 Version '{theme_remote}/style.css' || true")
    print("Live version before:", out.strip())
    backup_remote(client, theme_remote, stamp)
    push_local(client, theme_remote, stamp)
    client.close()
    print("Pushed local theme. Check https://kcjdrama.com/ (purge LiteSpeed if stale).")
    return 0


def wp_root_from_theme(theme_remote: str) -> str:
    parts = theme_remote.replace("\\", "/").rstrip("/").split("/")
    # .../public_html/wp-content/themes/kcjdrama
    if len(parts) < 4:
        raise SystemExit(f"Unexpected theme path: {theme_remote}")
    return "/".join(parts[:-3])


def export_local_pack() -> Path:
    pack = LOCAL_WP / "scripts" / "_hostinger-pack.json"
    exporter = LOCAL_WP / "scripts" / "export-hostinger-pack.php"
    wp = LOCAL_WP / "scripts" / "wp.ps1"
    if not exporter.is_file() or not wp.is_file():
        raise SystemExit("Missing export-hostinger-pack.php or wp.ps1")
    cmd = [
        "powershell",
        "-NoProfile",
        "-File",
        str(wp),
        "eval-file",
        str(exporter),
    ]
    print("Exporting local pages/heroes/quotes/tags/cats (no products) …")
    proc = subprocess.run(cmd, cwd=str(LOCAL_WP), capture_output=True, text=True)
    if proc.stdout:
        print(proc.stdout.strip()[:1500])
    if proc.returncode != 0:
        print((proc.stderr or "")[:1500])
        raise SystemExit(f"export failed ({proc.returncode})")
    if not pack.is_file():
        raise SystemExit("Pack JSON was not written")
    print("Pack bytes:", pack.stat().st_size)
    return pack


def remote_php(client: paramiko.SSHClient) -> str:
    out, _, _ = run(
        client,
        "command -v php || ls /opt/alt/php83/usr/bin/php /opt/alt/php82/usr/bin/php /usr/bin/php 2>/dev/null | head -1",
    )
    php = (out or "").strip().splitlines()[0] if out.strip() else ""
    if not php:
        raise SystemExit("No php CLI on Hostinger")
    return php


def _sftp_mkdirs(sftp: paramiko.SFTPClient, remote_dir: str) -> None:
    parts = [p for p in remote_dir.replace("\\", "/").split("/") if p]
    cur = ""
    for part in parts:
        cur += "/" + part
        try:
            sftp.stat(cur)
        except OSError:
            try:
                sftp.mkdir(cur)
            except OSError:
                pass


def _resolve_local_media(rel: str) -> Path | None:
    """Find a readable local copy of an uploads-relative file.

    Some plates under wordpress/wp-content/uploads are locked by Apache on Windows.
    Prefer a staging tree, then direct path, then HTTP fetch from local WP.
    """
    import urllib.request

    rel = rel.replace("\\", "/").lstrip("/")
    staging = LOCAL_WP / "_ship_media_tmp"
    candidates = [
        staging.joinpath(*rel.split("/")),
        (LOCAL_WP / "wordpress" / "wp-content" / "uploads").joinpath(*rel.split("/")),
    ]
    for path in candidates:
        try:
            if path.is_file() and path.stat().st_size > 0:
                with path.open("rb") as fh:
                    fh.read(1)
                return path
        except OSError:
            continue

    # Last resort: pull via local Apache (can read files this process cannot).
    staged = staging.joinpath(*rel.split("/"))
    staged.parent.mkdir(parents=True, exist_ok=True)
    url = f"http://127.0.0.1:8080/wp-content/uploads/{rel}"
    try:
        urllib.request.urlretrieve(url, staged)
        if staged.is_file() and staged.stat().st_size > 0:
            return staged
    except OSError as exc:
        print(f"  HTTP fetch failed for {rel}: {exc}")
    return None


def upload_pack_media(client: paramiko.SSHClient, wp_root: str, pack: Path) -> int:
    """SFTP hero plate files listed in pack['media'] into remote uploads/. Returns count uploaded."""
    import json

    data = json.loads(pack.read_text(encoding="utf-8"))
    media = data.get("media") or []
    if not media:
        print("No pack media to upload.")
        return 0

    remote_uploads = f"{wp_root.rstrip('/')}/wp-content/uploads"
    sftp = client.open_sftp()
    uploaded = 0
    for item in media:
        rel = str((item or {}).get("rel") or "").replace("\\", "/").lstrip("/")
        if not rel or ".." in rel:
            continue
        local = _resolve_local_media(rel)
        if local is None:
            print(f"  skip missing/unreadable local media: {rel}")
            continue
        remote = f"{remote_uploads}/{rel}"
        _sftp_mkdirs(sftp, remote.rsplit("/", 1)[0])
        print(f"  upload {rel} ({local.stat().st_size} bytes)")
        sftp.put(str(local), remote)
        uploaded += 1
    sftp.close()
    print(f"Uploaded media files: {uploaded}")
    return uploaded


def apply_remote_content(
    client: paramiko.SSHClient,
    wp_root: str,
    stamp: str,
    pack: Path,
) -> None:
    upload_pack_media(client, wp_root, pack)
    apply_src = LOCAL_WP / "scripts" / "apply-hostinger-content.php"
    remote_dir = f"/tmp/kcj-ship-{stamp}"
    run(client, f"mkdir -p '{remote_dir}'")
    sftp = client.open_sftp()
    sftp.put(str(pack), f"{remote_dir}/pack.json")
    sftp.put(str(apply_src), f"{remote_dir}/apply.php")
    sftp.close()
    php = remote_php(client)
    print("Remote php:", php)
    cmd = f"{php} '{remote_dir}/apply.php' '{wp_root}' '{remote_dir}/pack.json'"
    out, err, code = run(client, cmd, timeout=600)
    # Print tail so heroes/quotes summary is visible.
    text = out.strip() if out.strip() else "(no apply stdout)"
    print(text[-4000:] if len(text) > 4000 else text)
    if code != 0:
        print("APPLY ERR", (err or "")[:1200])
        run(client, f"rm -rf '{remote_dir}'")
        raise SystemExit(code)
    run(client, f"rm -rf '{remote_dir}'")
    # LiteSpeed / generic caches — do not delete uploads; products untouched.
    run(
        client,
        f"rm -rf '{wp_root}/wp-content/cache' '{wp_root}/wp-content/litespeed' 2>/dev/null || true",
    )


def apply_only_main() -> int:
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    pack = export_local_pack()
    print(f"Connecting {USER}@{HOST}:{PORT} …")
    client = ssh_connect()
    theme_remote = find_theme_dir(client, verbose=False)
    wp_root = wp_root_from_theme(theme_remote)
    apply_remote_content(client, wp_root, stamp, pack)
    client.close()
    print("Re-applied pages/heroes/quotes/tags/cats. Products untouched.")
    return 0


def ship_main() -> int:
    """Theme + pages + heroes + quotes + merch tags/cats. Never ships products."""
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    pack = export_local_pack()
    backup_local(stamp)
    print(f"Connecting {USER}@{HOST}:{PORT} …")
    client = ssh_connect()
    theme_remote = find_theme_dir(client, verbose=False)
    wp_root = wp_root_from_theme(theme_remote)
    print("Theme remote:", theme_remote)
    print("WP root:", wp_root)
    out, _, _ = run(client, f"grep -m1 Version '{theme_remote}/style.css' || true")
    print("Live version before:", out.strip())
    backup_remote(client, theme_remote, stamp)
    push_local(client, theme_remote, stamp)
    apply_remote_content(client, wp_root, stamp, pack)
    out, _, _ = run(client, f"grep -m1 Version '{theme_remote}/style.css' || true")
    print("Live version after:", out.strip())
    client.close()
    print("Shipped theme + pages + heroes + quotes + tags + categories. Products left on Hostinger.")
    print("Check https://kcjdrama.com/ and purge LiteSpeed in hPanel if the home CSS looks old.")
    return 0


def ssh_probe() -> int:
    """Connect and run a harmless remote command. Does not print secrets."""
    print(f"Connecting {USER}@{HOST}:{PORT} …")
    client = ssh_connect()
    out, err, code = run(client, "pwd; uname -s")
    client.close()
    print("SSH ok" if code == 0 else "SSH command failed")
    if out.strip():
        print(out.strip())
    if code != 0 and err.strip():
        print(err.strip()[:400])
    return code


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
    import sys
    if "--probe" in sys.argv:
        raise SystemExit(ssh_probe())
    if "--push" in sys.argv:
        raise SystemExit(push_main())
    if "--ship" in sys.argv:
        raise SystemExit(ship_main())
    if "--apply-only" in sys.argv:
        raise SystemExit(apply_only_main())
    raise SystemExit(main())
