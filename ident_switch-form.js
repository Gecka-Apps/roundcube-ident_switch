/**
 * ident_switch - Identity settings form handler.
 *
 * Copyright (C) 2018 Boris Gulay
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */

$(function() {
	$("INPUT[name='_ident_switch.form.common.enabled']").change();
	plugin_switchIdent_processPreconfig();
});

function plugin_switchIdent_processPreconfig() {
    var disFld = $("INPUT[name='_ident_switch.form.common.readonly']");
    disFld.parentsUntil("TABLE", "TR").hide();

    var disVal = disFld.val();
    if (disVal > 0) {
        $("INPUT[name='_ident_switch.form.imap.host']").prop("disabled", true);
        $("INPUT[name='_ident_switch.form.imap.tls']").prop("disabled", true);
        $("INPUT[name='_ident_switch.form.imap.port']").prop("disabled", true);

        $("INPUT[name='_ident_switch.form.smtp.host']").prop("disabled", true);
        $("INPUT[name='_ident_switch.form.smtp.port']").prop("disabled", true);
    }
    if (2 == disVal) {
        $("INPUT[name='_ident_switch.form.imap.username']").prop("disabled", true);
    }

}

function plugin_switchIdent_enabled_onChange(e) {
    var $enFld = $("INPUT[name='_ident_switch.form.common.enabled'], INPUT[name='_ident_switch.form.imap.host'], INPUT[name='_ident_switch.form.smtp.host']");
    $("INPUT[name!='_ident_switch.form.common.enabled']", $enFld.parents("FIELDSET")).prop("disabled", !$enFld.is(":checked"));
    plugin_switchIdent_processPreconfig();
}