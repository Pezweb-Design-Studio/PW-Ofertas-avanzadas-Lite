<?php
// views/components/link.php

/**
 * Link component — PW Design System.
 *
 * @var array $atts  Link attributes from ComponentRenderer::link().
 */

defined("ABSPATH") || exit();

$variant = $atts["variant"] ?? "default";
$classes = implode(
	" ",
	array_filter([
		"pw-bui-link",
		$variant !== "default" ? "pw-bui-link--" . $variant : "",
		$atts["class"] ?? "",
	]),
);
?>
<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<a
    href="<?php echo esc_url($atts["href"] ?? "#"); ?>"
    target="<?php echo esc_attr($atts["target"] ?? "_self"); ?>"
    class="<?php echo esc_attr($classes); ?>"
    <?php echo ($atts["target"] ?? "") === "_blank"
    	? 'rel="noopener noreferrer"'
    	: ""; ?>
>
    <?php echo esc_html($atts["label"] ?? ""); ?>
</a>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
