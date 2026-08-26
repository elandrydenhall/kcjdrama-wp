#!/usr/bin/env bash
# Run ON Beast. Installs the kcjdrama theme + media into an existing WordPress.
# Does not replace WordPress core. Does not overwrite the whole database.
set -euo pipefail

if [[ -z "${LEAF:-}" ]]; then
  if [[ -d /mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf ]]; then
    LEAF=/mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf
  else
    LEAF=/mnt/drive-a/wp-dev/kcjdrama/export-leaf
  fi
fi
SHARE="${SHARE:-/mnt/drive-a/wp-dev/kcjdrama}"
WP_ROOT="${WP_ROOT:-}"

if [[ -z "$WP_ROOT" ]]; then
  echo "Set WP_ROOT to the WordPress root (directory that contains wp-config.php)."
  echo "Example: WP_ROOT=/var/www/html bash $0"
  exit 1
fi

if [[ ! -f "$WP_ROOT/wp-config.php" ]]; then
  echo "No wp-config.php in WP_ROOT=$WP_ROOT"
  exit 1
fi

if ! command -v wp >/dev/null 2>&1; then
  echo "wp-cli (wp) is required on PATH."
  exit 1
fi

THEME_SRC="$LEAF/theme"
THEME_DST="$WP_ROOT/wp-content/themes/kcjdrama"
UPLOADS_SRC="$SHARE/uploads"
UPLOADS_DST="$WP_ROOT/wp-content/uploads"

mkdir -p "$THEME_DST"
echo "Copying theme → $THEME_DST"
cp -a "$THEME_SRC"/. "$THEME_DST"/

mkdir -p "$UPLOADS_DST"
echo "Syncing uploads → $UPLOADS_DST"
cp -an "$UPLOADS_SRC"/. "$UPLOADS_DST"/

cd "$WP_ROOT"
wp theme activate kcjdrama

if [[ -f "$LEAF/export/pages-heroes.xml" ]]; then
  wp plugin install wordpress-importer --activate || true
  wp import "$LEAF/export/pages-heroes.xml" --authors=create || true
fi

if [[ -f "$LEAF/export/kcj-options.json" ]] && command -v python3 >/dev/null; then
  export LEAF
  python3 - <<'PY' || true
import json, subprocess, os
path = os.environ.get("LEAF", "/mnt/drive-a/wp-dev/kcjdrama/export-leaf") + "/export/kcj-options.json"
try:
    data = json.load(open(path, encoding="utf-8"))
except Exception as e:
    print("options skip:", e)
    raise SystemExit(0)
# wp option list --format=json is a list of {option_name, option_value}
rows = data if isinstance(data, list) else []
for row in rows:
    name = row.get("option_name") or row.get("name")
    value = row.get("option_value") if "option_value" in row else row.get("value")
    if not name or not str(name).startswith("kcj_"):
        continue
    subprocess.run(["wp", "option", "update", str(name), str(value)], check=False)
PY
fi

wp rewrite structure '/%postname%/' || true
wp rewrite flush || true

echo "Done. Open http://localhost:8080  (admin: existing Beast WP user)"
echo "Theme: kcjdrama"
