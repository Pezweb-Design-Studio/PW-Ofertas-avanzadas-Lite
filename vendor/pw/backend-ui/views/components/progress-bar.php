<?php
// views/components/progress-bar.php

/**
 * ProgressBar component — PW Design System.
 *
 * @var array $atts  ProgressBar attributes from ComponentRenderer::progress_bar().
 */

defined("ABSPATH") || exit();

$percent = max(0, min(100, (int) ($atts["value"] ?? 0)));
$size = $atts["size"] ?? "sm";
$variant = $atts["variant"] ?? "default";

$bar_class = implode(
	" ",
	array_filter([
		"pw-bui-progress__bar",
		$variant !== "default" ? "pw-bui-progress__bar--" . $variant : "",
	]),
);

$wrap_class = implode(
	" ",
	array_filter([
		"pw-bui-progress",
		$size !== "sm" ? "pw-bui-progress--" . $size : "",
		$atts["class"] ?? "",
	]),
);
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <?php if (!empty($atts["label"])): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <span class="pw-bui-label" style="margin-bottom:0;"><?php echo esc_html(
            	$atts["label"],
            ); ?></span>
            <?php if (!empty($atts["show_value"])): ?>
                <span style="font-size:12px;color:var(--pw-color-fg-muted);"><?php echo esc_html(
                	$percent,
                ); ?>%</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div
        class="<?php echo esc_attr($wrap_class); ?>"
        role="progressbar"
        aria-valuenow="<?php echo esc_attr($percent); ?>"
        aria-valuemin="0"
        aria-valuemax="100"
        <?php if (!empty($atts["label"])) {
        	echo 'aria-label="' . esc_attr($atts["label"]) . '"';
        } ?>
    >
        <div class="<?php echo esc_attr(
        	$bar_class,
        ); ?>" style="width:<?php echo esc_attr($percent); ?>%;"></div>
    </div>

    <?php if (!empty($atts["help"])): ?>
        <p class="pw-bui-form-help"><?php echo esc_html($atts["help"]); ?></p>
    <?php endif; ?>
</div>
