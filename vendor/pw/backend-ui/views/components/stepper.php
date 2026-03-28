<?php
// views/components/stepper.php

/**
 * Stepper / Wizard progress indicator — PW Design System.
 *
 * Renders a horizontal step indicator. Works with backend-ui.js wizard logic.
 * Each step has a state: pending | active | done.
 *
 * @var array $atts  Stepper attributes from ComponentRenderer::stepper().
 *
 * $atts['steps'] format:
 *   [
 *     [ 'slug' => 'curso',    'label' => 'Curso',    'state' => 'active'  ],
 *     [ 'slug' => 'empresa',  'label' => 'Empresa',  'state' => 'pending' ],
 *     [ 'slug' => 'alumnos',  'label' => 'Alumnos',  'state' => 'pending' ],
 *     [ 'slug' => 'config',   'label' => 'Detalles', 'state' => 'pending' ],
 *   ]
 */

defined("ABSPATH") || exit();

$steps = $atts["steps"] ?? [];
$total = count($steps);

if (empty($steps)) {
	return;
}
?>

<?php if (!empty($atts["wrapper_class"])): ?><div class="<?php echo esc_attr($atts["wrapper_class"]); ?>"><?php endif; ?>
<nav
	class="pw-bui-stepper <?php echo esc_attr($atts["class"] ?? ""); ?>"
	aria-label="<?php esc_attr_e("Pasos del formulario", "pw-backend-ui"); ?>"
	data-pw-stepper
>
	<?php foreach ($steps as $i => $step):

 	$slug = $step["slug"] ?? "";
 	$label = $step["label"] ?? "";
 	$state = $step["state"] ?? "pending"; // pending | active | done
 	$number = $i + 1;
 	$is_last = $i === $total - 1;
 	?>

		<div
			class="pw-bui-stepper__item pw-bui-stepper__item--<?php echo esc_attr(
   	$state,
   ); ?>"
			data-pw-step="<?php echo esc_attr($slug); ?>"
			data-pw-step-index="<?php echo esc_attr($i); ?>"
		>
			<div class="pw-bui-stepper__indicator">
				<?php if ($state === "done"): ?>
					<span class="pw-bui-stepper__icon" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 16 16" fill="none">
							<path d="M2 8l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				<?php else: ?>
					<span class="pw-bui-stepper__number" aria-hidden="true">
						<?php echo esc_html($number); ?>
					</span>
				<?php endif; ?>
			</div>

			<span class="pw-bui-stepper__label">
				<?php echo esc_html($label); ?>
			</span>

			<?php if (!$is_last): ?>
				<div class="pw-bui-stepper__connector" aria-hidden="true"></div>
			<?php endif; ?>
		</div>

	<?php
 endforeach; ?>
</nav>
<?php if (!empty($atts["wrapper_class"])): ?></div><?php endif; ?>
