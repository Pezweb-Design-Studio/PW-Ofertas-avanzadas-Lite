(function () {
	'use strict';
	var cfg = typeof window.pwoaBadgeConfig === 'object' && window.pwoaBadgeConfig ? window.pwoaBadgeConfig : null;
	if (!cfg || !cfg.badges || typeof cfg.badges !== 'object') {
		return;
	}

	var badges = cfg.badges;
	var slugMap = cfg.slugMap && typeof cfg.slugMap === 'object' ? cfg.slugMap : {};

	function addBadges() {
		if (Object.keys(badges).length === 1) {
			var productId = Object.keys(badges)[0];
			var singleSelectors = [
				'.woocommerce-product-gallery__wrapper',
				'.woocommerce-product-gallery__image:first-child',
				'.wp-block-woocommerce-product-image-gallery',
				'div[data-block-name="woocommerce/product-image-gallery"]',
			];

			for (var s = 0; s < singleSelectors.length; s++) {
				var container = document.querySelector(singleSelectors[s]);
				if (container && !container.querySelector('.pwoa-discount-badge')) {
					container.style.position = 'relative';
					container.insertAdjacentHTML('beforeend', badges[productId]);
					return;
				}
			}
		}

		document.querySelectorAll('.product, [data-product-id], [data-wp-context]').forEach(function (item) {
			if (item.querySelector('.pwoa-discount-badge')) {
				return;
			}

			var productId = null;

			if (item.dataset.productId) {
				productId = item.dataset.productId;
			} else if (item.dataset.wpContext) {
				try {
					var context = JSON.parse(item.dataset.wpContext);
					productId = context.productId;
				} catch (e) { /* ignore */ }
			} else {
				var classes = item.className.split(' ');
				for (var c = 0; c < classes.length; c++) {
					if (classes[c].indexOf('post-') === 0) {
						productId = classes[c].replace('post-', '');
						break;
					}
				}
			}

			if (!productId || !badges[productId]) {
				return;
			}

			var selectors = [
				'.jet-woo-builder-archive-product-thumbnail',
				'.wc-block-components-product-image__inner-container',
				'a.woocommerce-LoopProduct-link',
				'.product-thumbnail',
				'a[href*="producto"]',
			];

			var container = null;
			for (var i = 0; i < selectors.length; i++) {
				container = item.querySelector(selectors[i]);
				if (container && !container.querySelector('.pwoa-discount-badge')) {
					break;
				}
				container = null;
			}

			if (!container) {
				return;
			}

			container.style.position = 'relative';
			container.insertAdjacentHTML('beforeend', badges[productId]);
		});

		if (Object.keys(badges).length > 0) {
			var classicRows = document.querySelectorAll('.cart_item');

			classicRows.forEach(function (row) {
				if (row.querySelector('.pwoa-discount-badge')) {
					return;
				}

				var link = row.querySelector('.product-name a, td.product-name a');
				if (!link) {
					return;
				}

				var pid = null;
				var href = link.getAttribute('href');

				for (var id in badges) {
					if (
						href.indexOf('/' + id + '/') !== -1 ||
						href.indexOf('?p=' + id) !== -1 ||
						href.indexOf('post=' + id) !== -1
					) {
						pid = id;
						break;
					}
				}

				if (!pid || !badges[pid]) {
					return;
				}

				var thumbnail = row.querySelector('.product-thumbnail, td.product-thumbnail');

				if (thumbnail && !thumbnail.querySelector('.pwoa-discount-badge')) {
					thumbnail.style.position = 'relative';
					thumbnail.insertAdjacentHTML('beforeend', badges[pid]);
				}
			});

			var blockRows = document.querySelectorAll('.wc-block-cart-items__row');

			blockRows.forEach(function (row, index) {
				if (row.querySelector('.pwoa-discount-badge')) {
					return;
				}

				var productId = null;

				var thumbLink = row.querySelector('.wc-block-cart-item__image a');
				if (thumbLink && thumbLink.dataset.productId) {
					productId = thumbLink.dataset.productId;
				}

				if (!productId && row.dataset) {
					for (var attr in row.dataset) {
						if (!Object.prototype.hasOwnProperty.call(row.dataset, attr)) {
							continue;
						}
						try {
							var data = JSON.parse(row.dataset[attr]);
							if (data.id || data.productId || data.product_id) {
								productId = data.id || data.productId || data.product_id;
								break;
							}
						} catch (e2) { /* ignore */ }
					}
				}

				if (!productId) {
					var nameLink = row.querySelector('.wc-block-components-product-name');
					if (nameLink) {
						var href2 = nameLink.getAttribute('href');
						var slug = href2.split('/').filter(Boolean).pop();

						if (slugMap[slug]) {
							productId = slugMap[slug];
						}
					}
				}

				if (!productId) {
					var badgeIds = Object.keys(badges);
					if (badgeIds[index]) {
						productId = badgeIds[index];
					}
				}

				if (!productId || !badges[productId]) {
					return;
				}

				var thumb = row.querySelector('.wc-block-cart-item__image');

				if (thumb && !thumb.querySelector('.pwoa-discount-badge')) {
					thumb.style.position = 'relative';
					thumb.insertAdjacentHTML('beforeend', badges[productId]);
				}
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', addBadges);
	} else {
		addBadges();
	}

	var observer = new MutationObserver(function () {
		requestAnimationFrame(addBadges);
	});

	observer.observe(document.body, {
		childList: true,
		subtree: true,
	});

	setTimeout(function () {
		observer.disconnect();
	}, 5000);
})();
