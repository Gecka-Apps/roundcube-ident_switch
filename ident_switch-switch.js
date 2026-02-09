/**
 * ident_switch - Account switcher UI component.
 *
 * Copyright (C) 2016-2018 Boris Gulay
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */

$(function() {
	$sw = $('#plugin-ident_switch-account');
	isOk = false;

	switch (rcmail.env['skin']) {
		case 'larry':
			isOk = plugin_switchIdent_addCbLarry($sw);
			break;
		case 'classic':
			isOk = plugin_switchIdent_addCbClassic($sw);
			break;
        case 'elastic':
            isOk = plugin_switchIdent_addCbElastic($sw);
            break;
	}

	if (isOk)
        $sw.show();
});

function plugin_switchIdent_addCbLarry($sw) {
	var $truName = $('.topright .username');
	if ($truName.length > 0) {
		if ($sw.length > 0) {
			$sw.prependTo('.topright');
			$truName.hide();

			return true;
		}
	}

	return false;
}

function plugin_switchIdent_addCbClassic($sw) {
	var $taskBar = $('#taskbar');
	if ($taskBar.length > 0) {
		$taskBar.prepend($sw);
		return true;
	}

	return false;
}

function plugin_switchIdent_addCbElastic($sw) {
    var $taskBar = $('.header-title.username');
    $sw.css("background-color", "transparent").css("border","none");
    $sw.css("background-position-x","left 0.75rem").css("padding","0 0 0 2rem");
    $sw.css("font-weight","bold").css("box-shadow","none");
    if ($taskBar.length > 0) {
        $taskBar.prepend($sw);
        return true;
    }

    return false;
}

function plugin_switchIdent_switch(val) {
    rcmail.env.unread_counts = {};
    console.log(rcmail.env.unread_counts);
	rcmail.http_post('plugin.ident_switch.switch', { '_ident-id': val, '_mbox': rcmail.env.mailbox });
}

function  plugin_switchIdent_fixIdent(iid) {
	if (parseInt(iid) > 0)
		$("#_from").val(iid);
}
