<?php
// views/components/side-nav.php

/**
 * Side navigation component.
 * Inspired by WordPress settings panel sidebar (vertical nav).
 *
 * @var array $atts  Attributes from ComponentRenderer::side_nav().
 */

defined("ABSPATH") || exit();

if (empty($atts["items"])) {
	return;
}

$sidenav_class = implode(" ", array_filter(["pw-bui-sidenav", $atts["class"] ?? "", $atts["wrapper_class"] ?? ""]));
?>
<nav class="<?php echo esc_attr($sidenav_class); ?>">
<?php foreach ($atts["items"] as $item):

	// Separator
	if (isset($item["separator"]) && $item["separator"]):
		echo '<div class="pw-bui-sidenav__sep"></div>';
		continue;
	endif;

	// Group label
	if (isset($item["group"])):
		echo '<span class="pw-bui-sidenav__label">' .
			esc_html($item["group"]) .
			"</span>";
		continue;
	endif;

	$is_active = !empty($item["active"]);
	$classes =
		"pw-bui-sidenav__item" .
		($is_active ? " pw-bui-sidenav__item--active" : "");
	$tag = !empty($item["href"]) ? "a" : "button";
	$href_attr = !empty($item["href"])
		? ' href="' . esc_url($item["href"]) . '"'
		: "";
	$type_attr = $tag === "button" ? ' type="button"' : "";
	$aria_attr = $is_active ? ' aria-current="page"' : "";
	$data_attr = !empty($item["data"])
		? ' data-pw-sidenav="' . esc_attr($item["data"]) . '"'
		: "";
	?>
    <<?php echo $tag; ?>
        <?php echo $href_attr . $type_attr . $aria_attr . $data_attr; ?>
        class="<?php echo esc_attr($classes); ?>"
    >
        <?php if (!empty($item["icon"])): ?>
            <span class="pw-bui-sidenav__icon" aria-hidden="true"><?php echo $item[
            	"icon"
            ]; ?></span>
        <?php endif; ?>
        <span><?php echo esc_html($item["label"]); ?></span>
    </<?php echo $tag; ?>>

<?php
endforeach; ?>
</nav>
