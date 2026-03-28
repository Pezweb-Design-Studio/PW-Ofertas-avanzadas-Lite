<?php
// views/components/toggle.php

/**
 * Toggle switch component — PW Design System.
 *
 * @var array $atts  Toggle attributes from ComponentRenderer::toggle().
 */

defined("ABSPATH") || exit();

$toggle_id = "pw-toggle-" . sanitize_title($atts["name"]);
$checked = !empty($atts["checked"]);
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <div class="pw-bui-toggle-wrap <?php echo esc_attr(
    	$atts["class"] ?? "",
    ); ?>">
        <div>
            <?php if (!empty($atts["label"])): ?>
                <label for="<?php echo esc_attr(
                	$toggle_id,
                ); ?>" class="pw-bui-label" style="margin-bottom:0;cursor:pointer;">
                    <?php echo esc_html($atts["label"]); ?>
                </label>
            <?php endif; ?>
            <?php if (!empty($atts["help"])): ?>
                <p class="pw-bui-form-help" style="margin-top:2px;"><?php echo esc_html(
                	$atts["help"],
                ); ?></p>
            <?php endif; ?>
        </div>

        <button
            type="button"
            role="switch"
            id="<?php echo esc_attr($toggle_id); ?>"
            class="pw-bui-toggle"
            aria-checked="<?php echo $checked ? "true" : "false"; ?>"
            data-name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
            data-value="<?php echo esc_attr($atts["value"] ?? "1"); ?>"
            <?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
        >
            <span class="pw-bui-toggle__knob" aria-hidden="true"></span>
        </button>

        <input
            type="hidden"
            name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
            value="<?php echo $checked
            	? esc_attr($atts["value"] ?? "1")
            	: ""; ?>"
        />
    </div>
</div>
