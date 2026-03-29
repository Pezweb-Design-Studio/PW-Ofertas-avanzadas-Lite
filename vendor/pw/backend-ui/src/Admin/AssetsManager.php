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

		// Apply persisted theme before the bundle runs to avoid flash of wrong theme.
		// Runs via WP API (wp_add_inline_script) instead of a raw <script> tag in the template.
		wp_add_inline_script(
			$slug . "-scripts",
			"try{var __pwt=localStorage.getItem('pw-bui-theme');if(__pwt==='light'||__pwt==='dark'){var __pwa=document.getElementById('pw-backend-ui-app');if(__pwa){__pwa.setAttribute('data-pw-theme',__pwt);}}}catch(e){}",
			"before",
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
