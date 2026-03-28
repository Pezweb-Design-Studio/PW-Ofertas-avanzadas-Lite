<?php
// views/components/card.php

/**
 * Card container component — PW Design System.
 *
 * @var array $atts  Card attributes from ComponentRenderer::card().
 */

defined("ABSPATH") || exit(); ?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<div class="pw-bui-card <?php echo esc_attr($atts["class"] ?? ""); ?>">

    <?php if (!empty($atts["title"]) || !empty($atts["description"])): ?>
        <div class="pw-bui-card__header">
            <?php if (!empty($atts["title"])): ?>
                <h3 class="pw-bui-card__title"><?php echo esc_html(
                	$atts["title"],
                ); ?></h3>
            <?php endif; ?>
            <?php if (!empty($atts["description"])): ?>
                <p class="pw-bui-card__description"><?php echo esc_html(
                	$atts["description"],
                ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="pw-bui-card__body <?php echo empty($atts["padded"])
    	? "pw-bui-card__body--flush"
    	: ""; ?>">
        <?php if (is_callable($atts["content"])) {
        	call_user_func($atts["content"]);
        } ?>
    </div>

    <?php if (is_callable($atts["footer"] ?? null)): ?>
        <div class="pw-bui-card__footer">
            <?php call_user_func($atts["footer"]); ?>
        </div>
    <?php endif; ?>

</div>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
