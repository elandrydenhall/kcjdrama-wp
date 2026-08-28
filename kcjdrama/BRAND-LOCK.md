# KCJDrama brand lock

This file is the freeze. Artists (human or agent) do not “improve” the look unless the operator names the exact surface and the exact change.

## Frozen

- **Hero plate:** `assets/seed/home-01.webp` (healed Soft|Mirror 1920×1080). CSS-crop stack on mobile. No canvas. No Imagine heal. No replacement collage.
- **House mark:** CSS text `kcjdrama` (Great Vibes) over the seam by default. Not `topbar_logo`. Not abstract PNGs. Per-hero float wordmarks from the ImageWork heal pipeline are allowed when the operator reviews them (`_hero-trove/HERO-EDIT-NOTES.md`, skill `kcj-hero-edit`).
- **Soft palette:** porcelain tokens in `front.css` `:root` (`--kcj-soft-*`).
- **Mirror palette:** violet-circuit tokens (`--kcj-mirror-*`).
- **Gold:** `--kcj-gold` is the shop/profit bridge only. Not a third world.
- **Catalog:** real Woo/Hostinger products. No invented merch.

## Allowed without a new ask (atmosphere only)

Paint, not furniture: grain, canvas tint, heading typefaces, gold on the Everything/shop-the-split *surfaces*. Do **not** change grids, hero HTML/CSS-crop, hotspot boxes, rail markup, or catalog cards’ structure.

## Two rooms, not a blend

Soft stays porcelain. Mirror stays violet roast. Do not hybridize, do not restyle both with one new font pairing unless asked. Do not apply generic “frontend-design” risks (new cream paper, acid-green dark SaaS, Inter, purple mesh, Awwwards WebGL) to this site.

## Revert

- **Before any design-skill / atmosphere work:** `_theme_backups\kcjdrama-NOW-pre-design-skills-20260826_194500`
- **Before audit implementation (1.4.7):** `_theme_backups\kcjdrama-1.4.7-pre-audit-impl`

Copy the folder back onto `kcjdrama\` (and restore `_php-router.php` from the NOW snapshot if you also need the old router).
