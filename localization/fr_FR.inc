<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Plugin ident_switch
$labels['form.common.caption'] = 'Plugin ident_switch';

// Enabled
$labels['form.common.enabled'] = 'Activer';

// Label
$labels['form.common.label'] = 'Nom à afficher';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = 'La valeur du champ \'Nom à afficher\' est trop longue (32 caractères max).';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Server host name
$labels['form.imap.host'] = 'Serveur';

// Security
$labels['form.imap.security'] = 'Sécurité';

// Port
$labels['form.imap.port'] = 'Port';

// Username
$labels['form.imap.username'] = 'Nom d\'utilisateur';

// Password
$labels['form.imap.password'] = 'Mot de passe';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Délimiteur de hiérarchie des dossiers';

// Value in \'Username\' field of IMAP section is too long (64 chars max).
$labels['err.user.long'] = 'La valeur du champ \'Nom d\'utilisateur\' de la section IMAP est trop longue (64 caractères max).';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Server host name
$labels['form.smtp.host'] = 'Serveur';

// Security
$labels['form.smtp.security'] = 'Sécurité';

// Port
$labels['form.smtp.port'] = 'Port';

// Authorization
$labels['form.smtp.auth'] = 'Autorisation';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Identique à IMAP';

// None
$labels['form.smtp.auth.none'] = 'Aucune';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Serveur';

// Security
$labels['form.sieve.security'] = 'Sécurité';

// Port
$labels['form.sieve.port'] = 'Port';

// Authorization
$labels['form.sieve.auth'] = 'Autorisation';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Identique à IMAP';

// None
$labels['form.sieve.auth.none'] = 'Aucune';


// Notifications
$labels['form.notify.caption'] = 'Notifications';

// Check for new mail
$labels['form.notify.check'] = 'Vérifier les nouveaux messages';

// Focus / favicon
$labels['form.notify.basic'] = 'Focus / favicon';

// Sound
$labels['form.notify.sound'] = 'Son';

// Desktop notification
$labels['form.notify.desktop'] = 'Notification bureau';

// Use default
$labels['form.notify.default'] = 'par défaut';

// On
$labels['form.notify.on'] = 'Activé';

// Off
$labels['form.notify.off'] = 'Désactivé';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Les notifications nécessitent le plugin newmail_notifier. Veuillez l\'activer ou contacter votre administrateur.';


// Security options
$labels['form.security.none'] = 'Aucune';
$labels['form.security.starttls'] = 'STARTTLS';
$labels['form.security.ssl'] = 'SSL/TLS';
$labels['form.security.none_warning'] = 'Les connexions non chiffrées sont vulnérables. Vos identifiants et emails peuvent être interceptés. Utilisez SSL/TLS ou STARTTLS autant que possible.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = 'La valeur du champ \'Serveur\' est trop longue (64 caractères max).';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = 'La valeur du champ \'Port\' doit être un nombre.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = 'La valeur du champ \'Port\' doit être comprise entre 1 et 65535.';
