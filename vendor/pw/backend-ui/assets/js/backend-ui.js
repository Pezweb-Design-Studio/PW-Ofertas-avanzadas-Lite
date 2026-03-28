// assets/js/backend-ui.js

/**
 * pw/backend-ui — Interactive behaviors for the PW design system.
 *
 * Handles:
 *   - Theme toggle (dark/light) with localStorage persistence
 *   - Tab / UnderlineNav navigation (click + keyboard)
 *   - Segmented control
 *   - Toggle switch state
 *   - Dismissible notices
 *   - Tooltip show/hide
 *
 * No dependencies. Vanilla JS. Scoped to #pw-backend-ui-app.
 */

(function () {
	"use strict";

	const SCOPE = "#pw-backend-ui-app";
	const THEME_KEY = "pw-bui-theme";
	const THEME_DEFAULT = "dark";

	// =========================================================================
	// HELPERS
	// =========================================================================

	function getRoot() {
		return document.querySelector(SCOPE);
	}

	function $$(selector) {
		const root = getRoot();
		return root ? Array.from(root.querySelectorAll(selector)) : [];
	}

	function delegate(event, selector, handler) {
		const root = getRoot();
		if (!root) return;
		root.addEventListener(event, function (e) {
			const target = e.target.closest(selector);
			if (target && root.contains(target)) {
				handler.call(target, e);
			}
		});
	}

	// =========================================================================
	// THEME
	// =========================================================================

	function getTheme() {
		try {
			return localStorage.getItem(THEME_KEY) || THEME_DEFAULT;
		} catch (e) {
			return THEME_DEFAULT;
		}
	}

	function setTheme(theme) {
		const root = getRoot();
		if (!root) return;

		root.setAttribute("data-pw-theme", theme);

		try {
			localStorage.setItem(THEME_KEY, theme);
		} catch (e) {
			/* silently fail in private browsing */
		}

		document.dispatchEvent(
			new CustomEvent("pw-bui:theme-changed", {
				detail: { theme: theme },
			}),
		);
	}

	function initTheme() {
		const root = getRoot();
		if (!root) return;

		// Apply persisted theme immediately
		const saved = getTheme();
		root.setAttribute("data-pw-theme", saved);

		// Wire the toggle button
		delegate("click", ".pw-bui-theme-toggle", function () {
			const current = getRoot().getAttribute("data-pw-theme") || THEME_DEFAULT;
			setTheme(current === "dark" ? "light" : "dark");
		});
	}

	// =========================================================================
	// TABS (UnderlineNav)
	// =========================================================================

	function initTabs() {
		delegate("click", "[data-pw-tab]", function () {
			const slug = this.getAttribute("data-pw-tab");
			const tabNav = this.closest('[role="tablist"]');
			if (!tabNav) return;

			// Deactivate all in same nav
			tabNav.querySelectorAll("[data-pw-tab]").forEach(function (tab) {
				tab.classList.remove("pw-bui-tab--active");
				tab.setAttribute("aria-selected", "false");
			});

			// Activate clicked
			this.classList.add("pw-bui-tab--active");
			this.setAttribute("aria-selected", "true");

			// Show/hide panels
			$$("[data-pw-tab-panel]").forEach(function (panel) {
				const isTarget = panel.getAttribute("data-pw-tab-panel") === slug;
				panel.hidden = !isTarget;
				panel.setAttribute("aria-hidden", String(!isTarget));
			});

			document.dispatchEvent(
				new CustomEvent("pw-bui:tab-changed", {
					detail: { slug: slug },
				}),
			);
		});

		// Keyboard: left/right arrows
		delegate("keydown", "[data-pw-tab]", function (e) {
			if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
			e.preventDefault();

			const tabs = Array.from(
				this.closest('[role="tablist"]').querySelectorAll("[data-pw-tab]"),
			);
			const idx = tabs.indexOf(this);
			const nextIdx =
				e.key === "ArrowRight"
					? (idx + 1) % tabs.length
					: (idx - 1 + tabs.length) % tabs.length;

			tabs[nextIdx].focus();
			tabs[nextIdx].click();
		});
	}

	// =========================================================================
	// SEGMENTED CONTROL
	// =========================================================================

	function initSegmented() {
		delegate("click", "[data-pw-segment]", function () {
			const value = this.getAttribute("data-pw-segment");
			const control = this.closest("[data-pw-segmented]");
			if (!control) return;

			control.querySelectorAll("[data-pw-segment]").forEach(function (btn) {
				btn.classList.remove("pw-bui-segmented__btn--active");
				btn.setAttribute("aria-selected", "false");
			});

			this.classList.add("pw-bui-segmented__btn--active");
			this.setAttribute("aria-selected", "true");

			// Update hidden input if present
			const input = control.querySelector('input[type="hidden"]');
			if (input) input.value = value;

			document.dispatchEvent(
				new CustomEvent("pw-bui:segment-changed", {
					detail: {
						name: control.getAttribute("data-pw-segmented"),
						value: value,
					},
				}),
			);
		});
	}

	// =========================================================================
	// TOGGLE SWITCH
	// =========================================================================

	function initToggles() {
		delegate("click", ".pw-bui-toggle", function () {
			if (this.hasAttribute("disabled")) return;

			const isChecked = this.getAttribute("aria-checked") === "true";
			const newState = !isChecked;
			const name = this.getAttribute("data-name");
			const value = this.getAttribute("data-value");

			this.setAttribute("aria-checked", String(newState));

			// Update hidden input
			const input = this.closest(".pw-bui-toggle-wrap")
				? this.closest(".pw-bui-toggle-wrap").querySelector(
						'input[type="hidden"]',
					)
				: this.parentElement.querySelector('input[name="' + name + '"]');

			if (input) input.value = newState ? value || "1" : "";

			document.dispatchEvent(
				new CustomEvent("pw-bui:toggle-changed", {
					detail: { name: name, checked: newState, value: value },
				}),
			);
		});
	}

	// =========================================================================
	// DISMISSIBLE NOTICES
	// =========================================================================

	function initDismissible() {
		delegate("click", ".pw-bui-notice__dismiss", function () {
			const notice = this.closest("[data-pw-dismissible]");
			if (!notice) return;

			notice.classList.add("pw-bui-dismissing");
			setTimeout(function () {
				notice.remove();
			}, 220);
		});
	}

	// =========================================================================
	// TOOLTIP (keyboard / focus fallback — hover handled by CSS)
	// =========================================================================

	function initTooltips() {
		delegate("focusin", "[data-pw-tooltip]", function () {
			let tip = this.querySelector(".pw-bui-tooltip");
			if (!tip) {
				tip = document.createElement("span");
				tip.className = "pw-bui-tooltip";
				tip.textContent = this.getAttribute("data-pw-tooltip");
				this.appendChild(tip);
			}
			tip.classList.add("pw-bui-tooltip--visible");
		});

		delegate("focusout", "[data-pw-tooltip]", function () {
			const tip = this.querySelector(".pw-bui-tooltip");
			if (tip) tip.classList.remove("pw-bui-tooltip--visible");
		});
	}

	// =========================================================================
	// WIZARD (Stepper multi-step forms)
	// =========================================================================
	//
	// Estructura HTML esperada:
	//
	//   [data-pw-stepper]          → el componente stepper (indicador visual)
	//     [data-pw-step="slug"]    → item de paso individual
	//
	//   [data-pw-wizard]           → el form wrapper
	//     [data-pw-wizard-step="slug"]       → panel de contenido del paso
	//     [data-pw-wizard-next]              → botón avanzar
	//     [data-pw-wizard-prev]              → botón retroceder
	//     [data-pw-wizard-submit]            → botón submit (solo en último paso)
	//
	// Validación automática:
	//   - Antes de avanzar, se validan todos los [required] visibles del paso actual.
	//   - Si falla, se muestra un mensaje de error nativo del browser (reportValidity).
	//   - Hacia atrás no requiere validación.
	//
	// Evento disparado al cambiar de paso:
	//   pw-bui:wizard-step-changed → { detail: { from, to, index } }

	function initWizard() {
		const wizards = document.querySelectorAll("[data-pw-wizard]");
		if (!wizards.length) return;

		wizards.forEach(function (wizard) {
			const steps = Array.from(
				wizard.querySelectorAll("[data-pw-wizard-step]"),
			);
			const stepper = wizard.closest(".pw-bui-page-wrapper")
				? wizard
						.closest(".pw-bui-page-wrapper")
						.querySelector("[data-pw-stepper]")
				: document.querySelector("[data-pw-stepper]");

			let currentIndex = 0;

			// Muestra el paso en el índice dado, oculta el resto.
			function goTo(targetIndex) {
				if (targetIndex < 0 || targetIndex >= steps.length) return;

				const fromSlug = steps[currentIndex].getAttribute(
					"data-pw-wizard-step",
				);
				const toSlug = steps[targetIndex].getAttribute("data-pw-wizard-step");

				// Ocultar todos los paneles
				steps.forEach(function (panel) {
					panel.hidden = true;
				});

				// Mostrar el target
				steps[targetIndex].hidden = false;
				currentIndex = targetIndex;

				// Actualizar botones de navegación
				updateNavButtons();

				// Sincronizar stepper visual
				if (stepper) {
					syncStepper(stepper, steps, currentIndex);
				}

				document.dispatchEvent(
					new CustomEvent("pw-bui:wizard-step-changed", {
						detail: { from: fromSlug, to: toSlug, index: currentIndex },
					}),
				);
			}

			// Valida los campos required del paso actual.
			function validateCurrentStep() {
				const currentPanel = steps[currentIndex];
				const fields = Array.from(
					currentPanel.querySelectorAll("[required]"),
				).filter(function (el) {
					// Solo campos visibles y no deshabilitados
					return !el.disabled && el.offsetParent !== null;
				});

				for (const field of fields) {
					if (!field.checkValidity()) {
						field.reportValidity();
						return false;
					}
				}

				// Validación extra: al menos un checkbox si el step lo requiere
				const checkGroup = currentPanel.querySelector(
					"[data-pw-wizard-require-check]",
				);
				if (checkGroup) {
					const checked = checkGroup.querySelectorAll(
						'input[type="checkbox"]:checked',
					);
					if (!checked.length) {
						// Mostrar error personalizado dentro del grupo
						let errEl = checkGroup.querySelector(".pw-bui-wizard-check-error");
						if (!errEl) {
							errEl = document.createElement("p");
							errEl.className = "pw-bui-wizard-check-error";
							errEl.style.cssText =
								"color:var(--pw-color-danger-fg);font-size:13px;margin:8px 0 0;";
							checkGroup.appendChild(errEl);
						}
						errEl.textContent =
							checkGroup.getAttribute("data-pw-wizard-require-check") ||
							"Debes seleccionar al menos un ítem.";
						return false;
					} else {
						// Limpiar error si existe
						const errEl = checkGroup.querySelector(
							".pw-bui-wizard-check-error",
						);
						if (errEl) errEl.remove();
					}
				}

				return true;
			}

			// Muestra / oculta botones según el paso actual.
			function updateNavButtons() {
				const btnPrev = wizard.querySelector("[data-pw-wizard-prev]");
				const btnNext = wizard.querySelector("[data-pw-wizard-next]");
				const btnSubmit = wizard.querySelector("[data-pw-wizard-submit]");
				const isLast = currentIndex === steps.length - 1;
				const isFirst = currentIndex === 0;

				if (btnPrev) btnPrev.style.display = isFirst ? "none" : "";
				if (btnNext) btnNext.style.display = isLast ? "none" : "";
				if (btnSubmit) btnSubmit.style.display = isLast ? "" : "none";
			}

			// Avanzar
			wizard.addEventListener("click", function (e) {
				if (!e.target.closest("[data-pw-wizard-next]")) return;
				e.preventDefault();
				if (validateCurrentStep()) {
					goTo(currentIndex + 1);
				}
			});

			// Retroceder
			wizard.addEventListener("click", function (e) {
				if (!e.target.closest("[data-pw-wizard-prev]")) return;
				e.preventDefault();
				goTo(currentIndex - 1);
			});

			// Init: mostrar solo el primer paso
			goTo(0);
		});
	}

	// Sincroniza el stepper visual con el paso actual.
	function syncStepper(stepper, steps, currentIndex) {
		const items = Array.from(stepper.querySelectorAll("[data-pw-step]"));

		items.forEach(function (item, i) {
			item.classList.remove(
				"pw-bui-stepper__item--active",
				"pw-bui-stepper__item--done",
				"pw-bui-stepper__item--pending",
			);

			let state;
			if (i < currentIndex) state = "done";
			else if (i === currentIndex) state = "active";
			else state = "pending";

			item.classList.add("pw-bui-stepper__item--" + state);

			// Swap número por checkmark cuando done
			const indicator = item.querySelector(".pw-bui-stepper__indicator");
			if (!indicator) return;

			if (state === "done") {
				indicator.innerHTML =
					'<span class="pw-bui-stepper__icon" aria-hidden="true">' +
					'<svg width="14" height="14" viewBox="0 0 16 16" fill="none">' +
					'<path d="M2 8l4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
					"</svg></span>";
			} else {
				const num = i + 1;
				indicator.innerHTML =
					'<span class="pw-bui-stepper__number" aria-hidden="true">' +
					num +
					"</span>";
			}
		});
	}

	// =========================================================================

	// =========================================================================
	// INIT
	// =========================================================================

	function init() {
		initTheme();
		initTabs();
		initSegmented();
		initToggles();
		initDismissible();
		initTooltips();
		initWizard();

		document.dispatchEvent(new CustomEvent("pw-bui:ready"));
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
