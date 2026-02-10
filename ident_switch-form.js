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
	// Apply initial mode visibility
	var initialMode = $("SELECT[name='_ident_switch.form.common.mode']").val() || 'primary';
	plugin_switchIdent_mode_onChange(initialMode);
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

	// IMAP host → update SMTP/Sieve host placeholders
	$("INPUT[name='_ident_switch.form.imap.host']").on('input change blur', function() {
		var imapHost = $(this).val() || 'localhost';
		$("INPUT[name='_ident_switch.form.smtp.host']").attr('placeholder', imapHost);
		$("INPUT[name='_ident_switch.form.sieve.host']").attr('placeholder', imapHost);
	});

	// Bind auth change handlers for SMTP and Sieve custom credentials
	$.each(['smtp', 'sieve'], function(i, proto) {
		var authSel = "SELECT[name='_ident_switch.form." + proto + ".auth']";
		$(authSel).on('change', function() {
			plugin_switchIdent_onAuthChange(proto, $(this).val());
		});
		// Apply initial visibility
		plugin_switchIdent_onAuthChange(proto, $(authSel).val());
	});

	// Delimiter mode handler
	$("SELECT[name='_ident_switch.form.imap.delimiter_mode']").on('change', function() {
		if ($(this).val() === 'manual') {
			$('#ident-switch-delimiter-input').show();
		} else {
			$('#ident-switch-delimiter-input').hide();
			$("INPUT[name='_ident_switch.form.imap.delimiter']").val('');
		}
	});

	// Watch email field for dynamic preconfig application
	$("INPUT[name='_email']").on('change blur', function() {
		plugin_switchIdent_onEmailChange($(this).val());
	});

	// Set initial placeholders from current field values
	var initialEmail = $("INPUT[name='_email']").val();
	if (initialEmail) {
		$("INPUT[name='_ident_switch.form.common.label']").attr('placeholder', initialEmail);
	}
	var initialImapHost = $("INPUT[name='_ident_switch.form.imap.host']").val();
	if (initialImapHost) {
		$("INPUT[name='_ident_switch.form.smtp.host']").attr('placeholder', initialImapHost);
		$("INPUT[name='_ident_switch.form.sieve.host']").attr('placeholder', initialImapHost);
	}
});

/**
 * Handle mode select change: show/hide form sections based on selected mode.
 * @param {string} mode - 'primary', 'alias:N', or 'separate'.
 */
function plugin_switchIdent_mode_onChange(mode) {
	// Find the fieldsets/legends for each section
	var $modeFld = $("SELECT[name='_ident_switch.form.common.mode']");
	var $allFieldsets = $modeFld.closest('form').find('fieldset');
	var $readonlyRow = $("INPUT[name='_ident_switch.form.common.readonly']").parentsUntil('FIELDSET', 'TR, .row');

	// Separate account fieldsets (label, IMAP, SMTP, Sieve, Notify)
	var separateFieldsets = [];
	$allFieldsets.each(function() {
		var $fs = $(this);
		if ($fs.find("INPUT[name='_ident_switch.form.common.label']").length ||
			$fs.find("INPUT[name='_ident_switch.form.imap.host']").length ||
			$fs.find("INPUT[name='_ident_switch.form.smtp.host']").length ||
			$fs.find("INPUT[name='_ident_switch.form.sieve.host']").length ||
			$fs.find("INPUT[name='_ident_switch.form.notify.check']").length) {
			separateFieldsets.push($fs);
		}
	});

	if (mode === 'separate') {
		// Show all separate account fieldsets
		$.each(separateFieldsets, function(_, $fs) { $fs.show(); });
		plugin_switchIdent_processPreconfig();
	} else {
		// Hide all separate account fieldsets for primary and alias modes
		$.each(separateFieldsets, function(_, $fs) { $fs.hide(); });
	}

	// Always hide readonly row
	$readonlyRow.hide();
}

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

/**
 * Handle auth dropdown change: show/hide custom credential fields.
 * @param {string} proto - Protocol name (smtp, sieve).
 * @param {string} authVal - Selected auth value.
 */
function plugin_switchIdent_onAuthChange(proto, authVal) {
	var isCustom = (authVal === '3'); // SMTP_AUTH_CUSTOM / SIEVE_AUTH_CUSTOM
	var userFld = $("INPUT[name='_ident_switch.form." + proto + ".username']");
	var passFld = $("INPUT[name='_ident_switch.form." + proto + ".password']");
	if (isCustom) {
		userFld.parentsUntil("TABLE", "TR").show();
		passFld.parentsUntil("TABLE", "TR").show();
	} else {
		userFld.parentsUntil("TABLE", "TR").hide();
		passFld.parentsUntil("TABLE", "TR").hide();
	}
}

