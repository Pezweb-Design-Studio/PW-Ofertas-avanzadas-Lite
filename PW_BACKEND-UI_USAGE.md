# pw/backend-ui — Usage Guide

**v1.4.0** · Design system for WordPress admin plugin pages.
Stack: PHP 8.0+, WordPress 6.0+, Tailwind CSS CDN, vanilla JS.

---

## Installation

```json
// composer.json of your plugin
{
    "require": {
        "pw/backend-ui": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../[PACKAGE] pw-backend-ui"
        }
    ]
}
```

```bash
composer require pw/backend-ui
```

---

## Bootstrap

Call `BackendUI::init()` once inside `plugins_loaded`. Pass **`screens`** with the WP admin screen IDs where assets should load (leaving it empty means nothing loads).

```php
use PW\BackendUI\BackendUI;

add_action('plugins_loaded', function () {
    BackendUI::init([
        'assets_url' => plugin_dir_url(__FILE__) . 'vendor/pw/backend-ui/assets/',
        'version'    => '1.4.0',
        'slug'       => 'my-plugin',          // unique handle for CSS/JS
        'screens'    => [
            'toplevel_page_my-plugin',         // add your admin screen IDs here
            'my-plugin_page_my-plugin-settings',
        ],
        'brand' => [
            'name'     => 'My Plugin',
            'logo_url' => plugin_dir_url(__FILE__) . 'assets/logo.svg',
        ],
    ]);
});
```

> **How to find your screen ID:** In WordPress admin, open the page and check the URL. The `page=` param maps to a screen ID like `toplevel_page_{page_slug}` or `{parent}_page_{page_slug}`. You can also use `get_current_screen()->id` in a hook.

---

## Rendering a page

Use `render_page()` to wrap your settings page in the design system layout (header, nav tabs, content area, optional sidebar/footer).

```php
function my_plugin_settings_page() {
    $bui = BackendUI::init();

    $bui->render_page([
        'title'       => 'My Plugin Settings',
        'description' => 'Configure your plugin options.',
        'tabs'        => [
            ['slug' => 'general',  'label' => 'General',  'active' => true],
            ['slug' => 'advanced', 'label' => 'Advanced'],
        ],
        'content' => function (BackendUI $bui) {
            $ui = $bui->ui();

            $ui->tab_panel([
                'slug'    => 'general',
                'active'  => true,
                'content' => function () use ($ui) {
                    $ui->card([
                        'title'   => 'API Settings',
                        'content' => function () use ($ui) {
                            $ui->input([
                                'name'  => 'my_plugin_api_key',
                                'label' => 'API Key',
                                'value' => get_option('my_plugin_api_key', ''),
                            ]);
                        },
                    ]);
                },
            ]);
        },
        'sidebar' => [
            'title'   => 'Status',
            'content' => function (BackendUI $bui) {
                $bui->ui()->notice([
                    'type'    => 'success',
                    'message' => 'Plugin is active.',
                ]);
            },
        ],
    ]);
}
```

### `render_page()` options

| Key | Type | Description |
|-----|------|-------------|
| `title` | string | Page header title |
| `description` | string | Subtitle below title |
| `breadcrumbs` | array | `[['label' => '', 'href' => ''], ...]` |
| `tabs` | array | `[['slug' => '', 'label' => '', 'active' => false], ...]` |
| `content` | callable | `function(BackendUI $bui)` — main content area |
| `sidebar` | array | `['title' => '', 'content' => callable]` |
| `sidenav` | callable\|array | Activates a sidenav layout instead of top tabs |
| `footer` | array | `['left' => callable, 'right' => callable]` |

> **Note:** `tab_panel()` must not be used when tabs `mode` is `'url'`.

---

## Accessing components

```php
$ui = BackendUI::init()->ui(); // returns ComponentRenderer
$ui->button(['label' => 'Save']);
```

Or inside a `render_page()` callback, use the injected `$bui`:

```php
'content' => function (BackendUI $bui) {
    $ui = $bui->ui();
    $ui->button(['label' => 'Save']);
}
```

---

## Components reference

### button

```php
$ui->button([
    'label'    => 'Save Changes',
    'type'     => 'submit',               // 'button' | 'submit' | 'reset'
    'variant'  => 'primary',              // 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'invisible'
    'size'     => 'md',                   // 'sm' | 'md' | 'lg'
    'icon'     => '',                     // optional SVG HTML
    'disabled' => false,
    'attrs'    => ['data-action' => 'save'],
]);
```

### input

