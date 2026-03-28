<?php
// views/components/checkbox.php

/**
 * Checkbox component — PW Design System.
 *
 * @var array $atts  Checkbox attributes from ComponentRenderer::checkbox().
 */

defined("ABSPATH") || exit();

$checkbox_id = sanitize_title($atts["name"]) . "-" . wp_rand(1000, 9999);
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <div class="pw-bui-checkbox-item <?php echo esc_attr(
    	$atts["class"] ?? "",
    ); ?>">
        <input
            type="checkbox"
            id="<?php echo esc_attr($checkbox_id); ?>"
            name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
            value="<?php echo esc_attr($atts["value"] ?? "1"); ?>"
            class="pw-bui-checkbox-item__input"
            <?php checked($atts["checked"] ?? false); ?>
            <?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
        />
        <div>
            <?php if (!empty($atts["label"])): ?>
                <label for="<?php echo esc_attr(
                	$checkbox_id,
                ); ?>" class="pw-bui-checkbox-item__label">
                    <?php echo esc_html($atts["label"]); ?>
                </label>
            <?php endif; ?>
            <?php if (!empty($atts["help"])): ?>
                <p class="pw-bui-checkbox-item__help"><?php echo esc_html(
                	$atts["help"],
                ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
