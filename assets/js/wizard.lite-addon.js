/**
 * Lite edition: block Pro-only strategies and show upgrade modal.
 */
(function () {
	'use strict';

	const IS_LITE = typeof pwoaData !== 'undefined' && pwoaData.edition === 'lite';
	const UPGRADE_URL =
		typeof pwoaData !== 'undefined' && pwoaData.upgradeUrl
			? pwoaData.upgradeUrl
			: '';

	function t(key, fallback) {
		try {
			const pack =
				typeof pwoaData !== 'undefined' && pwoaData.i18n ? pwoaData.i18n : {};
			const v = pack[key];
			if (v != null && String(v) !== '') return v;
		} catch (e) {}
		return fallback;
	}

	document.addEventListener(
		'click',
		function (e) {
			if (!IS_LITE) {
				return;
			}

			const t = e.target;

			if (t.closest('.strategy-card')) {
				const card = t.closest('.strategy-card');

				try {
					const strategyData = JSON.parse(card.dataset.strategy);

					if (strategyData.available === false) {
						e.preventDefault();
						e.stopPropagation();
						e.stopImmediatePropagation();

						PWOAWizard.showProModal(strategyData.name);
						return false;
					}
				} catch (err) {
					/* ignore malformed strategy payload */
				}
			}
		},
		true,
	);

	PWOAWizard.showProModal = function (featureName) {
		const modal = document.createElement('div');
		modal.className =
			'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
		modal.innerHTML =
			`
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-8">
            <div class="text-center">
                <div class="text-6xl mb-4">🔒</div>
                <h2 class="text-2xl font-bold mb-3">${t('proOnlyModalTitle', 'Pro-only feature')}</h2>
                <p class="text-gray-600 mb-6">
                    <strong>${featureName}</strong> ${t('proOnlyModalBody', 'is available in Pro with six advanced strategy types and full analytics.')}
                </p>
                <div class="space-y-3">
                    <a href="${UPGRADE_URL}" target="_blank" rel="noopener noreferrer"
                       class="block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition">
                        ${t('viewProVersion', 'View Pro version →')}
                    </a>
                    <button type="button" class="block w-full bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition close-pro-modal">
                        ${t('close', 'Close')}
                    </button>
                </div>
            </div>
        </div>
    `;

		document.body.appendChild(modal);

		modal.querySelector('.close-pro-modal').addEventListener('click', function () {
			modal.remove();
		});

		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				modal.remove();
			}
		});
	};

	const originalRenderStrategies = PWOAWizard.renderStrategies;

	PWOAWizard.renderStrategies = function (strategies) {
		if (!IS_LITE) {
			return originalRenderStrategies.call(this, strategies);
		}

		if (strategies.length === 0) {
			const html =
				`
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg p-12 text-center">
                <div class="text-6xl mb-4">✨</div>
                <h3 class="text-3xl font-bold mb-4">${t('proOnlyObjectiveTitle', 'Pro-only feature')}</h3>
                <p class="text-gray-700 text-lg mb-8">
                    ${t('proOnlyObjectiveBody', 'This objective is available in Pro.')}
                </p>
                <a href="${UPGRADE_URL}" target="_blank" rel="noopener noreferrer"
                   class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition">
                    ${t('upgradeToPro', 'Upgrade to Pro →')}
                </a>
            </div>
        `;
			document.getElementById('strategies-list').innerHTML = html;
			return;
		}

		const liteCount = strategies.filter(function (s) {
			return s.available !== false;
		}).length;
		const proCount = strategies.filter(function (s) {
			return s.available === false;
		}).length;

		let proBanner = '';
		if (proCount > 0) {
			const headTpl =
				proCount === 1
					? t('proBannerExclusiveOne', '%d Pro-only strategy')
					: t('proBannerExclusiveMany', '%d Pro-only strategies');
			const headLine = (headTpl + '').replace('%d', String(proCount));
			const liteWord =
				liteCount === 1
					? t('strategySingular', 'strategy')
					: t('strategyPlural', 'strategies');
			const liteLine = (t('proBannerLiteLine', 'You are viewing %1$d %2$s in Lite. ') + '')
				.replace('%1$d', String(liteCount))
				.replace('%2$s', liteWord);
			const advWord =
				proCount === 1
					? t('advancedSingular', 'advanced strategy')
					: t('advancedPlural', 'advanced strategies');
			const unlockLine = (t('proBannerUnlock', 'Upgrade to Pro to unlock %1$d more %2$s.') + '')
				.replace('%1$d', String(proCount))
				.replace('%2$s', advWord);
			proBanner =
				`
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-200 rounded-lg p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-purple-900 mb-2">
                            🔒 ${headLine}
                        </h3>
                        <p class="text-sm text-purple-700">
                            ${liteLine}
                            ${unlockLine}
                        </p>
                    </div>
                    <a href="${UPGRADE_URL}" target="_blank" rel="noopener noreferrer"
                       class="ml-4 bg-gradient-to-r from-blue-600 to-purple-600 !text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-purple-700 transition whitespace-nowrap">
                        ${t('viewPro', 'View Pro →')}
                    </a>
                </div>
            </div>
        `;
		}

		const html = strategies
			.map(function (s) {
				const isLocked = s.available === false;
				const lockedClass = isLocked ? 'locked' : '';
				const lockedBadge = isLocked ? '<span class="pro-badge">🔒 PRO</span>' : '';

				return (
					`
            <div class="strategy-card ${lockedClass} bg-white p-8 rounded-lg shadow mb-6 cursor-pointer hover:shadow-xl transition border-2 border-transparent hover:border-blue-500"
                 data-strategy='${JSON.stringify(s)}'>
                ${lockedBadge}
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-2xl font-bold">${s.name}</h3>
                    <span class="text-yellow-500 text-xl">${'★'.repeat(s.effectiveness)}</span>
                </div>
                <p class="text-gray-600 mb-6 leading-relaxed">${s.description}</p>
                <div class="bg-blue-50 p-4 rounded">
                    <strong class="text-blue-900">${t('whenToUse', 'When to use:')}</strong>
                    <span class="text-blue-800">${s.when_to_use}</span>
                </div>
            </div>
        `
				);
			})
			.join('');

		document.getElementById('strategies-list').innerHTML = proBanner + html;
	};
})();
