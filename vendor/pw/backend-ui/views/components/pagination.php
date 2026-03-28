<?php
// views/components/pagination.php

/**
 * Pagination component — PW Design System.
 *
 * @var array $atts  Pagination attributes from ComponentRenderer::pagination().
 */

defined("ABSPATH") || exit();

$current = (int) ($atts["current"] ?? 1);
$total = (int) ($atts["total"] ?? 1);
$base_url = $atts["base_url"] ?? "#";
$window = (int) ($atts["window"] ?? 2); // pages around current

if ($total <= 1) {
	return;
}

// Build page URL helper
$page_url = function (int $page) use ($base_url, $atts) {
	$param = $atts["param"] ?? "paged";
	return add_query_arg($param, $page, $base_url);
};

// Build range with gaps
$pages = [];
$pages[] = 1;
for (
	$i = max(2, $current - $window);
	$i <= min($total - 1, $current + $window);
	$i++
) {
	$pages[] = $i;
}
$pages[] = $total;
$pages = array_unique($pages);
sort($pages);
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<nav aria-label="Paginación" class="<?php echo esc_attr(
	$atts["class"] ?? "",
); ?>">
    <div class="pw-bui-pagination">

        <?php
// Previous
?>
        <?php if ($current > 1): ?>
            <a href="<?php echo esc_url(
            	$page_url($current - 1),
            ); ?>" class="pw-bui-pagination__btn" aria-label="Página anterior">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M9.78 12.78a.75.75 0 0 1-1.06 0L4.47 8.53a.75.75 0 0 1 0-1.06l4.25-4.25a.751.751 0 0 1 1.042.018.751.751 0 0 1 .018 1.042L6.06 8l3.72 3.72a.75.75 0 0 1 0 1.06Z"/></svg>
            </a>
        <?php else: ?>
            <button class="pw-bui-pagination__btn" disabled aria-label="Página anterior">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M9.78 12.78a.75.75 0 0 1-1.06 0L4.47 8.53a.75.75 0 0 1 0-1.06l4.25-4.25a.751.751 0 0 1 1.042.018.751.751 0 0 1 .018 1.042L6.06 8l3.72 3.72a.75.75 0 0 1 0 1.06Z"/></svg>
            </button>
        <?php endif; ?>

        <?php
// Pages with gaps
?>
        <?php $prev = null; ?>
        <?php foreach ($pages as $page): ?>
            <?php if ($prev !== null && $page - $prev > 1): ?>
                <span class="pw-bui-pagination__gap" aria-hidden="true">…</span>
            <?php endif; ?>

            <?php if ($page === $current): ?>
                <span class="pw-bui-pagination__btn pw-bui-pagination__btn--active" aria-current="page" aria-label="Página <?php echo esc_attr(
                	$page,
                ); ?>">
                    <?php echo esc_html($page); ?>
                </span>
            <?php else: ?>
                <a
                    href="<?php echo esc_url($page_url($page)); ?>"
                    class="pw-bui-pagination__btn"
                    aria-label="Página <?php echo esc_attr($page); ?>"
                >
                    <?php echo esc_html($page); ?>
                </a>
            <?php endif; ?>

            <?php $prev = $page; ?>
        <?php endforeach; ?>

        <?php
// Next
?>
        <?php if ($current < $total): ?>
            <a href="<?php echo esc_url(
            	$page_url($current + 1),
            ); ?>" class="pw-bui-pagination__btn" aria-label="Página siguiente">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M6.22 3.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L9.94 8 6.22 4.28a.75.75 0 0 1 0-1.06Z"/></svg>
            </a>
        <?php else: ?>
            <button class="pw-bui-pagination__btn" disabled aria-label="Página siguiente">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M6.22 3.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L9.94 8 6.22 4.28a.75.75 0 0 1 0-1.06Z"/></svg>
            </button>
        <?php endif; ?>

    </div>
</nav>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
