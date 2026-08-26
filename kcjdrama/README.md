# KCJ Drama theme

WordPress theme for kcjdrama.com (stylesheet slug `kcjdrama`).

| Desk | Path | Local shop |
|------|------|------------|
| BeeLink | `C:\Scripts\wp-dev\sites\kcjdrama\kcjdrama` (this folder) | http://127.0.0.1:8080/ |
| Beast | `~/wp-dev/themes/kcjdrama` → `/mnt/drive-a/wp-dev/kcjdrama-wp/kcjdrama` | http://127.0.0.1:8086/ |

The git remote for this whole site tree is `beast` (`kcjdrama-wp.git`). After a BeeLink commit: `git push beast` then `ssh 10.0.0.194 "git -C /mnt/drive-a/wp-dev/kcjdrama-wp pull"`.

Do not commit credentials, `wp-config.php`, or uploads. Do not import a dump over the running Beast `:8086` stack.

