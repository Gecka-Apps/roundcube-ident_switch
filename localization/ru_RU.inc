<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Plugin ident_switch
$labels['form.common.caption'] = 'Плагин ident_switch';

// Enabled
$labels['form.common.enabled'] = 'Включено';

// Label
$labels['form.common.label'] = 'Название';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = '\'Название\' должно быть не длинее 32 символов.';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Server host name
$labels['form.imap.host'] = 'Адрес сервера';

// Secure connection (TLS)
$labels['form.imap.tls'] = 'Безопасное подключение (TLS)';

// Port
$labels['form.imap.port'] = 'Порт';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Разделитель в иерархии папок';

// Username
$labels['form.imap.username'] = 'Имя пользователя';

// Password
$labels['form.imap.password'] = 'Пароль';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = '\'Имя пользователя\' должно быть не длинее 64 символов.';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Server host name
$labels['form.smtp.host'] = 'Адрес сервера';

// Secure connection (TLS)
$labels['form.smtp.tls'] = 'Безопасное подключение (TLS)';

// Port
$labels['form.smtp.port'] = 'Порт';

// Authorization
$labels['form.smtp.auth'] = 'Авторизация';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Как IMAP';

// None
$labels['form.smtp.auth.none'] = 'Нет';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Адрес сервера';

// Port
$labels['form.sieve.port'] = 'Порт';

// Authorization
$labels['form.sieve.auth'] = 'Авторизация';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Как IMAP';

// None
$labels['form.sieve.auth.none'] = 'Нет';


// Notifications
$labels['form.notify.caption'] = 'Уведомления';

// Check for new mail
$labels['form.notify.check'] = 'Проверять новую почту';

// Focus / favicon
$labels['form.notify.basic'] = 'Фокус / фавикон';

// Sound
$labels['form.notify.sound'] = 'Звук';

// Desktop notification
$labels['form.notify.desktop'] = 'Уведомление на рабочем столе';

// Use default
$labels['form.notify.default'] = 'по умолчанию';

// On
$labels['form.notify.on'] = 'Включено';

// Off
$labels['form.notify.off'] = 'Выключено';

// Requires newmail_notifier plugin
$labels['form.notify.requires_newmail_notifier'] = 'Уведомления требуют плагин newmail_notifier. Пожалуйста, включите его или обратитесь к администратору.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = '\'Адрес сервера\' должен быть не длинее 64 символов.';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = '\'Порт\' должен быть числом.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = '\'Порт\' должен быть в диапазоне от 1 до 65535.';