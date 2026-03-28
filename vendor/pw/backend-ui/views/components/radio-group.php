<?php
// views/components/radio-group.php

/**
 * Radio Group component — PW Design System.
 * Renders a labeled group of radio buttons.
 *
 * @var array $atts  RadioGroup attributes from ComponentRenderer::radio_group().
 */

defined("ABSPATH") || exit(); ?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <?php if (!empty($atts["label"])): ?>
        <fieldset style="border:none;padding:0;margin:0;">
            <legend class="pw-bui-label"><?php echo esc_html(
            	$atts["label"],
            ); ?></legend>
            <?php if (!empty($atts["help"])): ?>
                <p class="pw-bui-form-help" style="margin-bottom:8px;"><?php echo esc_html(
                	$atts["help"],
                ); ?></p>
            <?php endif; ?>
            <div class="pw-bui-radio-group <?php echo esc_attr($atts["class"] ?? ""); ?>">
                <?php foreach ($atts["options"] ?? [] as $option):
                	$radio_id =
                		sanitize_title($atts["name"]) .
                		"-" .
                		sanitize_title($option["value"] ?? ""); ?>
                    <div class="pw-bui-radio-item">
                        <input
                            type="radio"
                            id="<?php echo esc_attr($radio_id); ?>"
                            name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
                            value="<?php echo esc_attr(
                            	$option["value"] ?? "",
                            ); ?>"
                            class="pw-bui-radio-item__input"
                            <?php checked(
                            	$atts["value"] ?? "",
                            	$option["value"] ?? "",
                            ); ?>
                            <?php echo !empty($option["disabled"])
                            	? "disabled"
                            	: ""; ?>
                        />
                        <div>
                            <label for="<?php echo esc_attr(
                            	$radio_id,
                            ); ?>" class="pw-bui-radio-item__label">
                                <?php echo esc_html($option["label"] ?? ""); ?>
                            </label>
                            <?php if (!empty($option["help"])): ?>
                                <p class="pw-bui-radio-item__help"><?php echo esc_html(
                                	$option["help"],
                                ); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                endforeach; ?>
            </div>
        </fieldset>
    <?php else: ?>
        <div class="pw-bui-radio-group <?php echo esc_attr($atts["class"] ?? ""); ?>">
            <?php foreach ($atts["options"] ?? [] as $option):
            	$radio_id =
            		sanitize_title($atts["name"]) .
            		"-" .
            		sanitize_title($option["value"] ?? ""); ?>
                <div class="pw-bui-radio-item">
                    <input
                        type="radio"
                        id="<?php echo esc_attr($radio_id); ?>"
                        name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
                        value="<?php echo esc_attr($option["value"] ?? ""); ?>"
                        class="pw-bui-radio-item__input"
                        <?php checked(
                        	$atts["value"] ?? "",
                        	$option["value"] ?? "",
                        ); ?>
                        <?php echo !empty($option["disabled"])
                        	? "disabled"
                        	: ""; ?>
                    />
                    <div>
                        <label for="<?php echo esc_attr(
                        	$radio_id,
                        ); ?>" class="pw-bui-radio-item__label">
                            <?php echo esc_html($option["label"] ?? ""); ?>
                        </label>
                        <?php if (!empty($option["help"])): ?>
                            <p class="pw-bui-radio-item__help"><?php echo esc_html(
                            	$option["help"],
                            ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($atts["error"])): ?>
        <p class="pw-bui-form-error"><?php echo esc_html($atts["error"]); ?></p>
    <?php endif; ?>
</div>
