<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Separate Account
$labels['form.caption'] = 'Ločen račun';
$labels['form.description'] = 'Konfigurirajte to identiteto kot ločen račun z lastnimi povezavami do poštnega strežnika.';
$labels['form.preconfig_only_warning'] = 'Konfiguracija ločenega računa za domeno %s ni na voljo.';

// General
$labels['form.common.general'] = 'Splošno';

// Account mode
$labels['form.common.mode'] = 'Način računa';
$labels['form.common.mode.primary'] = 'Glavni račun';
$labels['form.common.mode.separate'] = 'Ločen račun';
$labels['form.common.mode.hint'] = 'Povežite to identiteto z obstoječim računom ali jo konfigurirajte kot ločen račun z lastnim poštnim strežnikom.';

// Label
$labels['form.common.label'] = 'Oznaka';
$labels['form.common.label.hint'] = 'Ime, prikazano v izbirniku računov. Če je prazno, se uporabi e-poštni naslov.';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Število znakov v polju \'Oznaka\' presega maksimalno število znakov (max 32 znakov).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Incoming mail server
$labels['form.imap.host'] = 'Strežnik dohodne pošte';

// Security
$labels['form.imap.security'] = 'Varnost';

// Port
$labels['form.imap.port'] = 'Vrata';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Ločilo hierarhije map';
$labels['form.imap.delimiter.auto'] = 'Samodejno zaznavanje';
$labels['form.imap.delimiter.manual'] = 'Ročno';

// Username
$labels['form.imap.username'] = 'Uporabniško ime';

// Password
$labels['form.imap.password'] = 'Geslo';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = 'Število znakov v polju \'Uporabniško ime\' presega maksimalno število znakov (max 64 znakov).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Outgoing mail server
$labels['form.smtp.host'] = 'Strežnik odhodne pošte';

// Security
$labels['form.smtp.security'] = 'Varnost';

// Port
$labels['form.smtp.port'] = 'Vrata';

// Authorization
$labels['form.smtp.auth'] = 'Avtorizacija';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Tako kot IMAP';

// None
$labels['form.smtp.auth.none'] = 'Brez';

// Custom
$labels['form.smtp.auth.custom'] = 'Po meri';

// Username
$labels['form.smtp.username'] = 'Uporabniško ime';

// Password
$labels['form.smtp.password'] = 'Geslo';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Ime oz. naslov Sieve strežnika';

// Security
$labels['form.sieve.security'] = 'Varnost';

// Port
$labels['form.sieve.port'] = 'Vrata';

// Authorization
$labels['form.sieve.auth'] = 'Avtorizacija';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Tako kot IMAP';

// None
$labels['form.sieve.auth.none'] = 'Brez';

// Custom
$labels['form.sieve.auth.custom'] = 'Po meri';

// Username
$labels['form.sieve.username'] = 'Uporabniško ime';

// Password
$labels['form.sieve.password'] = 'Geslo';


// Notifications
$labels['form.notify.caption'] = 'Obvestila';

// Check for new mail
$labels['form.notify.check'] = 'Preveri nova sporočila';

// Focus / favicon
$labels['form.notify.basic'] = 'Fokus / favicon';

// Sound
$labels['form.notify.sound'] = 'Zvok';

// Desktop notification
$labels['form.notify.desktop'] = 'Namizno obvestilo';

// Use default
$labels['form.notify.default'] = 'privzeto';

// On
$labels['form.notify.on'] = 'Vključeno';

// Off
$labels['form.notify.off'] = 'Izključeno';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Obvestila zahtevajo vtičnik newmail_notifier. Prosimo, omogočite ga ali se obrnite na skrbnika.';


// Security options
// None
$labels['form.security.none'] = 'Brez';

// STARTTLS
$labels['form.security.starttls'] = 'STARTTLS';

// SSL/TLS
$labels['form.security.ssl'] = 'SSL/TLS';

// None warning
$labels['form.security.none_warning'] = 'Nešifrirane povezave so nevarne. Vaše poverilnice in e-pošta so lahko prestreženi. Kadar koli je mogoče, uporabite SSL/TLS ali STARTTLS.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'Število znakov v polju \'Ime oz. naslov IMAP strežnika\' presega maksimalno število znakov (max 64 znakov).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'Vrednost \'Vrata\' mora biti številka.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'Vrednost v polju \'Vrata\' mora biti med 1 in 65535.';

// IMAP connection test failed.
$labels['err.imap.connect'] = 'Povezava IMAP ni uspela. Preverite naslov strežnika, vrata in poverilnice.';

// SMTP connection test failed.
$labels['err.smtp.connect'] = 'Povezava SMTP ni uspela. Preverite naslov strežnika, vrata in poverilnice.';

// Sieve connection test failed.
$labels['err.sieve.connect'] = 'Povezava Sieve ni uspela. Preverite naslov strežnika, vrata in poverilnice.';
