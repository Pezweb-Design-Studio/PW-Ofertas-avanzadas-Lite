(function () {
	'use strict';
	var L = window.pwoaAdminSettings || {};
	var nonce = L.nonce || '';
	var s = L.strings || {};

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('pwoa-settings-form');
		if (!form) {
			return;
		}

		form.addEventListener('submit', async function (e) {
			e.preventDefault();

			var formData = new FormData(form);
			formData.append('action', 'pwoa_save_settings');
			formData.append('nonce', nonce);

			try {
				var res = await fetch(ajaxurl, { method: 'POST', body: formData });
				var data = await res.json();

				if (data.success) {
					var notice = document.createElement('div');
					notice.className = 'notice notice-success is-dismissible';
					notice.innerHTML = '<p>' + data.data.message + '</p>';
					var main = document.querySelector('.pw-bui-main');
					if (main) {
						main.insertBefore(notice, main.firstChild);
					}
					window.scrollTo({ top: 0, behavior: 'smooth' });
					setTimeout(function () {
						notice.remove();
					}, 3000);
				} else {
					alert((s.saveError || 'Error') + ': ' + (data.data || s.couldNotSave || 'Could not save'));
				}
			} catch (err) {
				alert(s.saveFailed || 'Could not save settings');
			}
		});
	});
})();
