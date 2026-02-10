/**
 * ident_switch - Account switcher UI and new mail notifications.
 *
 * Places the hidden <select> from the footer into the appropriate
 * skin location (Larry, Classic, Elastic), shows it, and registers
 * notification listeners for background mail checking.
 *
 * Copyright (C) 2016-2018 Boris Gulay
 * Copyright (C) 2026      Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */

$(function() {
	var $wrapper = $('#ident-switch-wrapper');
	if (!$wrapper.length) {
		return;
	}

	var $sw = $wrapper.find('#plugin-ident_switch-account');
	var placed = false;

	switch (rcmail.env.skin) {
		case 'larry':
			placed = plugin_switchIdent_addCbLarry($wrapper, $sw);
			break;
		case 'classic':
			placed = plugin_switchIdent_addCbClassic($wrapper, $sw);
			break;
		case 'elastic':
			placed = plugin_switchIdent_addCbElastic($wrapper, $sw);
			break;
	}

	if (!placed) {
		return;
	}

	$sw.show();

	// Store original option texts for badge appending
	$sw.find('option').each(function() {
		$(this).data('orig-text', $(this).text());
	});

	// Register server-side notification listeners
	rcmail.addEventListener('plugin.ident_switch.update_counts', ident_switch_updateCounts);
	rcmail.addEventListener('plugin.ident_switch.notify', ident_switch_onNotify);

	// Apply initial counts from page load
	if (rcmail.env.ident_switch_initial_counts) {
		ident_switch_updateCounts(rcmail.env.ident_switch_initial_counts);
	}
});

/**
 * Place switcher in Larry skin: replace username in top-right corner.
 */
function plugin_switchIdent_addCbLarry($wrapper, $sw) {
	var $topRight = $('#topline .topright');
	if (!$topRight.length) {
		return false;
	}

	$sw.css({
		'font-weight': 'bold',
		'max-width': '200px',
		'text-overflow': 'ellipsis'
	});

	$topRight.find('.username').hide();
	$topRight.prepend($wrapper);
	return true;
}

/**
 * Place switcher in Classic skin: prepend to task bar.
 */
function plugin_switchIdent_addCbClassic($wrapper) {
	var $taskBar = $('#taskbar');
	if (!$taskBar.length) {
		return false;
	}

	$taskBar.prepend($wrapper);
	return true;
}

/**
 * Place switcher in Elastic skin: replace username in header.
 */
function plugin_switchIdent_addCbElastic($wrapper, $sw) {
	var $target = $('.header-title.username');
	if (!$target.length) {
		return false;
	}

	$sw.css({
		'background': 'transparent',
		'border': 'none',
		'font-weight': 'bold',
		'color': 'inherit',
		'box-shadow': 'none',
		'max-width': '200px',
		'text-overflow': 'ellipsis',
		'padding': '0 0.25em'
	});

	// Hide original username text and elements
	$target.contents().filter(function() {
		return this.nodeType === 3;
	}).remove();
	$target.children().not('#ident-switch-wrapper').hide();

	$target.prepend($wrapper);
	return true;
}

/**
 * Perform account switch via AJAX (called from <select> onchange).
 */
function plugin_switchIdent_switch(val) {
	rcmail.env.unread_counts = {};
	rcmail.http_post('plugin.ident_switch.switch', {
		'_ident-id': val,
		'_mbox': rcmail.env.mailbox
	});
}

/**
 * Update per-account counts in select options and total badge.
 *
 * Each entry in data is {unseen: N, baseline: B}.
 * - Option text: "account (baseline+delta)" when delta > 0, else "account (unseen)"
 * - Badge: sum of deltas across all accounts (new messages not yet visited).
 *
 * @param {Object} data - Map of iid to {unseen, baseline}.
 */
function ident_switch_updateCounts(data) {
	var map = rcmail.env.ident_switch_iid_map || {};
	var $select = $('#plugin-ident_switch-account');
	var totalDelta = 0;

	// Reset all options to original text
	$select.find('option').each(function() {
		var orig = $(this).data('orig-text');
		if (orig) {
			$(this).text(orig);
		}
	});

	// On mail task, skip count for the active account (already shown in folder list)
	var selectedVal = rcmail.env.task === 'mail' ? $select.val() : null;

	// Update each option with its count
	for (var iid in data) {
		if (!data.hasOwnProperty(iid)) continue;
		var info = data[iid];
		var unseen = parseInt(info.unseen) || 0;
		var baseline = parseInt(info.baseline) || 0;
		var delta = Math.max(0, unseen - baseline);

		totalDelta += delta;

		if (map[iid] === undefined) continue;
		var selectVal = '' + map[iid];
		var $opt = $select.find('option[value="' + selectVal + '"]');
		if (!$opt.length) continue;

		// Skip active account on mail task
		if (selectVal === selectedVal) continue;

		var suffix;
		if (delta > 0) {
			suffix = ' (' + baseline + '+' + delta + ')';
		} else if (unseen > 0) {
			suffix = ' (' + unseen + ')';
		} else {
			suffix = '';
		}

		if (suffix) {
			$opt.text($opt.data('orig-text') + suffix);
		}
	}

	// Update total badge (only new messages not yet visited)
	var $badge = $('#ident-switch-badge');
	if (totalDelta > 0) {
		$badge.text(totalDelta).show();
	} else {
		$badge.hide().text('');
	}
}

/**
 * Handle new mail notification from server.
 * @param {Object} data - {iid, label, count, basic, sound, desktop}
 */
function ident_switch_onNotify(data) {
	if (data.basic) {
		ident_switch_notifyBasic();
	}
	if (data.sound) {
		ident_switch_notifySound();
	}
	if (data.desktop) {
		ident_switch_notifyDesktop(data.label, data.count);
	}
}

/**
 * Basic notification: change page title.
 */
function ident_switch_notifyBasic() {
	var marker = '(*) ';
	if (document.title.indexOf(marker) !== 0) {
		document.title = marker + document.title;
	}
}

/**
 * Sound notification: play newmail_notifier sound.
 */
function ident_switch_notifySound() {
	var src = rcmail.assets_path('plugins/newmail_notifier/sound');
	try {
		new Audio(src + '.mp3').play().catch(function() {
			new Audio(src + '.wav').play().catch(function() {});
		});
	} catch(e) {}
}

/**
 * Desktop notification via Notification API.
 */
function ident_switch_notifyDesktop(label, count) {
	if (!('Notification' in window)) {
		return;
	}

	if (Notification.permission === 'default') {
		Notification.requestPermission();
		return;
	}

	if (Notification.permission !== 'granted') {
		return;
	}

	var body = count + ' unread message' + (count > 1 ? 's' : '');
	var popup = new Notification('New mail — ' + label, {
		body: body,
		tag: 'ident_switch_' + label,
		icon: rcmail.assets_path('plugins/ident_switch/mail.png')
	});

	var timeout = (rcmail.env.newmail_notifier_timeout || 10) * 1000;
	setTimeout(function() { popup.close(); }, timeout);
}

/**
 * Fix identity selection in compose view when impersonating.
 */
function plugin_switchIdent_fixIdent(iid) {
	if (parseInt(iid) > 0) {
		$('#_from').val(iid);
	}
}
