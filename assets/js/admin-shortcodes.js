(function () {
	'use strict';
	var L = window.pwoaAdminShortcodes || {};
	var s = L.strings || {};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.pwoa-copy-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var code = btn.dataset.code;
				var copyLabel = s.copied || 'Copied!';
				navigator.clipboard.writeText(code).then(function () {
					var orig = btn.textContent;
					btn.textContent = copyLabel;
					btn.style.background = 'var(--pw-color-success-subtle)';
					btn.style.borderColor = 'var(--pw-color-success-muted)';
					btn.style.color = 'var(--pw-color-success-fg)';
					setTimeout(function () {
						btn.textContent = orig;
						btn.style.background = '';
						btn.style.borderColor = '';
						btn.style.color = '';
					}, 1500);
				});
			});
		});

		var output = document.getElementById('gen-output');
		var copyBtn = document.getElementById('gen-copy-btn');
		var copyLbl = document.getElementById('gen-copy-label');
		var paginateChk = document.getElementById('gen-paginate');
		var perPageWrap = document.getElementById('gen-per_page-wrap');

		if (!output) {
			return;
		}

		var defaults = {
			limit: '12',
			columns: '4',
			orderby: 'date',
			order: 'DESC',
			show_badge: true,
			show_campaign_name: false,
			paginate: false,
			per_page: '12',
		};

		function getVal(id) {
			var el = document.getElementById(id);
			if (!el) {
				return null;
			}
			return el.type === 'checkbox' ? el.checked : el.value.trim();
		}

		function build() {
			var sc = '[pwoa_productos_oferta';
			var campaign_id = getVal('gen-campaign_id');
			if (campaign_id) {
				sc += ' campaign_id="' + campaign_id + '"';
			}
			var limit = getVal('gen-limit');
			if (limit && limit !== defaults.limit) {
				sc += ' limit="' + limit + '"';
			}
			var columns = getVal('gen-columns');
			if (columns && columns !== defaults.columns) {
				sc += ' columns="' + columns + '"';
			}
			var orderby = getVal('gen-orderby');
			if (orderby && orderby !== defaults.orderby) {
				sc += ' orderby="' + orderby + '"';
			}
			var order = getVal('gen-order');
			if (order && order !== defaults.order) {
				sc += ' order="' + order + '"';
			}
			var category = getVal('gen-category');
			if (category) {
				sc += ' category="' + category + '"';
			}
			var min_p = getVal('gen-min_price');
			if (min_p) {
				sc += ' min_price="' + min_p + '"';
			}
			var max_p = getVal('gen-max_price');
			if (max_p) {
				sc += ' max_price="' + max_p + '"';
			}
			if (!getVal('gen-show_badge')) {
				sc += ' show_badge="false"';
			}
			if (getVal('gen-show_campaign_name')) {
				sc += ' show_campaign_name="true"';
			}
			if (getVal('gen-paginate')) {
				sc += ' paginate="true"';
				var pp = getVal('gen-per_page');
				if (pp && pp !== defaults.per_page) {
					sc += ' per_page="' + pp + '"';
				}
			}
			return sc + ']';
		}

		function refresh() {
			if (output) {
				output.textContent = build();
			}
		}

		if (paginateChk) {
			paginateChk.addEventListener('change', function () {
				if (perPageWrap) {
					perPageWrap.style.display = paginateChk.checked ? 'block' : 'none';
				}
				refresh();
			});
		}

		var gen = document.getElementById('pwoa-generator');
		if (gen) {
			gen.querySelectorAll('input, select').forEach(function (el) {
				el.addEventListener('change', refresh);
				el.addEventListener('input', refresh);
			});
		}

		if (copyBtn) {
			copyBtn.addEventListener('click', function () {
				navigator.clipboard.writeText(output.textContent).then(function () {
					var copyVerb = s.copyVerb || 'Copy';
					var copiedMsg = s.copied || 'Copied!';
					copyLbl.textContent = copiedMsg;
					copyBtn.style.background = 'var(--pw-color-success-emphasis)';
					setTimeout(function () {
						copyLbl.textContent = copyVerb;
						copyBtn.style.background = '';
					}, 1500);
				});
			});
		}

		refresh();
	});
})();
