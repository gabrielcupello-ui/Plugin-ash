(function ($) {
	'use strict';

	const wrap = document.querySelector('.arc-etc-wrap');
	if (!wrap) {
		return;
	}

	const liveTimeEl = document.getElementById('arc-etc-live-time');
	const liveDateEl = document.getElementById('arc-etc-live-date');
	const timerEl = document.getElementById('arc-etc-timer');
	const statusTextEl = document.getElementById('arc-etc-status-text');
	const dotEl = document.getElementById('arc-etc-dot');
	const msgEl = document.getElementById('arc-etc-message');

	const inFields = document.getElementById('arc-etc-in-fields');
	const outFields = document.getElementById('arc-etc-out-fields');
	const recentWrap = document.getElementById('arc-etc-recent');
	const recentChips = document.getElementById('arc-etc-recent-chips');
	const todayBlocks = document.getElementById('arc-etc-today-blocks');
	const todayBody = document.getElementById('arc-etc-today-body');

	const clientEl = document.getElementById('arc-etc-client');
	const activityEl = document.getElementById('arc-etc-activity');
	const projectEl = document.getElementById('arc-etc-project');
	const taskEl = document.getElementById('arc-etc-task');
	const tagsEl = document.getElementById('arc-etc-tags');
	const billableEl = document.getElementById('arc-etc-billable');
	const notesEl = document.getElementById('arc-etc-notes');
	const lunchStartEl = document.getElementById('arc-etc-lunch-start');
	const lunchEndEl = document.getElementById('arc-etc-lunch-end');

	const btnClockIn = document.getElementById('arc-etc-clockin');
	const btnPause = document.getElementById('arc-etc-pause');
	const btnResume = document.getElementById('arc-etc-resume');
	const btnClockOut = document.getElementById('arc-etc-clockout');

	if (!liveTimeEl || !liveDateEl) {
		return;
	}

	let state = wrap.dataset.status || 'out';
	let since = null;
	let timerInterval = null;
	let bootData = null;

	function showMessage(text, isError) {
		msgEl.textContent = text;
		msgEl.classList.remove('hidden');
		if (isError) {
			msgEl.className = 'block rounded-xl p-3 text-sm bg-rose-50 text-rose-700 border border-rose-200';
		} else {
			msgEl.className = 'block rounded-xl p-3 text-sm bg-emerald-50 text-emerald-700 border border-emerald-200';
		}
	}

	function hideMessage() {
		msgEl.classList.add('hidden');
	}

	function getLocation(callback) {
		if (!navigator.geolocation) {
			callback(null);
			return;
		}
		navigator.geolocation.getCurrentPosition(
			function (pos) {
				callback({ lat: pos.coords.latitude, lng: pos.coords.longitude });
			},
			function () {
				callback(null);
			}
		);
	}

	function send(action, data, callback) {
		data = data || {};
		data.action = 'arc_etc_action';
		data.do = action;
		data.nonce = arcEtc.nonce;

		getLocation(function (loc) {
			if (loc) {
				data.location = JSON.stringify(loc);
			}
			$.post(arcEtc.ajaxUrl, data, function (response) {
				if (response.success) {
					callback(null, response.data);
				} else {
					callback(response.data && response.data.message ? response.data.message : 'Error');
				}
			}, 'json').fail(function () {
				callback('Request failed');
			});
		});
	}

	function formatDuration(totalSeconds) {
		const h = Math.floor(totalSeconds / 3600);
		const m = Math.floor((totalSeconds % 3600) / 60);
		const s = totalSeconds % 60;
		return (
			String(h).padStart(2, '0') +
			':' +
			String(m).padStart(2, '0') +
			':' +
			String(s).padStart(2, '0')
		);
	}

	function formatMinutes(min) {
		const m = Math.max(0, parseInt(min, 10) || 0);
		const h = Math.floor(m / 60);
		const rm = m % 60;
		return h + 'h ' + String(rm).padStart(2, '0') + 'm';
	}

	function updateTimer() {
		const now = new Date();
		liveTimeEl.textContent = now.toLocaleTimeString();
		liveDateEl.textContent = now.toLocaleDateString(undefined, {
			weekday: 'long',
			year: 'numeric',
			month: 'long',
			day: 'numeric',
		});

		if (state === 'paused') {
			return;
		}
		if (!since) {
			timerEl.textContent = '00:00:00';
			return;
		}

		const elapsed = Math.floor((now.getTime() - since) / 1000);
		timerEl.textContent = formatDuration(elapsed);
	}

	function populateSelect(el, options, selected, placeholder) {
		if (!el) return;
		const current = selected || el.value;
		el.innerHTML = '<option value="">' + (placeholder || '') + '</option>';
		options.forEach(function (opt) {
			const option = document.createElement('option');
			option.value = opt;
			option.textContent = opt;
			if (opt === current) option.selected = true;
			el.appendChild(option);
		});
	}

	function renderRecent(tasks) {
		if (!recentChips || !recentWrap) return;
		recentChips.innerHTML = '';
		if (!tasks || !tasks.length) {
			recentWrap.classList.add('hidden');
			return;
		}
		recentWrap.classList.remove('hidden');
		tasks.forEach(function (t) {
			const chip = document.createElement('button');
			chip.type = 'button';
			chip.className = 'px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition';
			chip.textContent = (t.client || '') + ' / ' + (t.activity || '');
			chip.title = t.notes || '';
			chip.addEventListener('click', function () {
				if (clientEl) clientEl.value = t.client || '';
				if (activityEl) activityEl.value = t.activity || '';
				if (notesEl) notesEl.value = t.notes || '';
			});
			recentChips.appendChild(chip);
		});
	}

	function renderTodayBlocks(blocks) {
		if (!todayBody || !todayBlocks) return;
		todayBody.innerHTML = '';
		if (!blocks || !blocks.length) {
			todayBlocks.classList.add('hidden');
			return;
		}
		todayBlocks.classList.remove('hidden');
		blocks.forEach(function (b) {
			const div = document.createElement('div');
			div.className = 'flex justify-between text-sm border-b border-slate-200 pb-1 last:border-0';
			const start = b.clock_in ? new Date(b.clock_in).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '-';
			const end = b.clock_out ? new Date(b.clock_out).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : (b.status === 'open' ? '...' : '-');
			const meta = [b.client, b.activity, b.project, b.task].filter(Boolean).join(' / ');
			div.innerHTML = '<span class="text-slate-700">' + start + ' - ' + end + '</span><span class="font-medium text-slate-900">' + formatMinutes(b.total_minutes || 0) + '</span>' +
				'<div class="w-full text-xs text-slate-500 mt-1">' + escHtml(meta) + '</div>';
			todayBody.appendChild(div);
		});
	}

	function escHtml(str) {
		const d = document.createElement('div');
		d.textContent = str;
		return d.innerHTML;
	}

	function setUI(newState, data) {
		state = newState;
		wrap.dataset.status = state;
		bootData = data || bootData;

		hideMessage();

		if (data && data.entry_id) {
			wrap.dataset.entry = data.entry_id;
		}

		if (state === 'paused' && data && data.elapsedMin) {
			since = null;
			if (timerEl) timerEl.textContent = formatDuration((data.elapsedMin || 0) * 60);
		} else if (data && data.since) {
			since = data.since * 1000;
		} else if (state !== 'out') {
			since = Date.now();
		} else {
			since = null;
		}

		if (inFields) inFields.style.display = state === 'out' ? 'block' : 'none';
		if (outFields) outFields.style.display = state === 'out' ? 'none' : 'block';

		if (btnClockIn) btnClockIn.style.display = state === 'out' ? 'inline-flex' : 'none';
		if (btnPause) btnPause.style.display = state === 'in' ? 'inline-flex' : 'none';
		if (btnResume) btnResume.style.display = state === 'paused' ? 'inline-flex' : 'none';
		if (btnClockOut) btnClockOut.style.display = (state === 'in' || state === 'paused') ? 'inline-flex' : 'none';

		if (dotEl) {
			dotEl.className = 'dot w-2.5 h-2.5 rounded-full ' + (state === 'out' ? 'bg-slate-400' : 'bg-emerald-500 ring-4 ring-emerald-500/25');
		}

		if (statusTextEl) {
			if (state === 'in') {
				statusTextEl.textContent = arcEtc.i18n.statusIn || 'You are clocked in.';
			} else if (state === 'paused') {
				statusTextEl.textContent = arcEtc.i18n.statusBreak || 'You are paused.';
			} else {
				statusTextEl.textContent = arcEtc.i18n.statusOut || 'You are clocked out.';
			}
		}

		if (clientEl && data && data.clients) {
			populateSelect(clientEl, data.clients, data.entry && data.entry.client ? data.entry.client : '', arcEtc.i18n.client || 'Client');
		}
		if (activityEl && data && data.activities) {
			populateSelect(activityEl, data.activities, data.entry && data.entry.activity ? data.entry.activity : '', arcEtc.i18n.activity || 'Activity');
		}

		if (data && data.recentTasks) {
			renderRecent(data.recentTasks);
		}
		if (data && data.todayBlocks) {
			renderTodayBlocks(data.todayBlocks);
		}

		if (timerInterval) {
			clearInterval(timerInterval);
		}
		timerInterval = setInterval(updateTimer, 1000);
		updateTimer();
	}

	function bootstrap() {
		send('bootstrap', {}, function (err, data) {
			if (err) {
				showMessage(err, true);
				return;
			}
			setUI(data.status || 'out', data);
		});
	}

	if (btnClockIn) {
		btnClockIn.addEventListener('click', function () {
			showMessage('Clocking in...', false);
			const data = {
				client: clientEl ? clientEl.value : '',
				activity: activityEl ? activityEl.value : '',
				project: projectEl ? projectEl.value : '',
				task: taskEl ? taskEl.value : '',
				tags: tagsEl ? tagsEl.value : '',
				billable: billableEl && billableEl.checked ? 1 : 0,
				notes: notesEl ? notesEl.value : '',
			};
			send('clockin', data, function (err, data) {
				if (err) {
					showMessage(err, true);
					return;
				}
				showMessage('Clocked in successfully.', false);
				setUI('in', data);
			});
		});
	}

	if (btnPause) {
		btnPause.addEventListener('click', function () {
			showMessage('Pausing...', false);
			send('pause', {}, function (err, data) {
				if (err) {
					showMessage(err, true);
					return;
				}
				showMessage('Paused.', false);
				setUI('paused', data);
			});
		});
	}

	if (btnResume) {
		btnResume.addEventListener('click', function () {
			showMessage('Resuming...', false);
			send('resume', {}, function (err, data) {
				if (err) {
					showMessage(err, true);
					return;
				}
				showMessage('Resumed.', false);
				setUI('in', data);
			});
		});
	}

	if (btnClockOut) {
		btnClockOut.addEventListener('click', function () {
			if (!confirm(arcEtc.i18n.confirmClockOut)) {
				return;
			}
			showMessage('Clocking out...', false);
			const data = {
				lunch_start: lunchStartEl ? lunchStartEl.value : '',
				lunch_end: lunchEndEl ? lunchEndEl.value : '',
				notes: notesEl ? notesEl.value : '',
			};
			send('clockout', data, function (err, data) {
				if (err) {
					showMessage(err, true);
					return;
				}
				showMessage('Clocked out successfully.', false);
				setUI('out', data);
			});
		});
	}

	if (clientEl) {
		clientEl.addEventListener('change', function () {
			if (!this.value) return;
			const client = this.value;
			if (bootData && bootData.recentTasks) {
				const match = bootData.recentTasks.find(function (t) { return t.client === client; });
				if (match && activityEl && !activityEl.value) activityEl.value = match.activity || '';
			}
		});
	}

	document.addEventListener('keydown', function (e) {
		if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT')) {
			return;
		}
		if (e.key === 'Enter') {
			if (state === 'out' && btnClockIn) btnClockIn.click();
			else if ((state === 'in' || state === 'paused') && btnClockOut) btnClockOut.click();
		} else if (e.key === ' ') {
			e.preventDefault();
			if (state === 'in' && btnPause) btnPause.click();
			else if (state === 'paused' && btnResume) btnResume.click();
		}
	});

	bootstrap();
})(jQuery);
