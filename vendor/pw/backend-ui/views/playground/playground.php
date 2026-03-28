<?php
// views/playground/playground.php
// PW UI Playground — showcase de todos los componentes.
// @var \PW\BackendUI\BackendUI $bui

defined("ABSPATH") || exit();
$ui = $bui->ui();

if (!function_exists("pw_pg_section")):
	function pw_pg_section(string $title, string $desc = ""): void
	{
		echo '<div style="margin-bottom:24px;"><h3 style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--pw-color-fg-muted);margin:0 0 12px;padding-bottom:8px;border-bottom:1px solid var(--pw-color-border-default);">' .
			esc_html($title) .
			"</h3>";
		if ($desc) {
			echo '<p style="font-size:12px;color:var(--pw-color-fg-muted);margin:0 0 12px;">' .
				esc_html($desc) .
				"</p>";
		}
	}
	function pw_pg_section_end(): void
	{
		echo "</div>";
	}
	function pw_pg_row(string $lbl = ""): void
	{
		if ($lbl) {
			echo '<p style="font-size:11px;color:var(--pw-color-fg-subtle);margin:0 0 6px;">' .
				esc_html($lbl) .
				"</p>";
		}
		echo '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:16px;">';
	}
	function pw_pg_row_end(): void
	{
		echo "</div>";
	}
endif;

// ── TAB 1: BOTONES & BADGES ──────────────────────────────────────────────────
$ui->tab_panel([
	"slug" => "buttons",
	"active" => true,
	"content" => function () use ($ui) {
		pw_pg_section("Botones — Variantes");
		pw_pg_row();
		foreach (
			["primary", "secondary", "outline", "ghost", "danger", "invisible"]
			as $v
		) {
			$ui->button(["label" => ucfirst($v), "variant" => $v]);
		}
		pw_pg_row_end();
		pw_pg_section_end();

		pw_pg_section("Botones — Tamaños");
		pw_pg_row();
		foreach (
			["sm" => "Small", "md" => "Medium", "lg" => "Large"]
			as $s => $l
		) {
			$ui->button(["label" => $l, "size" => $s]);
		}
		pw_pg_row_end();
		pw_pg_section_end();

		pw_pg_section("Con icono & disabled");
		$chk =
			'<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"/></svg>';
		$plus =
			'<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M7.75 2a.75.75 0 0 1 .75.75V7h4.25a.75.75 0 0 1 0 1.5H8.5v4.25a.75.75 0 0 1-1.5 0V8.5H2.75a.75.75 0 0 1 0-1.5H7V2.75A.75.75 0 0 1 7.75 2Z"/></svg>';
		pw_pg_row();
		$ui->button([
			"label" => "Guardar",
			"variant" => "primary",
			"icon" => $chk,
		]);
		$ui->button([
			"label" => "Agregar",
			"variant" => "outline",
			"icon" => $plus,
		]);
		$ui->button(["label" => "Disabled primary", "disabled" => true]);
		$ui->button([
			"label" => "Disabled danger",
			"variant" => "danger",
			"disabled" => true,
		]);
		pw_pg_row_end();
		pw_pg_section_end();

		pw_pg_section("Badges / Labels");
		pw_pg_row("Tamaño md");
		foreach (
			["default", "primary", "success", "warning", "danger", "info"]
			as $v
		) {
			$ui->badge(["label" => ucfirst($v), "variant" => $v]);
		}
		pw_pg_row_end();
		pw_pg_row("Tamaño sm");
		foreach (["default", "success", "danger", "info"] as $v) {
			$ui->badge([
				"label" => ucfirst($v),
				"variant" => $v,
				"size" => "sm",
			]);
		}
		pw_pg_row_end();
		pw_pg_section_end();
	},
]);