function plugin_switchIdent_processPreconfig() {
	var disFld = $("INPUT[name='_ident_switch.form.common.readonly']");
	disFld.parentsUntil("TABLE", "TR").hide();

	var disVal = parseInt(disFld.val(), 10) || 0;
	if (disVal > 0) {
		$("INPUT[name='_ident_switch.form.imap.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.imap.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.imap.port']").prop("disabled", true);

		$("INPUT[name='_ident_switch.form.smtp.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.smtp.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.smtp.port']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.smtp.auth']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.smtp.username']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.smtp.password']").prop("disabled", true);

		$("SELECT[name='_ident_switch.form.imap.delimiter_mode']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.imap.delimiter']").prop("disabled", true);

		$("INPUT[name='_ident_switch.form.sieve.host']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.sieve.security']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.sieve.port']").prop("disabled", true);
		$("SELECT[name='_ident_switch.form.sieve.auth']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.sieve.username']").prop("disabled", true);
		$("INPUT[name='_ident_switch.form.sieve.password']").prop("disabled", true);
	}
	if (disVal === 2) {
		$("INPUT[name='_ident_switch.form.imap.username']").prop("disabled", true);
	}
}

/**
 * Handle email field change: apply preconfig and manage domain restriction.
 * @param {string} email - The email address entered by the user.
 */
function plugin_switchIdent_onEmailChange(email) {
	var atPos = email.indexOf('@');
	if (atPos < 0) return;

	var domain = email.substring(atPos + 1).toLowerCase();
	if (!domain) return;

	// Update label and username placeholders to match current email
	$("INPUT[name='_ident_switch.form.common.label']").attr('placeholder', email);

	var preconfig = rcmail.env.ident_switch_preconfig || {};
	var preconfigOnly = rcmail.env.ident_switch_preconfig_only || false;
	var cfg = preconfig[domain] || preconfig['*'] || null;

	// Update username placeholder to match current email
	$("INPUT[name='_ident_switch.form.imap.username']").attr('placeholder', email);

	// Show/hide domain warning and restrict "separate" option
	var $modeSelect = $("SELECT[name='_ident_switch.form.common.mode']");
	if (preconfigOnly && !cfg) {
		var tpl = rcmail.env.ident_switch_warning_tpl || '';
		$('#ident-switch-domain-warning').text(tpl.replace('%s', domain)).show();
		// Disable "separate" option but keep alias options available
		$modeSelect.find('option[value="separate"]').prop('disabled', true);
		if ($modeSelect.val() === 'separate') {
			$modeSelect.val('primary');
			plugin_switchIdent_mode_onChange('primary');
		}
		return;
	}

	$('#ident-switch-domain-warning').hide();
	$modeSelect.find('option[value="separate"]').prop('disabled', false);

	// Only auto-fill for identities without an existing DB record
	if (!rcmail.env.ident_switch_has_record && cfg) {
		plugin_switchIdent_applyJsPreconfig(cfg, email);
	}
}

/**
 * Apply preconfig values from JS environment to the form fields.
 * @param {object} cfg - Preconfig entry for the matched domain.
 * @param {string} email - The full email address.
 */
function plugin_switchIdent_applyJsPreconfig(cfg, email) {
	// Pre-fill server fields silently without changing mode.
	// The user must explicitly select "Separate account" to see them.

	// Apply protocol settings
	$.each(['imap', 'smtp', 'sieve'], function(_, proto) {
		if (!cfg[proto]) return;

		// Set security first (updates port placeholder)
		var security = cfg[proto].security || '';
		$("SELECT[name='_ident_switch.form." + proto + ".security']").val(security);
		plugin_switchIdent_onSecurityChange(proto, security);

		// Set host
		$("INPUT[name='_ident_switch.form." + proto + ".host']").val(cfg[proto].host || '');

		// Set port (empty if matches default, so placeholder shows)
		var port = cfg[proto].port;
		var defaultPort = ident_switch_portDefaults[proto][security] || '';
		$("INPUT[name='_ident_switch.form." + proto + ".port']").val(
			(port && port != defaultPort) ? port : ''
		);
	});

	// Apply username from preconfig user mode
	if (cfg.user) {
		var username = '';
		if (cfg.user.toUpperCase() === 'EMAIL') {
			username = email;
		} else if (cfg.user.toUpperCase() === 'MBOX') {
			username = email.split('@')[0];
		}
		if (username) {
			$("INPUT[name='_ident_switch.form.imap.username']").val(username);
		}
	}

	// Apply readonly level
	var readonlyLevel = 0;
	if (cfg.readonly) {
		var hasUser = cfg.user && ['EMAIL', 'MBOX'].indexOf(cfg.user.toUpperCase()) >= 0;
		readonlyLevel = hasUser ? 2 : 1;
	}
	$("INPUT[name='_ident_switch.form.common.readonly']").val(readonlyLevel);

	// Re-apply preconfig readonly state
	plugin_switchIdent_processPreconfig();
}
