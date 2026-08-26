# Local WordPress

Docker Compose stack for local WordPress development: WordPress, MariaDB, phpMyAdmin, and WP-CLI.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) running

## Quick start

From this directory:

```powershell
.\scripts\setup.ps1
```

That starts the containers, waits until WordPress is reachable, and runs the installer if the site is not already installed.

Then open:

- Site: http://localhost:8080
- Admin: http://localhost:8080/wp-admin
- phpMyAdmin: http://localhost:8081

Default admin login (change these in `.env` before first setup if you want):

| Field | Value |
|---|---|
| User | `admin` |
| Password | `admin` |
| Email | `admin@example.com` |

Re-running `.\scripts\setup.ps1` is safe. It will not wipe an existing install.

## Everyday commands

```powershell
docker compose up -d          # start
docker compose down           # stop (keeps the database)
docker compose logs -f        # follow logs
.\scripts\wp.ps1 plugin list  # any WP-CLI command
```

Reset everything (deletes the database **and** the local `wordpress/` files):

```powershell
docker compose down -v
Remove-Item -Recurse -Force .\wordpress
.\scripts\setup.ps1
```

## Configuration

Copy `.env.example` to `.env` (setup does this if `.env` is missing) and edit:

- `HTTP_PORT` / `PHPMYADMIN_PORT` if 8080 or 8081 are already in use
- Database name, user, and passwords
- `WP_URL`, `WP_TITLE`, and admin credentials (used only on first install)

## Export to Beast (Linux Mint)

Pack theme + media + this Grok session onto the LAN share (not WordPress core):

```powershell
.\scripts\export-to-beast.ps1
```

Share leaf (uploads is the writable folder): `\\10.0.0.194\mnt\drive-a\wp-dev\kcjdrama\uploads\_export-leaf\`

Local copy: `C:\Scripts\wordpress\export-leaf\`

On Beast, read `HANDOFF.md`. Apply into the existing WordPress:

```bash
WP_ROOT=/path/to/wordpress LEAF=/mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf \
  bash /mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf/apply-on-beast.sh
```

To resume this Grok thread on Beast (native TUI, no Hermes), follow the `grok --resume` steps in `HANDOFF.md`.

## kcjdrama theme

The homepage theme lives at `./kcjdrama` and is bind-mounted into WordPress. It is a full-bleed Soft / Mirror hero with percentage hotspots and no footer.

After the stack is up:

```powershell
.\scripts\wp.ps1 theme activate kcjdrama
```

Then open http://localhost:8080. Add `?hotspots=1` to outline the clickable regions.

Manage stills and draw hotspots under **Heroes** in wp-admin. Rotation (hourly by default) is under **Heroes → Rotation**.

- Themes: `kcjdrama/` (this project) or `wordpress/wp-content/themes/`
- Plugins: `wordpress/wp-content/plugins/`

That folder is gitignored (it is generated on first start). To track a custom theme later, add an exception in `.gitignore`:

```
!wordpress/wp-content/themes/my-theme/
```

Debug logging is on. PHP notices and errors go to `wordpress/wp-content/debug.log` and are not printed on the front end.
