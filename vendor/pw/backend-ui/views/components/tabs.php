<?php
// views/components/tabs.php
/**
 * Tabs navigation — Primer UnderlineNav style — PW Design System.
 * Works with tab-panel.php and backend-ui.js.
 *
 * @var array $atts  Tabs attributes from ComponentRenderer::tabs().
 *
 * Modes:
 *   - 'js'  (default) — <button> elements, JS toggles tab panels.
 *   - 'url'           — <a href> elements, active tab marked server-side via 'active' => true.
 */
defined("ABSPATH") || exit();

if (empty($atts["tabs"])) {
	return;
}

$mode = $atts["mode"] ?? "js";
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<nav
    class="<?php echo esc_attr($atts["class"] ?? ""); ?>"
    role="tablist"
    aria-label="Tabs"
    style="display:flex;gap:0;"
>
    <?php foreach ($atts["tabs"] as $tab):

    	$slug = $tab["slug"] ?? "";
    	$label = $tab["label"] ?? "";
    	$active = !empty($tab["active"]);
    	$count = $tab["count"] ?? null;

    	$base_class = "pw-bui-tab" . ($active ? " pw-bui-tab--active" : "");

    	ob_start();
    	?>
            <?php echo esc_html($label); ?>
            <?php if ($count !== null): ?>
                <span class="pw-bui-badge pw-bui-badge--default pw-bui-badge--sm" style="margin-left:4px;">
                    <?php echo esc_html($count); ?>
                </span>
            <?php endif; ?>
    <?php
    $inner = ob_get_clean();

    if ($mode === "url"): ?>
        <a
            href="<?php echo esc_url($tab["href"] ?? "#"); ?>"
            role="tab"
            class="<?php echo esc_attr($base_class); ?>"
            aria-selected="<?php echo $active ? "true" : "false"; ?>"
        ><?php echo $inner; ?></a>
    <?php else: ?>
        <button
            type="button"
            role="tab"
            class="<?php echo esc_attr($base_class); ?>"
            data-pw-tab="<?php echo esc_attr($slug); ?>"
            aria-selected="<?php echo $active ? "true" : "false"; ?>"
            aria-controls="pw-tab-panel-<?php echo esc_attr($slug); ?>"
        ><?php echo $inner; ?></button>
    <?php endif;
    ?>
    <?php
    endforeach; ?>
</nav>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
