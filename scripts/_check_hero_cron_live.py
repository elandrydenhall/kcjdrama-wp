#!/usr/bin/env python3
"""Check + catch up Hostinger hero rotation cron. No secrets printed."""
from __future__ import annotations

import os
import urllib.request
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")
WP = "/home/u628528567/domains/kcjdrama.com/public_html"


def load_dotenv(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        os.environ.setdefault(key.strip(), val.strip().strip('"').strip("'"))


PHP = r"""<?php
define('WP_USE_THEMES', false);
require '%WP%/wp-load.php';
$next = wp_next_scheduled('kcj_rotate_hero');
$event = function_exists('wp_get_scheduled_event') ? wp_get_scheduled_event('kcj_rotate_hero') : null;
$current = (int) get_option('kcj_current_hero_id', 0);
$force = (int) get_option('kcj_force_hero_id', 0);
$interval = function_exists('kcj_rotate_interval') ? kcj_rotate_interval() : (string) get_option('kcj_rotate_interval', '');
echo 'LIVE interval=' . $interval . PHP_EOL;
echo 'LIVE force=' . $force . ' current=' . $current . PHP_EOL;
if ($current) {
  $p = get_post($current);
  echo 'LIVE title=' . ($p ? $p->post_title : '?') . ' slug=' . ($p ? $p->post_name : '?') . PHP_EOL;
}
echo 'LIVE next=' . ($next ? gmdate('c', $next) : 'NONE') . PHP_EOL;
echo 'LIVE now=' . gmdate('c') . PHP_EOL;
echo 'LIVE overdue=' . ($next && $next < time() ? 'YES' : 'no') . PHP_EOL;
if (is_object($event)) {
  echo 'LIVE schedule=' . ($event->schedule ?? '?') . ' interval_sec=' . (int) ($event->interval ?? 0) . PHP_EOL;
} else {
  echo 'LIVE schedule=unknown' . PHP_EOL;
}
echo 'LIVE DISABLE_WP_CRON=' . ((defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? '1' : '0') . PHP_EOL;

$do_run = in_array('--run', $argv ?? [], true);
if ($do_run) {
  $before = $current;
  // Always advance once for catch-up when overdue or forced check.
  if (function_exists('kcj_advance_hero')) {
    kcj_advance_hero();
  }
  wp_clear_scheduled_hook('kcj_rotate_hero');
  wp_schedule_event(time() + HOUR_IN_SECONDS, $interval ?: 'hourly', 'kcj_rotate_hero');
  $after = (int) get_option('kcj_current_hero_id', 0);
  echo 'LIVE action=advance_and_reschedule before=' . $before . ' after=' . $after . PHP_EOL;
  if ($after) {
    $p = get_post($after);
    echo 'LIVE after_title=' . ($p ? $p->post_title : '?') . PHP_EOL;
  }
  $next2 = wp_next_scheduled('kcj_rotate_hero');
  echo 'LIVE next_after=' . ($next2 ? gmdate('c', $next2) : 'NONE') . PHP_EOL;
}
""".replace("%WP%", WP)


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
    remote = "/tmp/kcj-hero-cron.php"
    sftp = client.open_sftp()
    with sftp.open(remote, "w") as fh:
        fh.write("#!/usr/bin/env php\n" + PHP)
    sftp.close()

    print("==== STATUS ====")
    _, stdout, stderr = client.exec_command(f"php '{remote}'", timeout=90)
    print(stdout.read().decode("utf-8", "replace"))
    err = stderr.read().decode("utf-8", "replace")
    if err.strip():
        print("ERR", err[:500])

    print("==== HTTP wp-cron ====")
    try:
        url = "https://kcjdrama.com/wp-cron.php?doing_wp_cron=1"
        req = urllib.request.Request(url, headers={"User-Agent": "kcj-cron-check"})
        with urllib.request.urlopen(req, timeout=60) as r:
            print("http_status", r.status, "bytes", len(r.read()))
    except Exception as exc:
        print("http_cron_error", type(exc).__name__, exc)

    print("==== CATCH-UP RUN ====")
    _, stdout, stderr = client.exec_command(f"php '{remote}' --run; rm -f '{remote}'", timeout=90)
    print(stdout.read().decode("utf-8", "replace"))
    err = stderr.read().decode("utf-8", "replace")
    if err.strip():
        print("ERR", err[:500])

    _, stdout, stderr = client.exec_command(
        "crontab -l 2>/dev/null | head -40; echo '(end crontab)'",
        timeout=30,
    )
    print("==== CRONTAB ====")
    print(stdout.read().decode("utf-8", "replace") or "(empty)")
    client.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
