        # KCJ Drama theme

        Custom WordPress theme for **kcjdrama.com**.

        Local bind-mount: `~/wp-dev/themes/gsolo-kcjdrama` →
        `/var/www/html/wp-content/themes/gsolo-kcjdrama`

        Local URL: http://localhost:8086

        ## Hostinger deployment

        This repo is the deployable unit. WordPress core, `wp-config.php`,
        and the database stay on the server — only this theme folder is shipped.

        1. Create an empty GitHub/GitLab repo for this theme.
        2. Point the remote and push:

           ```bash
           git remote add origin git@github.com:YOU/kcjdrama-theme.git
           git push -u origin main
           ```

        3. On Hostinger (File Manager or SSH), place the theme at:

           `public_html/wp-content/themes/kcjdrama/`

           Either `git clone` there, or upload a zip of this directory
           (not the parent `themes/` folder).

        4. In WP Admin → Appearance → Themes, activate **KCJ Drama**.

        Do not commit credentials, `wp-config.php`, or uploads into this repo.

This is the live local theme (v1.0.9): dual-nature homepage,
percentage hotspots, Soft | Mirror worlds. Do not replace it
from an import drop-zone. Do not import a BeeLink dump over
the running `:8086` stack.

