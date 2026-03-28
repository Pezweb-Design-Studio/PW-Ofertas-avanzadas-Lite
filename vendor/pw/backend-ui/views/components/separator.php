<?php
// views/components/separator.php

/**
 * Separator / Divider component — PW Design System.
 *
 * @var array $atts  Separator attributes from ComponentRenderer::separator().
 */

defined("ABSPATH") || exit(); ?>
<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<hr class="pw-bui-separator <?php echo esc_attr($atts["class"] ?? ""); ?>" />
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
