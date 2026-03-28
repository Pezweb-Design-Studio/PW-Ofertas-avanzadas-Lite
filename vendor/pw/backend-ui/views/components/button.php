<?php
// views/components/button.php

/**
 * Button component — PW Design System.
 * Renders <a> when href is provided, <button> otherwise.
 *
 * @var array $atts  Button attributes from ComponentRenderer::button().
 */

defined("ABSPATH") || exit();

$classes = implode(
	" ",
	array_filter([
		"pw-bui-btn",
		"pw-bui-btn--" . ($atts["variant"] ?? "primary"),
		"pw-bui-btn--" . ($atts["size"] ?? "md"),
		$atts["class"] ?? "",
	]),
);

$extra_attrs = "";
foreach ($atts["attrs"] ?? [] as $key => $val) {
	$extra_attrs .= " " . esc_attr($key) . '="' . esc_attr($val) . '"';
}

if (!empty($atts["style"])) {
	$extra_attrs .= ' style="' . esc_attr($atts["style"]) . '"';
}
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<?php if (!empty($atts["href"])): ?>
<a
    href="<?php echo esc_url($atts["href"]); ?>"
    class="<?php echo esc_attr($classes); ?>"
    <?php echo !empty($atts["target"])
    	? 'target="' . esc_attr($atts["target"]) . '"'
    	: ""; ?>
    <?php echo $extra_attrs; ?>
>
    <?php if (!empty($atts["icon"])): ?>
        <span class="pw-bui-btn__icon" aria-hidden="true"><?php echo $atts[
        	"icon"
        ]; ?></span>
    <?php endif; ?>
    <?php echo esc_html($atts["label"] ?? ""); ?>
</a>
<?php else: ?>
<button
    type="<?php echo esc_attr($atts["type"] ?? "button"); ?>"
    class="<?php echo esc_attr($classes); ?>"
    <?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
    <?php echo $extra_attrs; ?>
>
    <?php if (!empty($atts["icon"])): ?>
        <span class="pw-bui-btn__icon" aria-hidden="true"><?php echo $atts[
        	"icon"
        ]; ?></span>
    <?php endif; ?>
    <?php echo esc_html($atts["label"] ?? "Button"); ?>
</button>
<?php endif; ?>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
