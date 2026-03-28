<?php
// Shared stacking-mode copy for settings.php and dashboard.php help modal.
defined('ABSPATH') || exit;

$stacking_options = [
    'priority_first' => [
        'title'         => __('Priority first', 'pw-ofertas-avanzadas'),
        'recommended'   => true,
        'description'   => __('Campaigns marked as “Priority” always take precedence. If priority campaigns are available, the best discount among them is applied. “Stackable” campaigns are only used when no priority campaign applies.', 'pw-ofertas-avanzadas'),
        'note_type'     => 'neutral',
        'note'          => __('<strong>Use case:</strong> Best when you need full control over which discount wins. Ideal for special offers that must beat general promotions.', 'pw-ofertas-avanzadas'),
    ],
    'stack_first'    => [
        'title'         => __('Stackable only (classic)', 'pw-ofertas-avanzadas'),
        'recommended'   => false,
        'description'   => __('If at least one “Stackable” campaign applies, all stackable campaigns are summed and priority campaigns are ignored. Priority applies only when no stackable campaign applies.', 'pw-ofertas-avanzadas'),
        'note_type'     => 'neutral',
        'note'          => __('<strong>Use case:</strong> Traditional behavior. Use when you want discounts to add up whenever possible.', 'pw-ofertas-avanzadas'),
    ],
    'max_discount'   => [
        'title'         => __('Always the best discount', 'pw-ofertas-avanzadas'),
        'recommended'   => false,
        'description'   => __('The system evaluates both scenarios (sum of stackables vs best priority) and applies whichever saves the customer more.', 'pw-ofertas-avanzadas'),
        'note_type'     => 'warning',
        'note'          => __('<strong>Caution:</strong> This mode can produce higher total discounts than expected if campaigns are not capped. Use with care.', 'pw-ofertas-avanzadas'),
    ],
];