// ── TAB 2: FORMULARIOS ───────────────────────────────────────────────────────
$ui->tab_panel([
	"slug" => "forms",
	"content" => function () use ($ui) {
		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">';
		echo "<div>";
		pw_pg_section("Inputs");
		$ui->input([
			"name" => "t1",
			"label" => "Normal",
			"placeholder" => "Escribe algo...",
		]);
		$ui->input([
			"name" => "t2",
			"label" => "Requerido",
			"required" => true,
		]);
		$ui->input([
			"name" => "t3",
			"label" => "Con error",
			"value" => "invalid",
			"error" => "Campo obligatorio.",
		]);
		$ui->input([
			"name" => "t4",
			"label" => "Con ayuda",
			"help" => "Se mostrará en el header.",
		]);
		$ui->input([
			"name" => "t5",
			"label" => "Disabled",
			"value" => "no editable",
			"disabled" => true,
		]);
		pw_pg_section_end();
		pw_pg_section("Tipos especiales");
		$ui->input([
			"name" => "em",
			"label" => "Email",
			"type" => "email",
			"placeholder" => "usuario@ejemplo.com",
		]);
		$ui->input([
			"name" => "nm",
			"label" => "Número",
			"type" => "number",
			"min" => "0",
			"max" => "100",
		]);
		$ui->input([
			"name" => "ur",
			"label" => "URL",
			"type" => "url",
			"placeholder" => "https://",
		]);
		$ui->date_input([
			"name" => "dt",
			"label" => "Fecha",
			"help" => "Formato AAAA-MM-DD",
		]);
		pw_pg_section_end();
		echo "</div><div>";
		pw_pg_section("Textarea & Select");
		$ui->textarea([
			"name" => "ta1",
			"label" => "Descripción",
			"placeholder" => "Escribe...",
			"rows" => 3,
		]);
		$ui->textarea([
			"name" => "ta2",
			"label" => "Con error",
			"error" => "No puede estar vacío.",
		]);
		$ui->select([
			"name" => "sl1",
			"label" => "País",
			"options" => ["cl" => "Chile", "ar" => "Argentina", "pe" => "Perú"],
			"help" => "Tu país de origen.",
		]);
		$ui->select([
			"name" => "sl2",
			"label" => "Select con error",
			"options" => ["1h" => "1 hora", "24h" => "24 horas"],
			"error" => "Selecciona una opción.",
		]);
		pw_pg_section_end();
		pw_pg_section("Checkbox & Toggle");
		$ui->checkbox([
			"name" => "c1",
			"label" => "Acepto los términos y condiciones",
		]);
		$ui->checkbox([
			"name" => "c2",
			"label" => "Notificaciones por email",
			"checked" => true,
		]);
		$ui->checkbox([
			"name" => "c3",
			"label" => "Deshabilitado",
			"disabled" => true,
		]);
		$ui->toggle([
			"name" => "tg1",
			"label" => "Modo mantenimiento",
			"help" => "Oculta el sitio al público.",
		]);
		$ui->toggle([
			"name" => "tg2",
			"label" => "Caché activado",
			"checked" => true,
		]);
		$ui->toggle([
			"name" => "tg3",
			"label" => "Deshabilitado",
			"disabled" => true,
		]);
		pw_pg_section_end();
		echo "</div></div>";
		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:8px;">';
		echo "<div>";
		pw_pg_section("Radio Group");
		$ui->radio_group([
			"name" => "plan",
			"label" => "Plan de suscripción",
			"value" => "pro",
			"options" => [
				[
					"value" => "free",
					"label" => "Gratis",
					"help" => "Hasta 3 proyectos.",
				],
				[
					"value" => "pro",
					"label" => "Pro",
					"help" => "Proyectos ilimitados.",
				],
				[
					"value" => "ent",
					"label" => "Enterprise",
					"help" => "SLA + soporte.",
					"disabled" => true,
				],
			],
		]);
		pw_pg_section_end();
		echo "</div><div>";
		pw_pg_section("Segmented Control");
		$ui->segmented_control([
			"name" => "vm",
			"label" => "Modo de vista",
			"value" => "grid",
			"options" => [
				["value" => "list", "label" => "Lista"],
				["value" => "grid", "label" => "Cuadrícula"],
				["value" => "map", "label" => "Mapa", "disabled" => true],
			],
		]);
		$ui->segmented_control([
			"name" => "per",
			"label" => "Período",
			"value" => "30d",
			"options" => [
				["value" => "7d", "label" => "7 días"],
				["value" => "30d", "label" => "30 días"],
				["value" => "90d", "label" => "90 días"],
				["value" => "1y", "label" => "1 año", "disabled" => true],
			],
		]);
		pw_pg_section_end();
		echo "</div></div>";
	},
]);

