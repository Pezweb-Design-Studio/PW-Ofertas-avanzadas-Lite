<?php
// src/Admin/Views/settings.php
defined("ABSPATH") || exit();

use PW\BackendUI\BackendUI;

/**
 * @var string $stacking_behavior  Valor guardado: 'priority_first' | 'stack_first' | 'max_discount'
 */

$bui              = BackendUI::init();
$current_behavior = $stacking_behavior ?? "priority_first";

require __DIR__ . '/data/stacking-options.php';

$bui->render_page([
    "title"       => __('Settings', 'pw-ofertas-avanzadas'),
    "description" => __('Configure global behavior for PW - Ofertas Avanzadas.', 'pw-ofertas-avanzadas'),
    "content"     => function ($bui) use ($current_behavior, $stacking_options): void {
        $ui = $bui->ui();

        echo '<form id="pwoa-settings-form">';
        wp_nonce_field("pwoa_nonce", "pwoa_nonce");

        $ui->card([
            "title"       => __('Multiple discount behavior', 'pw-ofertas-avanzadas'),
            "description" => __('Define how discounts are applied when several campaigns are active at the same time.', 'pw-ofertas-avanzadas'),
            "content"     => function () use ($current_behavior, $stacking_options, $ui): void {
                echo '<div style="display:flex;flex-direction:column;gap:12px;">';

                foreach ($stacking_options as $value => $opt) {
                    $selected     = $current_behavior === $value;
                    $border_color = $selected
                        ? "var(--pw-color-accent-emphasis)"
                        : "var(--pw-color-border-default)";
                    $bg_color     = $selected
                        ? "var(--pw-color-accent-subtle)"
                        : "transparent";
                    ?>
                    <label style="display:flex;align-items:flex-start;gap:14px;padding:18px;border:2px solid <?php echo $border_color; ?>;border-radius:6px;background:<?php echo $bg_color; ?>;cursor:pointer;">
                        <input type="radio"
                               name="stacking_behavior"
                               value="<?php echo esc_attr($value); ?>"
                               style="margin-top:3px;flex-shrink:0;"
                               <?php checked($current_behavior, $value); ?>>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                <strong style="font-size:14px;color:var(--pw-color-fg-default);"><?php echo esc_html($opt["title"]); ?></strong>
                                <?php if ($opt["recommended"]): ?>
                                    <?php $ui->badge(["label" => __('Recommended', 'pw-ofertas-avanzadas'), "variant" => "success", "size" => "sm"]); ?>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:13px;color:var(--pw-color-fg-muted);margin:0 0 10px;"><?php echo esc_html($opt["description"]); ?></p>
                            <?php
                            $note_type = $opt["note_type"] === "warning" ? "warning" : "info";
                            $ui->notice(["type" => $note_type, "message" => wp_kses_post($opt["note"])]);
                            ?>
                        </div>
                    </label>
                    <?php
                }

                echo '</div>';
            },
            "footer" => function () use ($ui): void {
                $ui->button([
                    "label"   => __('Save changes', 'pw-ofertas-avanzadas'),
                    "type"    => "submit",
                    "variant" => "primary",
                ]);
            },
        ]);

        echo '</form>';
    },
]);
