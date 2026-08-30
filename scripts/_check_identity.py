#!/usr/bin/env python3
from __future__ import annotations
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _ssh_pull_kcj_theme import find_theme_dir, remote_php, run, ssh_connect, wp_root_from_theme

PHP = r"""<?php
require '{wp}/wp-load.php';
$keys = [
  'blogname','admin_email','timezone_string',
  'woocommerce_store_address','woocommerce_store_city',
  'woocommerce_default_country','woocommerce_store_postcode',
  'woocommerce_email_from_address','woocommerce_email_from_name',
  'woocommerce_stock_email_recipient',
];
foreach ($keys as $k) {
  echo $k . '=' . get_option($k) . "\n";
}
echo 'now=' . wp_date('Y-m-d H:i:s T') . "\n";
$u = get_users(['role'=>'administrator','number'=>5]);
foreach ($u as $user) {
  echo 'user:' . $user->user_login . '<' . $user->user_email . ">\n";
}
"""


def main() -> int:
    client = ssh_connect()
    wp = wp_root_from_theme(find_theme_dir(client, verbose=False))
    php = remote_php(client)
    remote = "/tmp/kcj-check-identity.php"
    sftp = client.open_sftp()
    with sftp.file(remote, "w") as fh:
        fh.write(PHP.replace("{wp}", wp))
    sftp.close()
    out, err, rc = run(client, f"{php} '{remote}'")
    print(out.strip())
    run(client, f"rm -f '{remote}'")
    client.close()
    return rc


if __name__ == "__main__":
    raise SystemExit(main())
