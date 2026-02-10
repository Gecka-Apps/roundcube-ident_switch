<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Separate Account
$labels['form.caption'] = 'Afzonderlijk account';
$labels['form.description'] = 'Configureer deze identiteit als een afzonderlijk account met eigen mailserververbindingen.';
$labels['form.preconfig_only_warning'] = 'Configuratie als afzonderlijk account is niet beschikbaar voor het domein %s.';

// General
$labels['form.common.general'] = 'Algemeen';

// Enabled
$labels['form.common.enabled'] = 'Inschakelen';

// Label
$labels['form.common.label'] = 'Label';
$labels['form.common.label.hint'] = 'Naam weergegeven in de accountkiezer. Indien leeg, wordt het e-mailadres gebruikt.';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'De waarde in het veld \'Label\' is te lang (maximaal 32 karakter is toegestaan).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Incoming mail server
$labels['form.imap.host'] = 'Inkomende mailserver';

// Security
$labels['form.imap.security'] = 'Beveiliging';

// Port
$labels['form.imap.port'] = 'Poortnummer';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Map hiërarchie scheidingsteken';
$labels['form.imap.delimiter.auto'] = 'Automatisch detecteren';
$labels['form.imap.delimiter.manual'] = 'Handmatig';

// Username
$labels['form.imap.username'] = 'Gebruikersnaam';

// Password
$labels['form.imap.password'] = 'Wachtwoord';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = 'De waarde in het veld \'Gebruikersnaam\' is te lang (maximaal 64 karakter is toegestaan).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Outgoing mail server
$labels['form.smtp.host'] = 'Uitgaande mailserver';

// Security
$labels['form.smtp.security'] = 'Beveiliging';

// Port
$labels['form.smtp.port'] = 'Poortnummer';

// Authorization
$labels['form.smtp.auth'] = 'Autorisatie';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Zoals IMAP';

// None
$labels['form.smtp.auth.none'] = 'Geen';

// Custom
$labels['form.smtp.auth.custom'] = 'Aangepast';

// Username
$labels['form.smtp.username'] = 'Gebruikersnaam';

// Password
$labels['form.smtp.password'] = 'Wachtwoord';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Servernaam';

// Security
$labels['form.sieve.security'] = 'Beveiliging';

// Port
$labels['form.sieve.port'] = 'Poortnummer';

// Authorization
$labels['form.sieve.auth'] = 'Autorisatie';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Zoals IMAP';

// None
$labels['form.sieve.auth.none'] = 'Geen';

// Custom
$labels['form.sieve.auth.custom'] = 'Aangepast';

// Username
$labels['form.sieve.username'] = 'Gebruikersnaam';

// Password
$labels['form.sieve.password'] = 'Wachtwoord';


// Notifications
$labels['form.notify.caption'] = 'Meldingen';

// Check for new mail
$labels['form.notify.check'] = 'Controleer op nieuwe berichten';

// Focus / favicon
$labels['form.notify.basic'] = 'Focus / favicon';

// Sound
$labels['form.notify.sound'] = 'Geluid';

// Desktop notification
$labels['form.notify.desktop'] = 'Bureaubladmelding';

// Use default
$labels['form.notify.default'] = 'standaard';

// On
$labels['form.notify.on'] = 'Aan';

// Off
$labels['form.notify.off'] = 'Uit';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Meldingen vereisen de newmail_notifier-plug-in. Schakel deze in of neem contact op met uw beheerder.';


// Security options
// None
$labels['form.security.none'] = 'Geen';

// STARTTLS
$labels['form.security.starttls'] = 'STARTTLS';

// SSL/TLS
$labels['form.security.ssl'] = 'SSL/TLS';

// None warning
$labels['form.security.none_warning'] = 'Onversleutelde verbindingen zijn onveilig. Uw inloggegevens en e-mails kunnen worden onderschept. Gebruik waar mogelijk SSL/TLS of STARTTLS.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'De waarde in het veld \'Servernaam\' is te lang (maximaal 64 karakter is toegestaan).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'De waarde in het veld \'Poortnummer\' moet een nummer zijn.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'De waarde in het veld \'Poortnummer\' moet tussen de 1 en 65535 zijn.';

// IMAP connection test failed.
$labels['err.imap.connect'] = 'IMAP-verbinding mislukt. Controleer het serveradres, de poort en de inloggegevens.';

// SMTP connection test failed.
$labels['err.smtp.connect'] = 'SMTP-verbinding mislukt. Controleer het serveradres, de poort en de inloggegevens.';

// Sieve connection test failed.
$labels['err.sieve.connect'] = 'Sieve-verbinding mislukt. Controleer het serveradres, de poort en de inloggegevens.';
