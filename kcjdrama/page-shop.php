<?php
/**
 * Template Name: Shop (Soft | Mirror catalog)
 * Satire-like /catalog/ chrome — Soft|Everything|Mirror as the top chip row.
 */
if (!defined('ABSPATH')) {
    exit;
}

$filters = kcj_catalog_filters();
$rail = $filters['rail'];
$slice = kcj_merch_slice($rail, 0, kcj_wall_chunk_size('shop'), $filters);

get_header();
?>
<main
    class="kcj-shop kcj-shop--catalog kcj-shop--<?php echo esc_attr($rail); ?>"
    id="kcj-shop-split"
    data-kcj-shop-root
    data-rail="<?php echo esc_attr($rail); ?>"
>
    <div class="kcj-shop-inner kcj-page-shell">
        <header class="kcj-catalog-intro">
            <p class="header-shop-line">
                <span class="shop-intro-title"><?php esc_html_e('The KCJ Catalog', 'kcjdrama'); ?></span>
                <span class="shop-intro-cotton"><?php esc_html_e('Soft porcelain.', 'kcjdrama'); ?></span>
                <em class="shop-intro-razor"><?php esc_html_e('Mirror chaos.', 'kcjdrama'); ?></em>
            </p>
            <h1 class="page-title screen-reader-text"><?php echo esc_html(get_the_title()); ?></h1>
        </header>

        <?php kcj_render_catalog_toolbar((int) $slice['total']); ?>

        <?php kcj_render_merch_wall('shop'); ?>
    </div>
</main>
<script>
(function () {
  var details = document.querySelector("[data-shop-filters]");
  if (!details) return;
  function sync() {
    if (window.matchMedia("(min-width: 701px)").matches) {
      details.setAttribute("open", "");
    }
  }
  sync();
  window.addEventListener("resize", sync);
})();
</script>
<?php
get_footer();
