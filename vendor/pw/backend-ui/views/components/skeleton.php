<?php
// views/components/skeleton.php

/**
 * Skeleton loading placeholder — PW Design System.
 *
 * @var array $atts  Skeleton attributes from ComponentRenderer::skeleton().
 */

defined("ABSPATH") || exit();

$type = $atts["type"] ?? "text"; // text | title | box | avatar
$lines = (int) ($atts["lines"] ?? 1);
$width = $atts["width"] ?? "100%";
$height = $atts["height"] ?? null;
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<div class="<?php echo esc_attr($atts["class"] ?? ""); ?>" aria-hidden="true">
    <?php if ($type === "avatar"): ?>
        <div
            class="pw-bui-skeleton pw-bui-skeleton--avatar"
            style="width:<?php echo esc_attr(
            	$width,
            ); ?>;height:<?php echo esc_attr($height ?? $width); ?>;"
        ></div>

    <?php // text — one or multiple lines
    	// Last line slightly shorter for realistic look

    	elseif ($type === "box"): ?>
        <div
            class="pw-bui-skeleton"
            style="width:<?php echo esc_attr(
            	$width,
            ); ?>;height:<?php echo esc_attr($height ?? "80px"); ?>;"
        ></div>

    <?php elseif ($type === "title"): ?>
        <div class="pw-bui-skeleton pw-bui-skeleton--title" style="width:<?php echo esc_attr(
        	$width,
        ); ?>;<?php echo $height
	? "height:" . esc_attr($height) . ";"
	: ""; ?>"></div>

    <?php else: ?>
        <?php for ($i = 0; $i < $lines; $i++):
        	$line_width = $i === $lines - 1 && $lines > 1 ? "70%" : $width; ?>
            <div
                class="pw-bui-skeleton pw-bui-skeleton--text"
                style="width:<?php echo esc_attr($line_width); ?>;"
            ></div>
        <?php
        endfor; ?>
    <?php endif; ?>
</div>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
