<?php
// src/BackendUI.php

namespace PW\BackendUI;

use PW\BackendUI\Admin\AssetsManager;
use PW\BackendUI\Components\ComponentRenderer;

/**
 * Entry point for the pw/backend-ui design system.
 *
 * Singleton. Call BackendUI::init($config) once in plugins_loaded,
 * then use BackendUI::init()->render_page() to wrap your settings page
 * and BackendUI::init()->ui() to access individual component methods.
 */
class BackendUI
{
	private static ?self $instance = null;
	private AssetsManager $assets;
	private ComponentRenderer $ui;
	private array $config;

	private function __construct(array $config)
	{
		$this->config = wp_parse_args($config, [
			"assets_url" => "",
			"version" => "1.0.0",
			"screens" => [],
			"slug" => "pw-backend-ui",
			"brand" => [
				"name" => "",
				"logo_url" => "",
			],
		]);

		$this->assets = new AssetsManager($this->config);
		$this->ui = new ComponentRenderer($this->config);
	}

	/**
	 * Initialize the design system (singleton).
	 *
	 * @param array $config {
	 *     @type string   $assets_url  Full URL to the package assets/ dir (required).
	 *     @type string   $version     Package version for cache busting. Default: '1.0.0'.
	 *     @type string[] $screens     WP admin screen IDs where assets load.
	 *     @type string   $slug        Unique slug used for CSS/JS handles. Default: 'pw-backend-ui'.
	 *     @type array    $brand       { name: string, logo_url: string }
	 * }
	 */
	public static function init(array $config = []): self
	{
		if (self::$instance === null) {
			self::$instance = new self($config);
			self::$instance->boot();
		}
		return self::$instance;
	}

	private function boot(): void
	{
		add_action("admin_enqueue_scripts", [$this->assets, "enqueue"]);
	}

	/**
	 * Access the component renderer for individual UI elements.
	 */
	public function ui(): ComponentRenderer
	{
		return $this->ui;
	}

	/**
	 * Get the current config (all keys or a specific key).
	 */
	public function config(?string $key = null): mixed
	{
		if ($key === null) {
			return $this->config;
		}
		return $this->config[$key] ?? null;
	}

	/**
	 * Render a full settings page wrapped in the design system layout.
	 *
	 * @param array $page {
	 *     @type string   $title       Page title shown in the header.
	 *     @type string   $description Short description under the title.
	 *     @type array    $tabs        [ [ 'slug' => 'general', 'label' => 'General', 'active' => true ], ... ]
	 *     @type callable $content     function( BackendUI $bui ) — outputs the page body.
	 *     @type array    $sidebar     Optional sidebar: [ 'title' => '', 'content' => callable ].
	 *     @type array    $footer      Optional footer: [ 'left' => callable, 'right' => callable ].
	 * }
	 */
	public function render_page(array $page): void
	{
		$page = wp_parse_args($page, [
			"title"       => "",
			"description" => "",
			"breadcrumbs" => [],   // array de items [['label'=>..., 'href'=>...], ...]
			"tabs"        => [],
			"content"     => null,
			"sidenav"     => null, // callable|array — activa layout sidenav
			"sidebar"     => null,
			"footer"      => null,
		]);

		$page = apply_filters("pw_bui/page_config", $page);

		// $bui expuesto para que page-wrapper.php lo use sin depender de $this
		$bui = $this;
		include __DIR__ . "/../views/layout/page-wrapper.php";
	}

	/**
	 * Register a self-documenting playground admin page.
	 *
	 * Registers a WP admin menu page that showcases all components.
	 * Recommended to call only when WP_DEBUG is true.
	 *
	 *   if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	 *       BackendUI::playground();
	 *   }
	 *
	 * @param array $opts {
	 *     @type string $capability Capability required. Default: 'manage_options'.
	 *     @type int    $menu_order Menu position. Default: 99.
	 * }
	 */
	public static function playground(array $opts = []): void
	{
		static $registered = false;
		if ($registered) {
			return;
		}
		$registered = true;

		$opts = wp_parse_args($opts, [
			"capability" => "manage_options",
			"menu_order" => 99,
		]);

		add_action("admin_menu", function () use ($opts) {
			add_menu_page(
				"PW UI Playground",
				"PW Playground",
				$opts["capability"],
				"pw-bui-playground",
				[self::class, "_render_playground"],
				"dashicons-art",
				(int) $opts["menu_order"],
			);

			self::_register_playground_screen();
		});
	}

	/**
	 * Add the playground screen to AssetsManager so CSS/JS loads on it.
	 * @internal
	 */
	public static function _register_playground_screen(): void
	{
		$instance = self::$instance;
		if (!$instance) {
			return;
		}

		$pg_screen = "toplevel_page_pw-bui-playground";
		if (!in_array($pg_screen, $instance->config["screens"], true)) {
			$instance->config["screens"][] = $pg_screen;
			$instance->assets->update_config($instance->config);
		}
	}

	/**
	 * Render the playground page.
	 * @internal
	 */
	public static function _render_playground(): void
	{
		$bui = self::$instance;
		$page = [
			"title" => "PW UI Playground",
			"description" => "Todos los componentes del design system.",
			"tabs" => [
				[
					"slug" => "buttons",
					"label" => "Botones & Badges",
					"active" => true,
				],
				["slug" => "forms", "label" => "Formularios"],
				["slug" => "feedback", "label" => "Feedback"],
				["slug" => "navigation", "label" => "Navegación"],
				["slug" => "layout", "label" => "Tipo & Layout"],
			],
			"content" => function (BackendUI $bui) {
				include __DIR__ . "/../views/playground/playground.php";
			},
			"sidebar" => [
				"title" => "Componentes",
				"content" => function (BackendUI $bui) {
					$ui = $bui->ui();
					$ui->card([
						"content" => function () use ($ui) {
							$components = [
								"button",
								"badge",
								"input",
								"textarea",
								"select",
								"checkbox",
								"toggle",
								"radio_group",
								"date_input",
								"segmented_control",
								"card",
								"notice",
								"spinner",
								"progress_bar",
								"breadcrumbs",
								"pagination",
								"tooltip",
								"skeleton",
								"tabs",
								"separator",
								"heading",
								"paragraph",
								"link",
							];
							echo '<div style="display:flex;flex-direction:column;gap:4px;">';
							foreach ($components as $c) {
								echo '<code style="font-size:11px;color:var(--pw-color-fg-muted);">' .
									esc_html($c) .
									"()</code>";
							}
							echo "</div>";
						},
					]);
					$ui->separator();
					$ui->paragraph([
						"text" => "pw/backend-ui v1.1.0",
						"variant" => "muted",
					]);
				},
			],
		];

		$page = apply_filters("pw_bui/page_config", $page);
		include __DIR__ . "/../views/layout/page-wrapper.php";
	}

	/**
	 * Reset singleton — useful for testing.
	 * @internal
	 */
	public static function reset(): void
	{
		self::$instance = null;
	}
}
