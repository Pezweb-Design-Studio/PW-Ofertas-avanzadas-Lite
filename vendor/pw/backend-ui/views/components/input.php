<?php
// views/components/input.php

/**
 * Text input component — PW Design System.
 * Supports type: text | email | password | url | number | date | search
 *
 * @var array $atts  Input attributes from ComponentRenderer::input().
 */

defined("ABSPATH") || exit();

$has_error = !empty($atts["error"]);
$input_id = sanitize_title($atts["name"]);
$classes = implode(
	" ",
	array_filter([
		"pw-bui-input",
		$has_error ? "pw-bui-input--error" : "",
		$atts["class"] ?? "",
	]),
);
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <?php if (!empty($atts["label"])): ?>
        <label
            for="<?php echo esc_attr($input_id); ?>"
            class="pw-bui-label<?php echo !empty($atts["required"])
            	? " pw-bui-label--required"
            	: ""; ?>"
        >
            <?php echo esc_html($atts["label"]); ?>
        </label>
    <?php endif; ?>

    <input
        type="<?php echo esc_attr($atts["type"] ?? "text"); ?>"
        id="<?php echo esc_attr($input_id); ?>"
        name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
        value="<?php echo esc_attr($atts["value"] ?? ""); ?>"
        placeholder="<?php echo esc_attr($atts["placeholder"] ?? ""); ?>"
        class="<?php echo esc_attr($classes); ?>"
        <?php echo !empty($atts["required"]) ? "required" : ""; ?>
        <?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
        <?php if (!empty($atts["min"])) {
        	echo 'min="' . esc_attr($atts["min"]) . '"';
        } ?>
        <?php if (!empty($atts["max"])) {
        	echo 'max="' . esc_attr($atts["max"]) . '"';
        } ?>
    />

    <?php if ($has_error): ?>
        <p class="pw-bui-form-error"><?php echo esc_html($atts["error"]); ?></p>
    <?php elseif (!empty($atts["help"])): ?>
        <p class="pw-bui-form-help"><?php echo esc_html($atts["help"]); ?></p>
    <?php endif; ?>
</div>
