<?php
// views/components/textarea.php

/**
 * Textarea component — PW Design System.
 *
 * @var array $atts  Textarea attributes from ComponentRenderer::textarea().
 */

defined("ABSPATH") || exit();

$has_error = !empty($atts["error"]);
$textarea_id = sanitize_title($atts["name"]);
$classes = implode(
	" ",
	array_filter([
		"pw-bui-textarea",
		$has_error ? "pw-bui-textarea--error" : "",
		$atts["class"] ?? "",
	]),
);
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <?php if (!empty($atts["label"])): ?>
        <label
            for="<?php echo esc_attr($textarea_id); ?>"
            class="pw-bui-label<?php echo !empty($atts["required"])
            	? " pw-bui-label--required"
            	: ""; ?>"
        >
            <?php echo esc_html($atts["label"]); ?>
        </label>
    <?php endif; ?>

    <textarea
        id="<?php echo esc_attr($textarea_id); ?>"
        name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
        placeholder="<?php echo esc_attr($atts["placeholder"] ?? ""); ?>"
        rows="<?php echo esc_attr($atts["rows"] ?? 4); ?>"
        class="<?php echo esc_attr($classes); ?>"
        <?php echo !empty($atts["required"]) ? "required" : ""; ?>
        <?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
    ><?php echo esc_textarea($atts["value"] ?? ""); ?></textarea>

    <?php if ($has_error): ?>
        <p class="pw-bui-form-error"><?php echo esc_html($atts["error"]); ?></p>
    <?php elseif (!empty($atts["help"])): ?>
        <p class="pw-bui-form-help"><?php echo esc_html($atts["help"]); ?></p>
    <?php endif; ?>
</div>
