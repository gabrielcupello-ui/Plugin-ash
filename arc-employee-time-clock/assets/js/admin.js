(function ($) {
	'use strict';

	$(document).ready(function () {
		function doAdmin(action, entryId, extra, callback) {
			const data = {
				action: 'arc_etc_admin_action',
				do: action,
				entry_id: entryId,
				nonce: arcEtcAdmin.nonce,
			};
			if (extra) {
				$.extend(data, extra);
			}
			$.post(arcEtcAdmin.ajaxUrl, data, function (response) {
				if (response.success) {
					callback(null, response.data);
				} else {
					callback(response.data && response.data.message ? response.data.message : 'Error');
				}
			}, 'json').fail(function () {
				callback('Request failed');
			});
		}

		const approvedBadge = '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700">Approved</span>';

		$('.arc-approve-entry').on('click', function () {
			const btn = $(this);
			const entryId = btn.data('id');
			doAdmin('approve', entryId, {}, function (err) {
				if (err) {
					alert(err);
					return;
				}
				btn.closest('tr').find('.rounded-full').first().replaceWith(approvedBadge);
				btn.remove();
			});
		});

		$('.arc-delete-entry').on('click', function () {
			const btn = $(this);
			const entryId = btn.data('id');
			if (!confirm('Delete this entry?')) {
				return;
			}
			doAdmin('delete', entryId, {}, function (err) {
				if (err) {
					alert(err);
					return;
				}
				btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
			});
		});

		$(document).on('change', '.arc-entry-type', function () {
			const select = $(this);
			const entryId = select.closest('tr').data('entry');
			doAdmin('update_type', entryId, { entry_type: select.val() }, function (err) {
				if (err) {
					alert(err);
				}
			});
		});
	});

})(jQuery);
