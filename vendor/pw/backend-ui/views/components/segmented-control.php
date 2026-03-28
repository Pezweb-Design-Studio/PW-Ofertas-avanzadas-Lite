<?php
// views/components/segmented-control.php

/**
 * Segmented Control component — PW Design System.
 * Permite elegir entre opciones mutuamente excluyentes.
 *
 * @var array $atts  SegmentedControl attributes from ComponentRenderer::segmented_control().
 */

defined("ABSPATH") || exit();

$name = $atts["name"] ?? "";
$current = $atts["value"] ?? "";
?>

<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
    <?php if (!empty($atts["label"])): ?>
        <p class="pw-bui-label"><?php echo esc_html($atts["label"]); ?></p>
    <?php endif; ?>

    <div
        class="pw-bui-segmented <?php echo esc_attr($atts["class"] ?? ""); ?>"
        data-pw-segmented="<?php echo esc_attr($name); ?>"
        role="group"
        aria-label="<?php echo esc_attr($atts["label"] ?? $name); ?>"
    >
        <?php foreach ($atts["options"] ?? [] as $option):

        	$val = $option["value"] ?? "";
        	$active = $val === $current;
        	?>
            <button
                type="button"
                class="pw-bui-segmented__btn <?php echo $active
                	? "pw-bui-segmented__btn--active"
                	: ""; ?>"
                data-pw-segment="<?php echo esc_attr($val); ?>"
                aria-selected="<?php echo $active ? "true" : "false"; ?>"
                <?php echo !empty($option["disabled"]) ? "disabled" : ""; ?>
            >
                <?php if (!empty($option["icon"])): ?>
                    <span aria-hidden="true" style="display:inline-flex;align-items:center;margin-right:4px;"><?php echo $option[
                    	"icon"
                    ]; ?></span>
                <?php endif; ?>
                <?php echo esc_html($option["label"] ?? ""); ?>
            </button>
        <?php
        endforeach; ?>

        <input type="hidden" name="<?php echo esc_attr(
        	$name,
        ); ?>" value="<?php echo esc_attr($current); ?>" />
    </div>

    <?php if (!empty($atts["help"])): ?>
        <p class="pw-bui-form-help"><?php echo esc_html($atts["help"]); ?></p>
    <?php endif; ?>
</div>