```php
$ui->input([
    'name'        => 'site_url',
    'label'       => 'Site URL',
    'value'       => get_option('site_url', ''),
    'type'        => 'text',              // 'text' | 'email' | 'password' | 'url' | 'number' | 'date' | 'search'
    'placeholder' => 'https://example.com',
    'help'        => 'The URL of your site.',
    'error'       => '',
    'required'    => false,
    'disabled'    => false,
]);
```

### textarea

```php
$ui->textarea([
    'name'  => 'description',
    'label' => 'Description',
    'value' => get_option('my_description', ''),
    'rows'  => 4,
    'help'  => 'Brief description.',
]);
```

### select

```php
$ui->select([
    'name'    => 'my_mode',
    'label'   => 'Mode',
    'value'   => get_option('my_mode', 'auto'),
    'options' => [
        'auto'   => 'Automatic',
        'manual' => 'Manual',
    ],
    // Or with full option arrays:
    // 'options' => [
    //     ['value' => 'auto', 'label' => 'Automatic'],
    //     ['value' => 'manual', 'label' => 'Manual', 'disabled' => true],
    // ],
]);
```

### checkbox

```php
$ui->checkbox([
    'name'    => 'enable_feature',
    'label'   => 'Enable feature',
    'checked' => (bool) get_option('enable_feature', false),
    'value'   => '1',
    'help'    => 'Enables the experimental feature.',
]);
```

### toggle

```php
$ui->toggle([
    'name'    => 'enable_notifications',
    'label'   => 'Enable notifications',
    'checked' => (bool) get_option('enable_notifications', false),
]);
```

### switch

```php
$ui->switch([
    'name'         => 'plugin_active',
    'label'        => 'Plugin active',
    'checked'      => (bool) get_option('plugin_active', false),
    'variant'      => 'default',          // 'default' | 'status'
    'status_label' => 'Active',           // used with variant='status'
    'data_attrs'   => ['data-nonce' => wp_create_nonce('toggle_plugin')],
]);
```

### radio / radio_group

```php
// Single radio
$ui->radio([
    'name'    => 'my_option',
    'label'   => 'Option A',
    'value'   => 'a',
    'checked' => get_option('my_option') === 'a',
]);

// Group
$ui->radio_group([
    'name'    => 'my_option',
    'label'   => 'Choose an option',
    'value'   => get_option('my_option', 'a'),
    'options' => [
        ['value' => 'a', 'label' => 'Option A'],
        ['value' => 'b', 'label' => 'Option B', 'help' => 'Advanced option'],
    ],
]);
```

### date_input

```php
$ui->date_input([
    'name'  => 'start_date',
    'label' => 'Start Date',
    'value' => get_option('start_date', ''),
    'min'   => '2024-01-01',
    'max'   => '2030-12-31',
]);
```

### segmented_control

```php
$ui->segmented_control([
    'name'    => 'view_mode',
    'label'   => 'View',
    'value'   => 'list',
    'options' => [
        ['value' => 'list', 'label' => 'List'],
        ['value' => 'grid', 'label' => 'Grid'],
    ],
]);
```

### card

```php
$ui->card([
    'title'       => 'Section Title',
    'description' => 'Optional subtitle.',
    'content'     => function () use ($ui) {
        $ui->input(['name' => 'field', 'label' => 'Field']);
    },
    'footer' => function () use ($ui) {
        $ui->button(['label' => 'Save', 'type' => 'submit']);
    },
    'padded' => true,
]);
```

### notice

```php
$ui->notice([
    'type'        => 'success',           // 'info' | 'success' | 'warning' | 'danger'
    'title'       => 'Settings saved',
    'message'     => 'Your changes were saved successfully.',
    'dismissible' => true,
]);
```

### badge

```php
$ui->badge([
    'label'   => 'Active',
    'variant' => 'success',              // 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
    'size'    => 'md',                   // 'sm' | 'md'
]);
```

### spinner

```php
$ui->spinner(['size' => 'md']);          // 'sm' | 'md' | 'lg'
```

### progress_bar

```php
$ui->progress_bar([
    'value'      => 65,                  // 0-100
    'label'      => 'Uploading...',
    'show_value' => true,
    'variant'    => 'default',           // 'default' | 'success' | 'warning' | 'danger' | 'info'
    'size'       => 'sm',                // 'sm' | 'md' | 'lg'
]);
```

### skeleton

```php
$ui->skeleton(['type' => 'text', 'lines' => 3]);   // 'text' | 'title' | 'box' | 'avatar'
$ui->skeleton(['type' => 'box', 'height' => '120px']);
```

### tooltip

```php
$ui->tooltip([
    'text'     => 'This is a tooltip',
    'position' => 'top',                 // 'top' | 'bottom'
    'trigger'  => function () use ($ui) {
        $ui->button(['label' => 'Hover me', 'variant' => 'ghost']);
    },
]);
```

