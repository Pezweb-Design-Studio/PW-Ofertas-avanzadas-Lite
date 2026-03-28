<?php
// views/components/tooltip.php

/**
 * Tooltip component — PW Design System.
 * Hover/focus handled by CSS. Keyboard handled by JS.
 *
 * @var array $atts  Tooltip attributes from ComponentRenderer::tooltip().
 */

defined("ABSPATH") || exit();

$position = $atts["position"] ?? "top";

// top | bottom
?>

<span
    class="pw-bui-tooltip-wrap <?php echo esc_attr(trim(($atts["class"] ?? "") . " " . ($atts["wrapper_class"] ?? ""))); ?>"
    data-pw-tooltip="<?php echo esc_attr($atts["text"] ?? ""); ?>"
    tabindex="0"
    style="display:inline-flex;"
>
    <?php if (is_callable($atts["trigger"] ?? null)) {
    	call_user_func($atts["trigger"]);
    } else {
    	echo wp_kses_post($atts["trigger_html"] ?? "");
    } ?>
    <span
        class="pw-bui-tooltip<?php echo $position === "bottom"
        	? " pw-bui-tooltip--bottom"
        	: ""; ?>"
        role="tooltip"
    >
        <?php echo esc_html($atts["text"] ?? ""); ?>
    </span>
</span>
