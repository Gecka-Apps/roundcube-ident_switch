<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Plugin ident_switch
$labels['form.common.caption'] = 'Plugin ident_switch';

// Enabled
$labels['form.common.enabled'] = 'Enabled';

// Label
$labels['form.common.label'] = 'Label';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Value in \'Label\' field is too long (32 chars max).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Server host name
$labels['form.imap.host'] = 'Server host name';

// Security
$labels['form.imap.security'] = 'Security';

// Port
$labels['form.imap.port'] = 'Port';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Folder hierarchy delimiter';
$labels['form.imap.delimiter.auto'] = 'Auto-detect';
$labels['form.imap.delimiter.manual'] = 'Manual';

// Username
$labels['form.imap.username'] = 'Username';

// Password
$labels['form.imap.password'] = 'Password';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = 'Value in \'Username\' field is too long (64 chars max).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Server host name
$labels['form.smtp.host'] = 'Server host name';

// Security
$labels['form.smtp.security'] = 'Security';

// Port
$labels['form.smtp.port'] = 'Port';

// Authorization
$labels['form.smtp.auth'] = 'Authorization';

// As IMAP
$labels['form.smtp.auth.imap'] = 'As IMAP';

// None
$labels['form.smtp.auth.none'] = 'None';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Server host name';

// Security
$labels['form.sieve.security'] = 'Security';

// Port
$labels['form.sieve.port'] = 'Port';

// Authorization
$labels['form.sieve.auth'] = 'Authorization';

// As IMAP
$labels['form.sieve.auth.imap'] = 'As IMAP';

// None
$labels['form.sieve.auth.none'] = 'None';


// Notifications
$labels['form.notify.caption'] = 'Notifications';

// Check for new mail
$labels['form.notify.check'] = 'Check for new mail';

// Focus / favicon
$labels['form.notify.basic'] = 'Focus / favicon';

// Sound
$labels['form.notify.sound'] = 'Sound';

// Desktop notification
$labels['form.notify.desktop'] = 'Desktop notification';


// Use default
$labels['form.notify.default'] = 'default';

// On
$labels['form.notify.on'] = 'On';

// Off
$labels['form.notify.off'] = 'Off';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Notifications require the newmail_notifier plugin. Please enable it or contact your administrator.';


// Security options
$labels['form.security.none'] = 'None';
$labels['form.security.starttls'] = 'STARTTLS';
$labels['form.security.ssl'] = 'SSL/TLS';
$labels['form.security.none_warning'] = 'Unencrypted connections are insecure. Your credentials and emails may be intercepted. Use SSL/TLS or STARTTLS whenever possible.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'Value in \'Server host name\' field is too long (64 chars max).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'Value in \'Port\' field must be a number.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'Value in \'Port\' field must be between 1 and 65535.';