### tabs + tab_panel

```php
// In render_page() 'tabs' key define the nav items.
// In content callback, wrap each section with tab_panel():

$ui->tab_panel([
    'slug'    => 'general',
    'active'  => true,
    'content' => function () use ($ui) {
        // panel content
    },
]);
```

### side_nav

Used inside `render_page()` with the `sidenav` key for a vertical sidebar navigation.

```php
$bui->render_page([
    'title'   => 'Settings',
    'sidenav' => function (BackendUI $bui) {
        $bui->ui()->side_nav([
            'items' => [
                ['label' => 'Connection',  'href' => '#connection',  'active' => true],
                ['label' => 'Advanced',    'href' => '#advanced'],
                ['separator' => true],
                ['group' => 'Danger Zone'],
                ['label' => 'Reset',       'href' => '#reset'],
            ],
        ]);
    },
    'content' => function (BackendUI $bui) { /* ... */ },
]);
```

### stepper (wizard)

The stepper must be placed **outside the `<form>`** element.

```php
$ui->stepper([
    'steps' => [
        ['slug' => 'step-1', 'label' => 'Account',  'state' => 'active'],
        ['slug' => 'step-2', 'label' => 'Settings', 'state' => 'pending'],
        ['slug' => 'step-3', 'label' => 'Finish',   'state' => 'pending'],
    ],
]);
```

### breadcrumbs

```php
$ui->breadcrumbs([
    'items' => [
        ['label' => 'Dashboard', 'href' => admin_url()],
        ['label' => 'My Plugin',  'href' => admin_url('admin.php?page=my-plugin')],
        ['label' => 'Settings'],  // last item = current page, no href
    ],
]);
```

### pagination

```php
$ui->pagination([
    'current'  => $current_page,
    'total'    => $total_pages,
    'base_url' => admin_url('admin.php?page=my-plugin'),
    'param'    => 'paged',
    'window'   => 2,
]);
```

### Typography

```php
$ui->heading(['text' => 'Section Title', 'level' => 2]);
$ui->paragraph(['text' => 'Some description.', 'variant' => 'muted']); // 'default' | 'muted' | 'small'
$ui->link(['label' => 'Learn more', 'href' => 'https://example.com', 'target' => '_blank']);
$ui->separator();
```

---

## Playground (development)

Register an admin page that showcases all components. Recommended only in debug mode.

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    BackendUI::playground();
}
```

This registers a "PW Playground" top-level menu page at position 99.

---

## Hooks

| Hook | Type | Description |
|------|------|-------------|
| `pw_bui/page_config` | filter | Modify the page config array before rendering |
| `pw_bui/enqueue_assets` | action | `($hook_suffix, $assets_url, $version)` — fired after assets are enqueued |

---

## Full plugin example

```php
<?php
/**
 * Plugin Name: My Plugin
 */

use PW\BackendUI\BackendUI;

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', function () {
    BackendUI::init([
        'assets_url' => plugin_dir_url(__FILE__) . 'vendor/pw/backend-ui/assets/',
        'version'    => '1.4.0',
        'slug'       => 'my-plugin',
        'screens'    => ['toplevel_page_my-plugin'],
    ]);
});

add_action('admin_menu', function () {
    add_menu_page(
        'My Plugin',
        'My Plugin',
        'manage_options',
        'my-plugin',
        'my_plugin_render_page',
        'dashicons-admin-generic',
        80
    );
});

function my_plugin_render_page(): void {
    $bui = BackendUI::init();

    $bui->render_page([
        'title'       => 'My Plugin',
        'description' => 'Manage your settings.',
        'tabs'        => [
            ['slug' => 'general', 'label' => 'General', 'active' => true],
        ],
        'content' => function (BackendUI $bui) {
            $ui = $bui->ui();

            $ui->tab_panel([
                'slug'    => 'general',
                'active'  => true,
                'content' => function () use ($ui) {
                    $ui->card([
                        'title'   => 'General Settings',
                        'content' => function () use ($ui) {
                            $ui->input([
                                'name'  => 'my_plugin_token',
                                'label' => 'API Token',
                                'value' => get_option('my_plugin_token', ''),
                                'type'  => 'password',
                            ]);
                            $ui->toggle([
                                'name'    => 'my_plugin_enabled',
                                'label'   => 'Enable plugin',
                                'checked' => (bool) get_option('my_plugin_enabled', false),
                            ]);
                        },
                        'footer' => function () use ($ui) {
                            $ui->button(['label' => 'Save', 'type' => 'submit']);
                        },
                    ]);
                },
            ]);
        },
    ]);
}
```
