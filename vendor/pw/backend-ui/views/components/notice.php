<?php
// views/components/notice.php

/**
 * Notice / Banner component — PW Design System.
 *
 * @var array $atts  Notice attributes from ComponentRenderer::notice().
 */

defined("ABSPATH") || exit();

$type = $atts["type"] ?? "info";
$classes = implode(
	" ",
	array_filter([
		"pw-bui-notice",
		"pw-bui-notice--" . $type,
		$atts["class"] ?? "",
	]),
);
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<div
    class="<?php echo esc_attr($classes); ?>"
    role="alert"
    <?php echo !empty($atts["dismissible"]) ? "data-pw-dismissible" : ""; ?>
>
    <div class="pw-bui-notice__body">
        <?php if (!empty($atts["title"])): ?>
            <strong style="display:block;margin-bottom:2px;"><?php echo esc_html(
            	$atts["title"],
            ); ?></strong>
        <?php endif; ?>
        <?php echo wp_kses_post($atts["message"] ?? ""); ?>
    </div>

    <?php if (!empty($atts["dismissible"])): ?>
        <button type="button" class="pw-bui-notice__dismiss" aria-label="Cerrar">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
