/**
 * ARC Native front-end helpers.
 */
(function () {
	'use strict';

	function apiUrl(path) {
		return (arcNativeData.restUrl || '/wp-json/arc-native/v1/') + path;
	}

	function apiCall(path, method, data) {
		method = method || 'GET';
		return fetch(apiUrl(path), {
			method: method,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': arcNativeData.nonce || '',
			},
			body: method === 'GET' ? null : JSON.stringify(data || {}),
		}).then(function (res) {
			return res.json().then(function (json) {
				if (!res.ok) {
					throw new Error((json && (json.message || json.error)) || 'Request failed');
				}
				return json;
			});
		});
	}

	function updateStats() {
		var container = document.querySelector('[data-arc-native-stats]');
		if (!container) return;

		apiCall('stats', 'GET').then(function (res) {
			Object.keys(res).forEach(function (key) {
				var el = container.querySelector('[data-stat="' + key + '"]');
				if (el) el.textContent = res[key];
			});
		}).catch(function () {
			// Fail silently; dashboard keeps working.
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		updateStats();
	});
})();
