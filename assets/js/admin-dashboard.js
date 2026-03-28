(function () {
	'use strict';
	var L = window.pwoaAdminDashboard || {};
	var nonce = L.nonce || '';
	var newCampaignUrl = L.newCampaignUrl || '';
	var s = L.strings || {};

	document.querySelectorAll('.toggle-campaign').forEach(function (input) {
		input.addEventListener('change', async function () {
			var campaignId = this.dataset.campaignId;
			var active = this.checked ? 1 : 0;
			var label = this.parentElement.querySelector('.pwoa-toggle-label');
			var track = this.parentElement.querySelector('.pwoa-toggle-track');
			var dot = track ? track.querySelector('span') : null;

			if (label) {
				label.textContent = active ? (s.active || 'Active') : (s.paused || 'Paused');
				label.classList.remove('text-green-600', 'text-gray-400');
				label.classList.add(active ? 'text-green-600' : 'text-gray-400');
			}
			if (track) {
				track.classList.remove('bg-green-500', 'bg-gray-300');
				track.classList.add(active ? 'bg-green-500' : 'bg-gray-300');
			}
			if (dot) {
				dot.classList.remove('left-0.5', 'right-0.5');
				dot.classList.add(active ? 'right-0.5' : 'left-0.5');
			}

			try {
				var res = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_toggle_campaign',
						campaign_id: campaignId,
						active: String(active),
						nonce: nonce,
					}),
				});
				var data = await res.json();
				if (!data.success) {
					throw new Error(data.data || s.unknownError || 'Unknown error');
				}
			} catch (err) {
				this.checked = !this.checked;
				alert((s.updateCampaignError || 'Could not update campaign') + ': ' + err.message);
			}
		});
	});

	document.querySelectorAll('.btn-edit').forEach(function (btn) {
		btn.addEventListener('click', function () {
			window.location.href = newCampaignUrl + '&edit=' + this.dataset.campaignId;
		});
	});

	document.querySelectorAll('.btn-delete').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			var campaignId = this.dataset.campaignId;
			var msg =
				(s.confirmDeletePrefix || 'Are you sure you want to delete the campaign') +
				' "' +
				this.dataset.campaignName +
				'"?';
			if (this.dataset.hasStats === '1') {
				msg += '\n\n' + (s.deleteHasStatsNote || 'This campaign has associated statistics.');
			}
			if (!confirm(msg)) {
				return;
			}

			this.disabled = true;
			this.classList.add('opacity-40');
			try {
				var res = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_delete_campaign',
						campaign_id: campaignId,
						nonce: nonce,
					}),
				});
				var data = await res.json();
				if (!data.success) {
					throw new Error(data.data || s.deleteError || 'Could not delete');
				}
				var row = this.closest('tr');
				if (row) {
					row.classList.add('transition-opacity', 'duration-300');
					requestAnimationFrame(function () {
						row.classList.add('opacity-0');
					});
				}
				setTimeout(function () {
					window.location.reload();
				}, 300);
			} catch (err) {
				this.disabled = false;
				this.classList.remove('opacity-40');
				alert((s.errorPrefix || 'Error') + ': ' + err.message);
			}
		});
	});

	document.querySelectorAll('.btn-reset').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			if (
				!confirm(
					(s.confirmResetPrefix || 'Reset units-sold counter for') +
						' "' +
						this.dataset.campaignName +
						'"?\n\n' +
						(s.confirmResetSuffix || 'This will set the counter to zero.'),
				)
			) {
				return;
			}
			this.disabled = true;
			this.classList.add('opacity-40');
			try {
				var res = await fetch(ajaxurl, {
					method: 'POST',
					body: new URLSearchParams({
						action: 'pwoa_reset_units_sold',
						campaign_id: this.dataset.campaignId,
						nonce: nonce,
					}),
				});
				var data = await res.json();
				if (!data.success) {
					throw new Error(data.data || s.resetError || 'Error');
				}
				alert('✓ ' + data.data.message);
				window.location.reload();
			} catch (err) {
				this.disabled = false;
				this.classList.remove('opacity-40');
				alert((s.errorPrefix || 'Error') + ': ' + err.message);
			}
		});
	});

	(function () {
		var helpBtn = document.getElementById('help-button');
		var modal = document.getElementById('help-modal');
		if (!helpBtn || !modal) {
			return;
		}

		helpBtn.addEventListener('click', function () {
			modal.classList.remove('hidden');
		});
		document.getElementById('close-help')?.addEventListener('click', function () {
			modal.classList.add('hidden');
		});
		document.getElementById('close-help-btn')?.addEventListener('click', function () {
			modal.classList.add('hidden');
		});
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				modal.classList.add('hidden');
			}
		});
	})();
})();
