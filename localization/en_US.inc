<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Separate Account
$labels['form.caption'] = 'Separate Account';
$labels['form.description'] = 'Configure this identity as a separate account with its own mail server connections.';
$labels['form.preconfig_only_warning'] = 'Separate account configuration is not available for the domain %s.';

// General
$labels['form.common.general'] = 'General';

// Account mode
$labels['form.common.mode'] = 'Account mode';
$labels['form.common.mode.primary'] = 'Primary account';
$labels['form.common.mode.separate'] = 'Separate account';
$labels['form.common.mode.hint'] = 'Link this identity to an existing account, or configure it as a separate account with its own mail server.';

// Label
$labels['form.common.label'] = 'Label';
$labels['form.common.label.hint'] = 'Name displayed in the account switcher. If empty, the email address is used.';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'Value in \'Label\' field is too long (32 chars max).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Incoming mail server
$labels['form.imap.host'] = 'Incoming mail server';

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

// Outgoing mail server
$labels['form.smtp.host'] = 'Outgoing mail server';

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

// Custom
$labels['form.smtp.auth.custom'] = 'Custom';

// Username
$labels['form.smtp.username'] = 'Username';

// Password
$labels['form.smtp.password'] = 'Password';


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

// Custom
$labels['form.sieve.auth.custom'] = 'Custom';

// Username
$labels['form.sieve.username'] = 'Username';

// Password
$labels['form.sieve.password'] = 'Password';


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

// IMAP connection test failed.
$labels['err.imap.connect'] = 'IMAP connection failed. Please check the server address, port, and credentials.';

// SMTP connection test failed.
$labels['err.smtp.connect'] = 'SMTP connection failed. Please check the server address, port, and credentials.';

// Sieve connection test failed.
$labels['err.sieve.connect'] = 'Sieve connection failed. Please check the server address, port, and credentials.';
