<?php
// src/Components/ComponentRenderer.php

namespace PW\BackendUI\Components;

/**
 * Renders individual UI components — PW Design System.
 *
 * Each method outputs HTML directly. All methods accept an $atts array.
 * Components use CSS variables defined in backend-ui.css (no Tailwind utilities).
 */
class ComponentRenderer
{
	private array $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	// =========================================================================
	// BUTTONS
	// =========================================================================

	/**
	 * Render a button.
	 *
	 * @param array $atts {
	 *     @type string $label    Button text.
	 *     @type string $type     'button' | 'submit' | 'reset'. Default: 'button'.
	 *     @type string $variant  'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'invisible'. Default: 'primary'.
	 *     @type string $size     'sm' | 'md' | 'lg'. Default: 'md'.
	 *     @type string $icon     Optional icon SVG HTML (prepended to label).
	 *     @type bool   $disabled Whether button is disabled.
	 *     @type string $class    Additional CSS classes.
	 *     @type array  $attrs    Extra HTML attributes. E.g. ['data-action' => 'save'].
	 * }
	 */
	public function button(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"label" => "Button",
			"type" => "button",
			"variant" => "primary",
			"size" => "md",
			"icon" => "",
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
			"attrs" => [],
		]);
		include __DIR__ . "/../../views/components/button.php";
	}

	// =========================================================================
	// FORM INPUTS
	// =========================================================================

	/**
	 * Render a text input with label.
	 *
	 * @param array $atts {
	 *     @type string $name        Input name attribute.
	 *     @type string $label       Label text.
	 *     @type string $value       Current value.
	 *     @type string $placeholder Placeholder text.
	 *     @type string $type        'text' | 'email' | 'password' | 'url' | 'number' | 'date' | 'search'. Default: 'text'.
	 *     @type string $min         Min value (for number/date).
	 *     @type string $max         Max value (for number/date).
	 *     @type string $help        Help text shown below input.
	 *     @type string $error       Error message (shows error state).
	 *     @type bool   $required    Whether field is required.
	 *     @type bool   $disabled    Whether field is disabled.
	 *     @type string $class       Additional CSS classes.
	 * }
	 */
	public function input(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"placeholder" => "",
			"type" => "text",
			"min" => "",
			"max" => "",
			"help" => "",
			"error" => "",
			"required" => false,
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/input.php";
	}

	/**
	 * Render a textarea with label.
	 *
	 * @param array $atts {
	 *     @type string $name        Input name attribute.
	 *     @type string $label       Label text.
	 *     @type string $value       Current value.
	 *     @type string $placeholder Placeholder text.
	 *     @type int    $rows        Number of rows. Default: 4.
	 *     @type string $help        Help text.
	 *     @type string $error       Error message.
	 *     @type bool   $required    Whether field is required.
	 *     @type bool   $disabled    Whether field is disabled.
	 *     @type string $class       Additional CSS classes.
	 * }
	 */
	public function textarea(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"placeholder" => "",
			"rows" => 4,
			"help" => "",
			"error" => "",
			"required" => false,
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/textarea.php";
	}

	/**
	 * Render a select dropdown with label.
	 *
	 * @param array $atts {
	 *     @type string $name        Input name attribute.
	 *     @type string $label       Label text.
	 *     @type string $value       Currently selected value.
	 *     @type array  $options     [ 'value' => 'Label', ... ] or [ [ 'value' => '', 'label' => '', 'disabled' => false ], ... ]
	 *     @type string $placeholder Placeholder option (empty value). Default: '— Seleccionar —'.
	 *     @type string $help        Help text.
	 *     @type string $error       Error message.
	 *     @type bool   $required    Whether field is required.
	 *     @type bool   $disabled    Whether field is disabled.
	 *     @type string $class       Additional CSS classes.
	 * }
	 */
	public function select(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"options" => [],
			"placeholder" => "— Seleccionar —",
			"help" => "",
			"error" => "",
			"required" => false,
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/select.php";
	}

	/**
	 * Render a checkbox.
	 *
	 * @param array $atts {
	 *     @type string $name     Input name attribute.
	 *     @type string $label    Label text next to checkbox.
	 *     @type bool   $checked  Whether checked.
	 *     @type string $value    Input value. Default: '1'.
	 *     @type string $help     Help text.
	 *     @type bool   $disabled Whether disabled.
	 *     @type string $class    Additional CSS classes.
	 * }
	 */
	public function checkbox(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"checked" => false,
			"value" => "1",
			"help" => "",
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/checkbox.php";
	}

	/**
	 * Render a toggle switch.
	 *
	 * @param array $atts {
	 *     @type string $name     Input name attribute.
	 *     @type string $label    Label text.
	 *     @type bool   $checked  Whether toggled on.
	 *     @type string $value    Input value. Default: '1'.
	 *     @type string $help     Help text.
	 *     @type bool   $disabled Whether disabled.
	 *     @type string $class    Additional CSS classes.
	 * }
	 */
	public function toggle(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"checked" => false,
			"value" => "1",
			"help" => "",
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/toggle.php";
	}

	/**
	 * Render a radio button (standalone). For groups, use radio_group().
	 *
	 * @param array $atts {
	 *     @type string $name     Input name attribute.
	 *     @type string $label    Label text.
	 *     @type string $value    Input value.
	 *     @type bool   $checked  Whether selected.
	 *     @type string $help     Help text.
	 *     @type bool   $disabled Whether disabled.
	 *     @type string $class    Additional CSS classes.
	 * }
	 */
	public function radio(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"checked" => false,
			"help" => "",
			"disabled" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/radio.php";
	}

	/**
	 * Render a radio button group with label.
	 *
	 * @param array $atts {
	 *     @type string $name    Input name (shared by all radios in the group).
	 *     @type string $label   Group label (rendered as <legend>).
	 *     @type string $value   Currently selected value.
	 *     @type array  $options [ [ 'value' => '', 'label' => '', 'help' => '', 'disabled' => false ], ... ]
	 *     @type string $help    Group-level help text.
	 *     @type string $error   Error message.
	 *     @type string $class   Additional CSS classes.
	 * }
	 */
	public function radio_group(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"options" => [],
			"help" => "",
			"error" => "",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/radio-group.php";
	}

	/**
	 * Render a date input.
	 * Alias de input() con type='date' y opciones específicas de fecha.
	 *
	 * @param array $atts {
	 *     @type string $name     Input name.
	 *     @type string $label    Label text.
	 *     @type string $value    Current date value (Y-m-d).
	 *     @type string $min      Minimum date (Y-m-d).
	 *     @type string $max      Maximum date (Y-m-d).
	 *     @type string $help     Help text.
	 *     @type string $error    Error message.
	 *     @type bool   $required Whether required.
	 *     @type bool   $disabled Whether disabled.
	 *     @type string $class    Additional CSS classes.
	 * }
	 */
	public function date_input(array $atts = []): void
	{
		$atts = wp_parse_args($atts, ["type" => "date"]);
		$atts["type"] = "date";
		$this->input($atts);
	}

	/**
	 * Render a segmented control (mutually exclusive options).
	 *
	 * @param array $atts {
	 *     @type string $name    Input name (for the hidden input).
	 *     @type string $label   Label shown above the control.
	 *     @type string $value   Currently selected value.
	 *     @type array  $options [ [ 'value' => '', 'label' => '', 'icon' => '', 'disabled' => false ], ... ]
	 *     @type string $help    Help text.
	 *     @type string $class   Additional CSS classes.
	 * }
	 */
	public function segmented_control(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name" => "",
			"label" => "",
			"value" => "",
			"options" => [],
			"help" => "",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/segmented-control.php";
	}

	// =========================================================================
	// LAYOUT & STRUCTURE
	// =========================================================================

	/**
	 * Render a card container.
	 *
	 * @param array $atts {
	 *     @type string   $title       Card title (optional).
	 *     @type string   $description Card subtitle/description (optional).
	 *     @type callable $content     Function that outputs the card body.
	 *     @type callable $footer      Function that outputs the card footer.
	 *     @type bool     $padded      Whether to add padding to the body. Default: true.
	 *     @type string   $class       Additional CSS classes.
	 * }
	 */
	public function card(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"title" => "",
			"description" => "",
			"content" => null,
			"footer" => null,
			"padded" => true,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/card.php";
	}

	/**
	 * Render a notice/alert/banner.
	 *
	 * @param array $atts {
	 *     @type string $title       Optional bold title above the message.
	 *     @type string $message     Notice text (supports basic HTML via wp_kses_post).
	 *     @type string $type        'info' | 'success' | 'warning' | 'danger'. Default: 'info'.
	 *     @type bool   $dismissible Whether notice can be dismissed. Default: false.
	 *     @type string $class       Additional CSS classes.
	 * }
	 */
	public function notice(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"title" => "",
			"message" => "",
			"type" => "info",
			"dismissible" => false,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/notice.php";
	}

	/**
	 * Render a badge/tag/label.
	 *
	 * @param array $atts {
	 *     @type string $label   Badge text.
	 *     @type string $variant 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'. Default: 'default'.
	 *     @type string $size    'sm' | 'md'. Default: 'md'.
	 *     @type string $class   Additional CSS classes.
	 * }
	 */
	public function badge(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"label" => "",
			"variant" => "default",
			"size" => "md",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/badge.php";
	}

	/**
	 * Render a spinner (indeterminate loading indicator).
	 *
	 * @param array $atts {
	 *     @type string $size  'sm' | 'md' | 'lg'. Default: 'md'.
	 *     @type string $label Accessible label. Default: 'Cargando...'.
	 *     @type string $class Additional CSS classes.
	 * }
	 */
	public function spinner(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"size" => "md",
			"label" => "Cargando...",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/spinner.php";
	}

	/**
	 * Render a progress bar.
	 *
	 * @param array $atts {
	 *     @type int    $value      Current value (0-100).
	 *     @type string $label      Optional label shown above the bar.
	 *     @type bool   $show_value Show numeric percentage next to label. Default: false.
	 *     @type string $variant    'default' | 'success' | 'warning' | 'danger' | 'info'. Default: 'default'.
	 *     @type string $size       'sm' | 'md' | 'lg'. Default: 'sm'.
	 *     @type string $help       Help text.
	 *     @type string $class      Additional CSS classes.
	 * }
	 */
	public function progress_bar(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"value" => 0,
			"label" => "",
			"show_value" => false,
			"variant" => "default",
			"size" => "sm",
			"help" => "",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/progress-bar.php";
	}

	/**
	 * Render breadcrumbs navigation.
	 *
	 * @param array $atts {
	 *     @type array  $items  [ [ 'label' => '', 'href' => '' ], ... ] — last item = current page (no href needed).
	 *     @type string $class  Additional CSS classes.
	 * }
	 */
	public function breadcrumbs(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"items" => [],
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/breadcrumbs.php";
	}

	/**
	 * Render a pagination control.
	 *
	 * @param array $atts {
	 *     @type int    $current   Current page number.
	 *     @type int    $total     Total number of pages.
	 *     @type string $base_url  Base URL for page links. Default: current URL.
	 *     @type string $param     Query parameter name for the page. Default: 'paged'.
	 *     @type int    $window    Pages to show around current page. Default: 2.
	 *     @type string $class     Additional CSS classes.
	 * }
	 */
	public function pagination(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"current" => 1,
			"total" => 1,
			"base_url" => remove_query_arg("paged"),
			"param" => "paged",
			"window" => 2,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/pagination.php";
	}

	/**
	 * Render a tooltip wrapper.
	 * The trigger content must be passed as a callable or raw HTML.
	 *
	 * @param array $atts {
	 *     @type string        $text         Tooltip text.
	 *     @type callable|null $trigger      Callable that outputs the trigger element.
	 *     @type string        $trigger_html Raw HTML for the trigger (used if $trigger is null).
	 *     @type string        $position     'top' | 'bottom'. Default: 'top'.
	 *     @type string        $class        Additional CSS classes on the wrapper.
	 * }
	 */
	public function tooltip(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"text" => "",
			"trigger" => null,
			"trigger_html" => "",
			"position" => "top",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/tooltip.php";
	}

	/**
	 * Render a skeleton loading placeholder.
	 *
	 * @param array $atts {
	 *     @type string $type   'text' | 'title' | 'box' | 'avatar'. Default: 'text'.
	 *     @type int    $lines  Number of text lines (only for type='text'). Default: 1.
	 *     @type string $width  CSS width. Default: '100%'.
	 *     @type string $height CSS height (for box/avatar types).
	 *     @type string $class  Additional CSS classes.
	 * }
	 */
	public function skeleton(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"type" => "text",
			"lines" => 1,
			"width" => "100%",
			"height" => null,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/skeleton.php";
	}

	// =========================================================================
	// TYPOGRAPHY
	// =========================================================================

	/**
	 * Render a heading (h1-h6).
	 *
	 * @param array $atts {
	 *     @type string $text  Heading text.
	 *     @type int    $level 1-6 for h1-h6. Default: 2.
	 *     @type string $class Additional CSS classes.
	 * }
	 */
	public function heading(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"text" => "",
			"level" => 2,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/heading.php";
	}

	/**
	 * Render a paragraph.
	 *
	 * @param array $atts {
	 *     @type string $text    Paragraph text (supports basic HTML).
	 *     @type string $variant 'default' | 'muted' | 'small'. Default: 'default'.
	 *     @type string $class   Additional CSS classes.
	 * }
	 */
	public function paragraph(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"text" => "",
			"variant" => "default",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/paragraph.php";
	}

	/**
	 * Render a link.
	 *
	 * @param array $atts {
	 *     @type string $label   Link text.
	 *     @type string $href    URL. Default: '#'.
	 *     @type string $target  '_self' | '_blank'. Default: '_self'.
	 *     @type string $variant 'default' | 'muted' | 'danger'. Default: 'default'.
	 *     @type string $class   Additional CSS classes.
	 * }
	 */
	public function link(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"label" => "",
			"href" => "#",
			"target" => "_self",
			"variant" => "default",
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/link.php";
	}

	/**
	 * Render a horizontal separator.
	 *
	 * @param array $atts {
	 *     @type string $class Additional CSS classes.
	 * }
	 */
	public function separator(array $atts = []): void
	{
		$atts = wp_parse_args($atts, ["class" => "", "wrapper_class" => ""]);
		include __DIR__ . "/../../views/components/separator.php";
	}

	// =========================================================================
	// NAVIGATION
	// =========================================================================

	/**
	 * Render a tabs navigation (UnderlineNav style, Primer-inspired).
	 *
	 * @param array $atts {
	 *     @type array  $tabs  [ [ 'slug' => '', 'label' => '', 'active' => false, 'count' => null ], ... ]
	 *     @type string $class Additional CSS classes.
	 * }
	 */
	public function tabs(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"tabs" => [],
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/tabs.php";
	}

	/**
	 * Render a tab panel content wrapper.
	 *
	 * @param array $atts {
	 *     @type string   $slug    Tab slug matching the tabs() definition.
	 *     @type bool     $active  Whether this panel is visible initially. Default: false.
	 *     @type callable $content Function that outputs the panel content.
	 *     @type string   $class   Additional CSS classes.
	 * }
	 */
	public function tab_panel(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"slug" => "",
			"active" => false,
			"content" => null,
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/tab-panel.php";
	}

	/**
	 * Render breadcrumbs navigation.
	 * Alias kept here for discoverability — delegates to breadcrumbs().
	 *
	 * @see self::breadcrumbs()
	 */
	public function breadcrumb(array $atts = []): void
	{
		$this->breadcrumbs($atts);
	}

	// =========================================================================
	// SIDE NAVIGATION
	// =========================================================================

	/**
	 * Render a vertical side navigation (sidebar nav, subtabs style).
	 *
	 * Designed to be used inside a sidenav layout (render_page with 'sidenav' key).
	 * Supports groups, separators, active states, optional icons, and href or button items.
	 *
	 * @param array $atts {
	 *     @type array $items Array of nav items. Each item:
	 *         - Regular item:  [ 'label' => '', 'href' => '', 'active' => false, 'icon' => '', 'data' => '' ]
	 *         - Group label:   [ 'group' => 'Sección' ]
	 *         - Separator:     [ 'separator' => true ]
	 *     @type string $class Additional CSS classes for the wrapper.
	 * }
	 *
	 * @example
	 *   $ui->side_nav([
	 *       'items' => [
	 *           [ 'label' => 'Conexión',          'href' => '#conexion',  'active' => true ],
	 *           [ 'label' => 'Enlazar Proyectos',  'href' => '#proyectos' ],
	 *           [ 'separator' => true ],
	 *           [ 'group' => 'Avanzado' ],
	 *           [ 'label' => 'Logs', 'href' => '#logs' ],
	 *       ],
	 *   ]);
	 */
	public function side_nav(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"items" => [],
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/side-nav.php";
	}

	// =========================================================================
	// STEPPER / WIZARD
	// =========================================================================

	/**
	 * Render a step progress indicator for multi-step forms (wizard).
	 *
	 * Works with the wizard JS logic in backend-ui.js:
	 *   - Advancing a step marks the previous as 'done' and activates the next.
	 *   - Navigation backwards is always allowed; forward requires JS validation.
	 *
	 * @param array $atts {
	 *     @type array  $steps  Required. Array of step definitions:
	 *                          [
	 *                            [ 'slug' => string, 'label' => string, 'state' => 'pending|active|done' ],
	 *                            ...
	 *                          ]
	 *                          The first step should have state 'active', the rest 'pending'.
	 *     @type string $class  Additional CSS classes.
	 * }
	 */
	public function stepper(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"steps" => [],
			"class" => "",
			"wrapper_class" => "",
		]);
		include __DIR__ . "/../../views/components/stepper.php";
	}

	/**
	 * Render a switch (checkbox-based toggle).
	 *
	 * @param array $atts {
	 *     @type string $name     Input name attribute.
	 *     @type string $label    Label text.
	 *     @type bool   $checked  Whether checked.
	 *     @type string $value    Input value. Default: '1'.
	 *     @type string $variant  Visual variant: 'default' | 'status'. Default: 'default'.
	 *     @type string $help     Help text.
	 *     @type bool   $disabled Whether disabled.
	 *     @type string $class      Additional CSS classes on the wrap.
	 *     @type array  $data_attrs Key→value pairs rendered as data-* on the input.
	 * }
	 */
	public function switch(array $atts = []): void
	{
		$atts = wp_parse_args($atts, [
			"name"         => "",
			"label"        => "",
			"checked"      => false,
			"value"        => "1",
			"variant"      => "default",
			"help"         => "",
			"disabled"     => false,
			"class"        => "",
			"wrapper_class" => "",
			"data_attrs"   => [],
			"status_label" => "",
		]);
		include __DIR__ . "/../../views/components/switch.php";
	}
}
