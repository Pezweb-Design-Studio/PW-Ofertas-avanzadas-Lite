<?php
// views/components/paragraph.php

/**
 * Paragraph component — PW Design System.
 *
 * @var array $atts  Paragraph attributes from ComponentRenderer::paragraph().
 */

defined("ABSPATH") || exit();

$variant = $atts["variant"] ?? "default";
$classes = implode(
	" ",
	array_filter([
		"pw-bui-paragraph",
		$variant !== "default" ? "pw-bui-paragraph--" . $variant : "",
		$atts["class"] ?? "",
	]),
);
?>
<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<p class="<?php echo esc_attr($classes); ?>"><?php echo wp_kses_post(
	$atts["text"] ?? "",
); ?></p>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
