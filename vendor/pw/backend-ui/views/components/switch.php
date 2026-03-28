<?php
// views/components/switch.php
defined("ABSPATH") || exit();

$switch_id = "pw-switch-" . sanitize_title($atts["name"]);
$checked   = !empty($atts["checked"]);
$variant   = in_array($atts["variant"] ?? "default", ["default", "status"], true)
	? $atts["variant"]
	: "default";
$wrap_class = implode(" ", array_filter([
	"pw-bui-switch-wrap",
	$atts["class"] ?? "",
]));
$data_attrs = "";
foreach ((array) ($atts["data_attrs"] ?? []) as $key => $val) {
	$data_attrs .= " data-" . esc_attr($key) . '="' . esc_attr($val) . '"';
}
?>
<div class="pw-bui-form-group <?php echo esc_attr($atts["wrapper_class"] ?? ""); ?>">
	<div class="<?php echo esc_attr($wrap_class); ?>">
		<div>
			<?php if (!empty($atts["label"])): ?>
				<label for="<?php echo esc_attr($switch_id); ?>"
				       class="pw-bui-label"
				       style="margin-bottom:0;cursor:pointer;">
					<?php echo esc_html($atts["label"]); ?>
				</label>
			<?php endif; ?>
			<?php if (!empty($atts["help"])): ?>
				<p class="pw-bui-form-help" style="margin-top:2px;">
					<?php echo esc_html($atts["help"]); ?>
				</p>
			<?php endif; ?>
		</div>
		<div class="pw-bui-switch__control">
			<?php if (isset($atts["status_label"]) && $atts["status_label"] !== ""): ?>
				<span class="pw-bui-switch__status"><?php echo esc_html($atts["status_label"]); ?></span>
			<?php endif; ?>
			<label class="pw-bui-switch pw-bui-switch--<?php echo esc_attr($variant); ?>"
			       for="<?php echo esc_attr($switch_id); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr($switch_id); ?>"
					name="<?php echo esc_attr($atts["name"] ?? ""); ?>"
					value="<?php echo esc_attr($atts["value"] ?? "1"); ?>"
					class="pw-bui-switch__input"
					<?php checked($checked); ?>
					<?php echo !empty($atts["disabled"]) ? "disabled" : ""; ?>
					<?php echo $data_attrs; ?>
				>
				<span class="pw-bui-switch__slider" aria-hidden="true"></span>
			</label>
		</div>
	</div>
</div>
