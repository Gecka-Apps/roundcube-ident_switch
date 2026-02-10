/**
 * ident_switch - Identity settings form handler.
 *
 * Copyright (C) 2018 Boris Gulay
 * Copyright (C) 2026 Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */

/**
 * Default ports per protocol and security type.
 */
var ident_switch_portDefaults = {
	imap:  { '': 143, tls: 143, ssl: 993 },
	smtp:  { '': 25,  tls: 587, ssl: 465 },
	sieve: { '': 4190, tls: 4190, ssl: 4190 }
};

$(function() {
	$("INPUT[name='_ident_switch.form.common.enabled']").change();
	plugin_switchIdent_processPreconfig();

	// Bind security change handlers
	$.each(['imap', 'smtp', 'sieve'], function(i, proto) {
		var secSel = "SELECT[name='_ident_switch.form." + proto + ".security']";
		$(secSel).on('change', function() {
			plugin_switchIdent_onSecurityChange(proto, $(this).val());
		});
	});

	// Bind blur handlers for smart placeholder clearing
	$.each(['imap', 'smtp', 'sieve'], function(i, proto) {
		var portFld = $("INPUT[name='_ident_switch.form." + proto + ".port']");
		portFld.on('blur', function() {
			plugin_switchIdent_clearIfDefault($(this));
		});
		var hostFld = $("INPUT[name='_ident_switch.form." + proto + ".host']");
		hostFld.on('blur', function() {
			plugin_switchIdent_clearIfDefault($(this));
		});
	});

	// Delimiter blur handler
	$("INPUT[name='_ident_switch.form.imap.delimiter']").on('blur', function() {
		plugin_switchIdent_clearIfDefault($(this));
	});
});

/**
 * Handle security dropdown change: update port placeholder and show/hide warning.
 * @param {string} proto - Protocol name (imap, smtp, sieve).
 * @param {string} security - Selected security value ('', 'tls', 'ssl').
 */
function plugin_switchIdent_onSecurityChange(proto, security) {
	var portFld = $("INPUT[name='_ident_switch.form." + proto + ".port']");
	var defaults = ident_switch_portDefaults[proto];
	// Check if port value matches any known default for this protocol
	var portVal = portFld.val();
	var isDefault = !portVal;
	if (portVal) {
		$.each(defaults, function(_, v) {
			if (parseInt(portVal) === v) {
				isDefault = true;
				return false;
			}
		});
	}

	// Update placeholder to new default
	var newDefault = defaults[security] || defaults[''];
	portFld.attr('placeholder', newDefault);

	// If port was empty or matched a known default, clear it
	if (isDefault) {
		portFld.val('');
	}

	// Show/hide security warning
	var warningId = '#ident-switch-security-warning-' + proto;
	if (security === '') {
		$(warningId).show();
	} else {
		$(warningId).hide();
	}
}

/**
 * On blur: clear field if value matches its placeholder.
 * @param {jQuery} $field - The input field.
 */
function plugin_switchIdent_clearIfDefault($field) {
	var val = $.trim($field.val());
	var placeholder = $field.attr('placeholder') || '';
	if (val !== '' && val === String(placeholder)) {
		$field.val('');
	}
}

function plugin_switchIdent_processPreconfig() {
	var disFld = $("INPUT[name='_ident_switch.form.common.readonly']");
	disFld.parentsUntil("TABLE", "TR").hide();

	var disVal = disFld.val();
	if (disVal > 0) {
		$("INPUT[name='_ident_switch.form.imap.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.imap.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.imap.port']").prop("disabled", true);

		$("INPUT[name='_ident_switch.form.smtp.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.smtp.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.smtp.port']").prop("disabled", true);

		$("INPUT[name='_ident_switch.form.sieve.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.sieve.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.sieve.port']").prop("disabled", true);
	}
	if (2 == disVal) {
		$("INPUT[name='_ident_switch.form.imap.username']").prop("disabled", true);
	}
}

function plugin_switchIdent_enabled_onChange(e) {
	var $enFld = $("INPUT[name='_ident_switch.form.common.enabled'], INPUT[name='_ident_switch.form.imap.host'], INPUT[name='_ident_switch.form.smtp.host']");
	var $fieldset = $enFld.parents("FIELDSET");
	var isEnabled = $enFld.is(":checked");
	$("INPUT[name!='_ident_switch.form.common.enabled']", $fieldset).prop("disabled", !isEnabled);
	$("SELECT", $fieldset).prop("disabled", !isEnabled);
	plugin_switchIdent_processPreconfig();
}
