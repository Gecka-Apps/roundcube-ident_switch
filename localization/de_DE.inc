<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Plugin ident_switch
$labels['form.common.caption'] = 'Plugin ident_switch';

// Enabled
$labels['form.common.enabled'] = 'Aktiviert';

// Label
$labels['form.common.label'] = 'Bezeichnung';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Der Wert in Feld \'Bezeichnung\' ist zu lang (max. 32 Zeichen).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Server host name
$labels['form.imap.host'] = 'Servername';

// Security
$labels['form.imap.security'] = 'Sicherheit';

// Port
$labels['form.imap.port'] = 'Port';

// Username
$labels['form.imap.username'] = 'Benutzername';

// Password
$labels['form.imap.password'] = 'Passwort';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Ordnerhierarchie-Trennzeichen';
$labels['form.imap.delimiter.auto'] = 'Automatisch erkennen';
$labels['form.imap.delimiter.manual'] = 'Manuell';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = 'Der Wert in Feld \'Benutzername\' ist zu lang (max. 64 Zeichen).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Server host name
$labels['form.smtp.host'] = 'Servername';

// Security
$labels['form.smtp.security'] = 'Sicherheit';

// Port
$labels['form.smtp.port'] = 'Port';

// Authorization
$labels['form.smtp.auth'] = 'Autorisierung';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Wie IMAP';

// None
$labels['form.smtp.auth.none'] = 'Keine';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Servername';

// Security
$labels['form.sieve.security'] = 'Sicherheit';

// Port
$labels['form.sieve.port'] = 'Port';

// Authorization
$labels['form.sieve.auth'] = 'Autorisierung';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Wie IMAP';

// None
$labels['form.sieve.auth.none'] = 'Keine';


// Notifications
$labels['form.notify.caption'] = 'Benachrichtigungen';

// Check for new mail
$labels['form.notify.check'] = 'Auf neue Nachrichten prüfen';

// Focus / favicon
$labels['form.notify.basic'] = 'Fokus / Favicon';

// Sound
$labels['form.notify.sound'] = 'Ton';

// Desktop notification
$labels['form.notify.desktop'] = 'Desktop-Benachrichtigung';

// Use default
$labels['form.notify.default'] = 'Standard';

// On
$labels['form.notify.on'] = 'Ein';

// Off
$labels['form.notify.off'] = 'Aus';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Benachrichtigungen erfordern das Plugin newmail_notifier. Bitte aktivieren Sie es oder kontaktieren Sie Ihren Administrator.';


// Security options
// None
$labels['form.security.none'] = 'Keine';

// STARTTLS
$labels['form.security.starttls'] = 'STARTTLS';

// SSL/TLS
$labels['form.security.ssl'] = 'SSL/TLS';

// None warning
$labels['form.security.none_warning'] = 'Unverschlüsselte Verbindungen sind unsicher. Ihre Anmeldedaten und E-Mails können abgefangen werden. Verwenden Sie nach Möglichkeit SSL/TLS oder STARTTLS.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'Der Wert in Feld \'Servername\' ist zu lang (max. 64 Zeichen).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'Der Wert in Feld \'Port\' muss eine Zahl sein.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'Der Wert in Feld \'Port\' muss zwischen 1 und 65535 liegen.';
