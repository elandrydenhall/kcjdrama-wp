#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _ssh_pull_kcj_theme import (  # noqa: E402
    find_theme_dir,
    remote_php,
    run,
    ssh_connect,
    wp_root_from_theme,
)

PHP = r"""<?php
require '{wp}/wp-load.php';
$n = (int) (wp_count_posts('kcj_hero')->publish ?? 0);
$cur = (int) get_option('kcj_current_hero_id', 0);
$p = $cur ? get_post($cur) : null;
$spots = $cur ? get_post_meta($cur, '_kcj_hotspots', true) : [];
$sc = is_array($spots) ? count($spots) : 0;
$thumb = $cur ? get_the_post_thumbnail_url($cur, 'full') : '';
echo "heroes_publish=$n\n";
echo 'current_id=' . $cur . ' title=' . ($p ? $p->post_title : '') . " spots=$sc\n";
echo 'thumb=' . ($thumb ?: 'none') . "\n";
$sample = get_posts(['post_type'=>'kcj_hero','name'=>'romantic-sunset-balcony-embrace','posts_per_page'=>1]);
if ($sample) {
  $s = get_post_meta($sample[0]->ID, '_kcj_hotspots', true);
  echo 'sample_sunset_spots=' . wp_json_encode($s) . "\n";
}
$missing = 0;
foreach (get_posts(['post_type'=>'kcj_hero','numberposts'=>-1,'fields'=>'ids']) as $id) {
  if (!has_post_thumbnail($id)) { $missing++; }
}
echo "heroes_missing_thumb=$missing\n";
"""


def main() -> int:
    client = ssh_connect()
    theme = find_theme_dir(client, verbose=False)
    wp = wp_root_from_theme(theme)
    php = remote_php(client)
    remote = "/tmp/kcj-verify-heroes.php"
    body = PHP.replace("{wp}", wp)
    sftp = client.open_sftp()
    with sftp.file(remote, "w") as fh:
        fh.write(body)
    sftp.close()
    out, err, rc = run(client, f"{php} '{remote}'", timeout=120)
    print(out.strip())
    if err.strip():
        print("ERR", err.strip()[:500])
    run(client, f"rm -f '{remote}'")
    client.close()
    return rc


if __name__ == "__main__":
    raise SystemExit(main())
