(function () {
	'use strict';
	var L = window.pwoaAdminDashboard || {};
	var nonce = L.nonce || '';
	var newCampaignUrl = L.newCampaignUrl || '';
	var s = L.strings || {};

	document.querySelectorAll('.toggle-campaign').forEach(function (toggle) {
		toggle.addEventListener('change', async function () {
			var campaignId = this.dataset.campaignId;
			var active = this.checked ? 1 : 0;
			var statusText = this.nextElementSibling && this.nextElementSibling.nextElementSibling;

			if (statusText) {
				statusText.textContent = active ? (s.active || 'Active') : (s.paused || 'Paused');
				statusText.classList.toggle('text-green-700', !!active);
				statusText.classList.toggle('text-gray-700', !active);
			}

			try {
				var response = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_toggle_campaign',
						campaign_id: campaignId,
						active: String(active),
						nonce: nonce,
					}),
				});

				var data = await response.json();

				if (!data.success) {
					throw new Error(data.data || s.unknownError || 'Unknown error');
				}
			} catch (error) {
				this.checked = !this.checked;
				if (statusText) {
					statusText.textContent = !active ? (s.active || 'Active') : (s.paused || 'Paused');
					statusText.classList.toggle('text-green-700', !active);
					statusText.classList.toggle('text-gray-700', active);
				}
				alert((s.updateCampaignError || 'Could not update campaign') + ': ' + error.message);
			}
		});
	});

	document.querySelectorAll('.btn-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var campaignId = this.dataset.campaignId;
			window.location.href = newCampaignUrl + '&edit=' + campaignId;
		});
	});

	document.querySelectorAll('.btn-delete').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			var campaignId = this.dataset.campaignId;
			var campaignName = this.dataset.campaignName;
			var hasStats = this.dataset.hasStats === '1';

			var confirmMessage =
				(s.confirmDeletePrefix || 'Are you sure you want to delete the campaign') +
				' "' +
				campaignName +
				'"?';

			if (hasStats) {
				confirmMessage +=
					'\n\n' +
					(s.deleteHasStatsNoteLite ||
						'This campaign has associated statistics. Historical data is kept for reports.');
			}

			if (!confirm(confirmMessage)) {
				return;
			}

			this.disabled = true;
			this.style.opacity = '0.5';

			try {
				var response = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_delete_campaign',
						campaign_id: campaignId,
						nonce: nonce,
					}),
				});

				var data = await response.json();

				if (!data.success) {
					throw new Error(data.data || s.deleteError || 'Could not delete');
				}

				var row = this.closest('.px-6');
				row.style.transition = 'opacity 0.3s, transform 0.3s';
				row.style.opacity = '0';
				row.style.transform = 'translateX(20px)';

				setTimeout(function () {
					window.location.reload();
				}, 300);
			} catch (error) {
				this.disabled = false;
				this.style.opacity = '1';
				alert((s.errorPrefix || 'Error') + ': ' + error.message);
			}
		});
	});

	document.querySelectorAll('.btn-reset').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			var campaignId = this.dataset.campaignId;
			var campaignName = this.dataset.campaignName;

			if (
				!confirm(
					(s.confirmResetPrefix || 'Reset units-sold counter for') +
						' "' +
						campaignName +
						'"?\n\n' +
						(s.confirmResetSuffixLite ||
							'This will set the counter for all units sold to zero.'),
				)
			) {
				return;
			}

			this.disabled = true;
			this.style.opacity = '0.5';

			try {
				var response = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_reset_units_sold',
						campaign_id: campaignId,
						nonce: nonce,
					}),
				});

				var data = await response.json();

				if (!data.success) {
					throw new Error(data.data || s.resetError || 'Could not reset');
				}

				alert('✓ ' + data.data.message);
				window.location.reload();
			} catch (error) {
				this.disabled = false;
				this.style.opacity = '1';
				alert((s.errorPrefix || 'Error') + ': ' + error.message);
			}
		});
	});
})();
