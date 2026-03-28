<?php
// views/components/spinner.php

/**
 * Spinner component — PW Design System.
 *
 * @var array $atts  Spinner attributes from ComponentRenderer::spinner().
 */

defined("ABSPATH") || exit();

$classes = implode(
	" ",
	array_filter([
		"pw-bui-spinner",
		"pw-bui-spinner--" . ($atts["size"] ?? "md"),
		$atts["class"] ?? "",
	]),
);
?>
<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<span
    class="<?php echo esc_attr($classes); ?>"
    role="status"
    aria-label="<?php echo esc_attr($atts["label"] ?? "Cargando..."); ?>"
></span>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
