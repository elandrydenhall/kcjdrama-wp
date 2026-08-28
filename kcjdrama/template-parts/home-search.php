<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<form class="kcj-home-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" data-kcj-home-search>
    <label class="kcj-vh" for="kcj-home-q"><?php esc_html_e('Search kcjdrama', 'gsolo-kcjdrama'); ?></label>
    <div class="kcj-home-search-bar">
        <input
            id="kcj-home-q"
            class="kcj-home-search-input"
            type="search"
            name="s"
            placeholder="<?php esc_attr_e('Search tropes, merch, syndromes…', 'gsolo-kcjdrama'); ?>"
            value="<?php echo esc_attr(get_search_query()); ?>"
            autocomplete="off"
            autocorrect="off"
            spellcheck="false"
            aria-autocomplete="list"
            aria-controls="kcj-home-suggest"
            aria-expanded="false"
            aria-haspopup="listbox"
        >
        <button type="button" class="kcj-home-search-clear" hidden aria-label="<?php esc_attr_e('Clear search', 'gsolo-kcjdrama'); ?>">×</button>
    </div>
    <ul id="kcj-home-suggest" class="kcj-home-suggest" hidden role="listbox"></ul>
</form>