// ── TAB 3: FEEDBACK ──────────────────────────────────────────────────────────
$ui->tab_panel([
	"slug" => "feedback",
	"content" => function () use ($ui) {
		pw_pg_section("Notices / Banners");
		$ui->notice([
			"type" => "info",
			"message" => "Notificación informativa con más contexto.",
		]);
		$ui->notice([
			"type" => "success",
			"message" => "Cambios guardados correctamente.",
		]);
		$ui->notice([
			"type" => "warning",
			"message" => "Algunos campos no se pudieron validar.",
		]);
		$ui->notice([
			"type" => "danger",
			"message" => "Error crítico. Revisa la configuración del servidor.",
		]);
		pw_pg_section_end();
		pw_pg_section("Notices con título & dismissible");
		$ui->notice([
			"type" => "info",
			"title" => "Actualización disponible",
			"message" => "Nueva versión disponible.",
			"dismissible" => true,
		]);
		$ui->notice([
			"type" => "success",
			"title" => "¡Listo!",
			"message" => "El proceso se completó.",
			"dismissible" => true,
		]);
		$ui->notice([
			"type" => "danger",
			"title" => "Permiso denegado",
			"message" => "No tienes permisos.",
			"dismissible" => true,
		]);
		pw_pg_section_end();
		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">';
		echo "<div>";
		pw_pg_section("Spinner");
		pw_pg_row();
		$ui->spinner(["size" => "sm"]);
		$ui->spinner(["size" => "md"]);
		$ui->spinner(["size" => "lg"]);
		pw_pg_row_end();
		pw_pg_section_end();
		echo "</div><div>";
		pw_pg_section("Progress Bar");
		$ui->progress_bar([
			"value" => 25,
			"label" => "Cargando archivos",
			"show_value" => true,
		]);
		$ui->progress_bar([
			"value" => 60,
			"variant" => "success",
			"label" => "Tareas completadas",
			"show_value" => true,
		]);
		$ui->progress_bar([
			"value" => 80,
			"variant" => "warning",
			"size" => "md",
			"label" => "Espacio en disco",
			"show_value" => true,
		]);
		$ui->progress_bar([
			"value" => 95,
			"variant" => "danger",
			"size" => "lg",
			"label" => "Uso de memoria",
			"show_value" => true,
		]);
		pw_pg_section_end();
		echo "</div></div>";
		pw_pg_section("Skeleton (loading placeholders)");
		$ui->card([
			"title" => "Cargando...",
			"content" => function () use ($ui) {
				$ui->skeleton(["type" => "title", "width" => "40%"]);
				$ui->skeleton(["type" => "text", "lines" => 3]);
				echo '<div style="display:flex;gap:12px;margin-top:12px;">';
				$ui->skeleton(["type" => "avatar", "width" => "40px"]);
				echo '<div style="flex:1;">';
				$ui->skeleton(["type" => "text", "width" => "60%"]);
				$ui->skeleton(["type" => "text", "width" => "40%"]);
				echo "</div></div>";
			},
		]);
		pw_pg_section_end();
		pw_pg_section("Tooltip");
		pw_pg_row();
		$ui->tooltip([
			"text" => "Guardar todos los cambios",
			"trigger_html" =>
				'<button class="pw-bui-btn pw-bui-btn--primary pw-bui-btn--md">Hover → tooltip arriba</button>',
		]);
		$ui->tooltip([
			"text" => "Eliminar permanentemente",
			"position" => "bottom",
			"trigger_html" =>
				'<button class="pw-bui-btn pw-bui-btn--danger pw-bui-btn--md">Hover → tooltip abajo</button>',
		]);
		pw_pg_row_end();
		pw_pg_section_end();
	},
]);

