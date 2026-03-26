<?php
// Datos compartidos de modos de apilamiento.
// Usados por settings.php (formulario) y dashboard.php (modal de ayuda).
defined('ABSPATH') || exit;

$stacking_options = [
    "priority_first" => [
        "title"       => "Prioridad primero",
        "recommended" => true,
        "description" => "Las campañas marcadas como \"Prioritarias\" siempre tienen precedencia. Si existen campañas prioritarias disponibles, se aplicará la de mayor descuento. Las campañas \"Apilables\" solo se usarán cuando NO haya campañas prioritarias aplicables.",
        "note_type"   => "neutral",
        "note"        => "<strong>Caso de uso:</strong> Ideal si quieres tener control total sobre qué descuento tiene más importancia. Útil para ofertas especiales que deben predominar sobre promociones generales.",
    ],
    "stack_first" => [
        "title"       => "Solo apilables (Clásico)",
        "recommended" => false,
        "description" => "Si existe AL MENOS una campaña \"Apilable\", se sumarán TODAS las campañas apilables disponibles e ignorará las prioritarias. Solo aplicará campañas prioritarias si no hay ninguna apilable aplicable.",
        "note_type"   => "neutral",
        "note"        => "<strong>Caso de uso:</strong> Comportamiento tradicional. Útil si prefieres que los descuentos se acumulen cuando sea posible.",
    ],
    "max_discount" => [
        "title"       => "Siempre el mejor descuento",
        "recommended" => false,
        "description" => "El sistema calcula AMBOS escenarios (suma de apilables vs mejor prioritario) y aplica automáticamente el que genere mayor ahorro para el cliente.",
        "note_type"   => "warning",
        "note"        => "<strong>⚠️ Precaución:</strong> Este modo puede generar descuentos totales mayores de los esperados si no configuras límites claros en tus campañas. Úsalo con cuidado.",
    ],
];
