#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply identity pack on Hostinger (kcjdrama.com)."""
from __future__ import annotations

import sys
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _ssh_pull_kcj_theme import (  # noqa: E402
    find_theme_dir,
    remote_php,
    run,
    ssh_connect,
    wp_root_from_theme,
)

LOCAL = Path(__file__).resolve().parent / "apply-identity-pack.php"


def main() -> int:
    if not LOCAL.is_file():
        raise SystemExit(f"Missing {LOCAL}")
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    client = ssh_connect()
    theme = find_theme_dir(client, verbose=False)
    wp = wp_root_from_theme(theme)
    php = remote_php(client)
    remote = f"/tmp/kcj-identity-{stamp}.php"
    print("WP root:", wp)
    sftp = client.open_sftp()
    sftp.put(str(LOCAL), remote)
    sftp.close()
    out, err, rc = run(client, f"{php} '{remote}' '{wp}'", timeout=180)
    print(out.strip())
    if err.strip():
        print("ERR", err.strip()[:800])
    run(client, f"rm -f '{remote}'")
    client.close()
    return rc


if __name__ == "__main__":
    raise SystemExit(main())
