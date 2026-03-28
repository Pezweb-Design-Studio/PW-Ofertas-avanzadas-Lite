<?php
// src/Admin/AssetsManager.php

namespace PW\BackendUI\Admin;

/**
 * Enqueues design system assets (CSS, JS) only on the configured admin screens.
 * The PW design system uses plain CSS variables — no Tailwind CDN required.
 */
class AssetsManager
{
	private array $config;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Update the internal config (used by BackendUI to add screens dynamically,
	 * e.g. when playground() is called after init()).
	 */
	public function update_config(array $config): void
	{
		$this->config = $config;
	}

	/**
	 * Hooked to admin_enqueue_scripts.
	 * Only loads assets on screens listed in config['screens'].
	 */
	public function enqueue(string $hook_suffix): void
	{
		if (!$this->should_load($hook_suffix)) {
			return;
		}

		$url = trailingslashit($this->config["assets_url"]);
		$version = $this->config["version"];
		$slug = $this->config["slug"];

		// Tailwind CSS CDN (disponible para el package y plugins consumidores)
		wp_enqueue_script(
			"tailwind-cdn",
			"https://cdn.tailwindcss.com",
			[],
			null,
			false,
		);

		// Package stylesheet (CSS variables + all component styles)
		wp_enqueue_style(
			$slug . "-styles",
			$url . "css/backend-ui.css",
			[],
			$version,
		);

		// Package JS (theme toggle, tabs, toggles, segmented, tooltips, dismiss)
		wp_enqueue_script(
			$slug . "-scripts",
			$url . "js/backend-ui.js",
			[],
			$version,
			true,
		);

		do_action("pw_bui/enqueue_assets", $hook_suffix, $url, $version);
	}

	/**
	 * Determines if assets should load on the current screen.
	 */
	private function should_load(string $hook_suffix): bool
	{
		$screens = $this->config["screens"] ?? [];

		if (empty($screens)) {
			return false;
		}

		$current_screen = get_current_screen();
		$screen_id = $current_screen ? $current_screen->id : $hook_suffix;

		return in_array($screen_id, $screens, true) ||
			in_array($hook_suffix, $screens, true);
	}
}
