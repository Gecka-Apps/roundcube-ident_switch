<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Separate Account
$labels['form.caption'] = 'Account separato';
$labels['form.description'] = 'Configura questa identità come un account separato con le proprie connessioni al server di posta.';
$labels['form.preconfig_only_warning'] = 'La configurazione come account separato non è disponibile per il dominio %s.';

// General
$labels['form.common.general'] = 'Generale';

// Enabled
$labels['form.common.enabled'] = 'Abilita';

// Label
$labels['form.common.label'] = 'Nome visualizzato';
$labels['form.common.label.hint'] = 'Nome visualizzato nel selettore account. Se vuoto, viene utilizzato l\'indirizzo email.';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Il valore del campo \'Nome Visualizzato\' è troppo lungo (massimo 32 caratteri).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Incoming mail server
$labels['form.imap.host'] = 'Server posta in arrivo';

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

// Outgoing mail server
$labels['form.smtp.host'] = 'Server posta in uscita';

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

// Custom
$labels['form.smtp.auth.custom'] = 'Personalizzata';

// Username
$labels['form.smtp.username'] = 'Username';

// Password
$labels['form.smtp.password'] = 'Password';


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

// Custom
$labels['form.sieve.auth.custom'] = 'Personalizzata';

// Username
$labels['form.sieve.username'] = 'Username';

// Password
$labels['form.sieve.password'] = 'Password';


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

// IMAP connection test failed.
$labels['err.imap.connect'] = 'Connessione IMAP fallita. Verificare l\'indirizzo del server, la porta e le credenziali.';

// SMTP connection test failed.
$labels['err.smtp.connect'] = 'Connessione SMTP fallita. Verificare l\'indirizzo del server, la porta e le credenziali.';

// Sieve connection test failed.
$labels['err.sieve.connect'] = 'Connessione Sieve fallita. Verificare l\'indirizzo del server, la porta e le credenziali.';