// ── TAB 4: NAVEGACIÓN ────────────────────────────────────────────────────────
$ui->tab_panel([
	"slug" => "navigation",
	"content" => function () use ($ui) {
		pw_pg_section("Breadcrumbs");
		$ui->breadcrumbs([
			"items" => [
				["label" => "Inicio", "href" => "#"],
				["label" => "Configuración", "href" => "#"],
				["label" => "PW Playground"],
			],
		]);
		pw_pg_section_end();

		pw_pg_section("Side Nav (navegación lateral tipo WP Settings)");
		echo '<div style="display:grid;grid-template-columns:200px 1fr;border:1px solid var(--pw-color-border-default);border-radius:2px;overflow:hidden;">';
		echo '<nav class="pw-bui-sidenav" style="min-height:auto;position:static;">';
		$ui->side_nav([
			"items" => [
				["label" => "Conexión", "href" => "#", "active" => true],
				["label" => "Enlazar Proyectos", "href" => "#"],
				["separator" => true],
				["group" => "Avanzado"],
				["label" => "Logs", "href" => "#"],
				["label" => "Webhooks", "href" => "#"],
			],
		]);
		echo "</nav>";
		echo '<div style="padding:20px;">';
		$ui->heading(["text" => "Panel de contenido", "level" => 3]);
		$ui->paragraph([
			"text" =>
				'El side_nav funciona como nav lateral. Se activa automáticamente al usar la clave "sidenav" en render_page().',
			"variant" => "muted",
		]);
		echo "</div>";
		echo "</div>";
		pw_pg_section_end();

		pw_pg_section("Tabs anidados");
		$ui->card([
			"title" => "Contenido tabulado",
			"content" => function () use ($ui) {
				$ui->tabs([
					"tabs" => [
						[
							"slug" => "sa",
							"label" => "Resumen",
							"active" => true,
						],
						["slug" => "sb", "label" => "Detalles", "count" => 3],
						["slug" => "sc", "label" => "Historial"],
					],
				]);
				echo '<div style="padding-top:16px;">';
				$ui->tab_panel([
					"slug" => "sa",
					"active" => true,
					"content" => function () use ($ui) {
						$ui->paragraph([
							"text" =>
								"Panel Resumen. Los tabs soportan badge de contador.",
						]);
					},
				]);
				$ui->tab_panel([
					"slug" => "sb",
					"content" => function () use ($ui) {
						$ui->paragraph(["text" => "Panel Detalles (3 items)."]);
					},
				]);
				$ui->tab_panel([
					"slug" => "sc",
					"content" => function () use ($ui) {
						$ui->paragraph(["text" => "Panel Historial."]);
					},
				]);
				echo "</div>";
			},
		]);
		pw_pg_section_end();
		pw_pg_section("Paginación");
		$ui->pagination(["current" => 3, "total" => 12, "base_url" => "#"]);
		echo "<br>";
		$ui->pagination(["current" => 1, "total" => 5, "base_url" => "#"]);
		echo "<br>";
		$ui->pagination(["current" => 100, "total" => 100, "base_url" => "#"]);
		pw_pg_section_end();
	},
]);

// ── TAB 5: TIPOGRAFÍA & LAYOUT ───────────────────────────────────────────────
$ui->tab_panel([
	"slug" => "layout",
	"content" => function () use ($ui) {
		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">';
		echo "<div>";
		pw_pg_section("Headings");
		for ($l = 1; $l <= 6; $l++) {
			$ui->heading(["text" => "Heading $l — h$l", "level" => $l]);
		}
		pw_pg_section_end();
		pw_pg_section("Paragraphs");
		$ui->paragraph([
			"text" => "Default — texto principal con buena legibilidad.",
		]);
		$ui->paragraph([
			"text" => "Muted — texto secundario para descripciones.",
			"variant" => "muted",
		]);
		$ui->paragraph([
			"text" => "Small — etiquetas, hints, notas al pie.",
			"variant" => "small",
		]);
		pw_pg_section_end();
		pw_pg_section("Links");
		pw_pg_row();
		$ui->link(["label" => "Default", "href" => "#"]);
		$ui->link(["label" => "Muted", "href" => "#", "variant" => "muted"]);
		$ui->link(["label" => "Danger", "href" => "#", "variant" => "danger"]);
		$ui->link([
			"label" => "External ↗",
			"href" => "#",
			"target" => "_blank",
		]);
		pw_pg_row_end();
		pw_pg_section_end();
		echo "</div><div>";
		pw_pg_section("Cards");
		$ui->card([
			"title" => "Con header y footer",
			"description" => "Subtítulo descriptivo.",
			"content" => function () use ($ui) {
				$ui->paragraph(["text" => "Contenido con padding estándar."]);
			},
			"footer" => function () use ($ui) {
				$ui->button(["label" => "Acción", "size" => "sm"]);
				$ui->button([
					"label" => "Cancelar",
					"variant" => "ghost",
					"size" => "sm",
				]);
			},
		]);
		echo '<div style="margin-top:12px;">';
		$ui->card([
			"title" => "Card flush (sin padding)",
			"padded" => false,
			"content" => function () {
				foreach (["Ítem uno", "Ítem dos", "Ítem tres"] as $i => $item) {
					$b =
						$i > 0
							? "border-top:1px solid var(--pw-color-border-default);"
							: "";
					echo '<div style="padding:10px 16px;font-size:13px;color:var(--pw-color-fg-default);' .
						$b .
						'">' .
						esc_html($item) .
						"</div>";
				}
			},
		]);
		echo "</div>";
		pw_pg_section_end();
		pw_pg_section("Separator");
		$ui->paragraph(["text" => "Contenido arriba del separador."]);
		$ui->separator();
		$ui->paragraph(["text" => "Contenido debajo del separador."]);
		pw_pg_section_end();
		echo "</div></div>";
	},
]);
