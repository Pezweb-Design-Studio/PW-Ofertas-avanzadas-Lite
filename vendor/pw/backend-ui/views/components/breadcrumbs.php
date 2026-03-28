<?php
// views/components/breadcrumbs.php

/**
 * Breadcrumbs component — PW Design System.
 *
 * @var array $atts  Breadcrumbs attributes from ComponentRenderer::breadcrumbs().
 */

defined("ABSPATH") || exit();

$items = $atts["items"] ?? [];
$last = count($items) - 1;
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<nav aria-label="Breadcrumb" class="<?php echo esc_attr(
	$atts["class"] ?? "",
); ?>">
    <ol class="pw-bui-breadcrumbs">
        <?php foreach ($items as $i => $item):
        	$is_last = $i === $last; ?>
            <li class="pw-bui-breadcrumbs__item <?php echo $is_last
            	? "pw-bui-breadcrumbs__item--current"
            	: ""; ?>">
                <?php if ($is_last || empty($item["href"])): ?>
                    <span <?php echo $is_last ? 'aria-current="page"' : ""; ?>>
                        <?php echo esc_html($item["label"] ?? ""); ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo esc_url($item["href"]); ?>">
                        <?php echo esc_html($item["label"] ?? ""); ?>
                    </a>
                <?php endif; ?>

                <?php if (!$is_last): ?>
                    <span class="pw-bui-breadcrumbs__sep" aria-hidden="true">/</span>
                <?php endif; ?>
            </li>
        <?php
        endforeach; ?>
    </ol>
</nav>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
