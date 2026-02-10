<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Plugin ident_switch
$labels['form.common.caption'] = 'Plugin ident_switch';

// Enabled
$labels['form.common.enabled'] = 'Abilita';

// Label
$labels['form.common.label'] = 'Nome visualizzato';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Il valore del campo \'Nome Visualizzato\' è troppo lungo (massimo 32 caratteri).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Server host name
$labels['form.imap.host'] = 'Nome del server';

// Security
$labels['form.imap.security'] = 'Sicurezza';

// Port
$labels['form.imap.port'] = 'Porta';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Delimitatore gerarchico delle cartelle';
$labels['form.imap.delimiter.auto'] = 'Rilevamento automatico';
$labels['form.imap.delimiter.manual'] = 'Manuale';

// Username
$labels['form.imap.username'] = 'Username';

// Password
$labels['form.imap.password'] = 'Password';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = 'Il valore del campo \'Username\' è troppo lungo (massimo 64 caratteri).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Server host name
$labels['form.smtp.host'] = 'Nome del server';

// Security
$labels['form.smtp.security'] = 'Sicurezza';

// Port
$labels['form.smtp.port'] = 'Porta';

// Authorization
$labels['form.smtp.auth'] = 'Autorizzazione';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Come IMAP';

// None
$labels['form.smtp.auth.none'] = 'Nessuna';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Nome del server';

// Security
$labels['form.sieve.security'] = 'Sicurezza';

// Port
$labels['form.sieve.port'] = 'Porta';

// Authorization
$labels['form.sieve.auth'] = 'Autorizzazione';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Come IMAP';

// None
$labels['form.sieve.auth.none'] = 'Nessuna';


// Notifications
$labels['form.notify.caption'] = 'Notifiche';

// Check for new mail
$labels['form.notify.check'] = 'Controlla nuova posta';

// Focus / favicon
$labels['form.notify.basic'] = 'Focus / favicon';

// Sound
$labels['form.notify.sound'] = 'Suono';

// Desktop notification
$labels['form.notify.desktop'] = 'Notifica desktop';

// Use default
$labels['form.notify.default'] = 'predefinito';

// On
$labels['form.notify.on'] = 'Attivo';

// Off
$labels['form.notify.off'] = 'Disattivo';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Le notifiche richiedono il plugin newmail_notifier. Abilitarlo o contattare l\'amministratore.';


// Security options
// None
$labels['form.security.none'] = 'Nessuna';

// STARTTLS
$labels['form.security.starttls'] = 'STARTTLS';

// SSL/TLS
$labels['form.security.ssl'] = 'SSL/TLS';

// None warning
$labels['form.security.none_warning'] = 'Le connessioni non crittografate non sono sicure. Le credenziali e le email possono essere intercettate. Utilizzare SSL/TLS o STARTTLS quando possibile.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'Il valore del campo \'Nome del server\' è troppo lungo (massimo 64 caratteri).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'Il valore del campo \'Porta\' deve essere un numero.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'Il valore del campo \'Porta\' deve essere compreso tra 1 e 65535.';
