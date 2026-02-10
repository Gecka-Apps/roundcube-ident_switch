<?php
/*
 * Localization file for ident_switch plugin
 */
$labels = array();

// Separate Account
$labels['form.caption'] = 'Отдельный аккаунт';
$labels['form.description'] = 'Настройте эту учётную запись как отдельный аккаунт с собственными подключениями к почтовому серверу.';
$labels['form.preconfig_only_warning'] = 'Настройка отдельного аккаунта недоступна для домена %s.';

// General
$labels['form.common.general'] = 'Общие';

// Account mode
$labels['form.common.mode'] = 'Режим аккаунта';
$labels['form.common.mode.primary'] = 'Основной аккаунт';
$labels['form.common.mode.separate'] = 'Отдельный аккаунт';
$labels['form.common.mode.hint'] = 'Привяжите эту учётную запись к существующему аккаунту или настройте её как отдельный аккаунт с собственным почтовым сервером.';

// Label
$labels['form.common.label'] = 'Название';
$labels['form.common.label.hint'] = 'Имя, отображаемое в переключателе аккаунтов. Если пусто, используется адрес электронной почты.';

// Value in \'Label\' field is too long (32 chars max).
$labels['err.label.long'] = '\'Название\' должно быть не длинее 32 символов.';


// IMAP
$labels['form.imap.caption'] = 'IMAP';

// Incoming mail server
$labels['form.imap.host'] = 'Сервер входящей почты';

// Security
$labels['form.imap.security'] = 'Безопасность';

// Port
$labels['form.imap.port'] = 'Порт';

// Folder hierarchy delimiter
$labels['form.imap.delimiter'] = 'Разделитель в иерархии папок';
$labels['form.imap.delimiter.auto'] = 'Автоопределение';
$labels['form.imap.delimiter.manual'] = 'Вручную';

// Username
$labels['form.imap.username'] = 'Имя пользователя';

// Password
$labels['form.imap.password'] = 'Пароль';

// Value in \'Username\' field is too long (64 chars max).
$labels['err.user.long'] = '\'Имя пользователя\' должно быть не длинее 64 символов.';


// SMTP
$labels['form.smtp.caption'] = 'SMTP';

// Outgoing mail server
$labels['form.smtp.host'] = 'Сервер исходящей почты';

// Security
$labels['form.smtp.security'] = 'Безопасность';

// Port
$labels['form.smtp.port'] = 'Порт';

// Authorization
$labels['form.smtp.auth'] = 'Авторизация';

// As IMAP
$labels['form.smtp.auth.imap'] = 'Как IMAP';

// None
$labels['form.smtp.auth.none'] = 'Нет';

// Custom
$labels['form.smtp.auth.custom'] = 'Другие';

// Username
$labels['form.smtp.username'] = 'Имя пользователя';

// Password
$labels['form.smtp.password'] = 'Пароль';


// Sieve
$labels['form.sieve.caption'] = 'Sieve';

// Server host name
$labels['form.sieve.host'] = 'Адрес сервера';

// Security
$labels['form.sieve.security'] = 'Безопасность';

// Port
$labels['form.sieve.port'] = 'Порт';

// Authorization
$labels['form.sieve.auth'] = 'Авторизация';

// As IMAP
$labels['form.sieve.auth.imap'] = 'Как IMAP';

// None
$labels['form.sieve.auth.none'] = 'Нет';

// Custom
$labels['form.sieve.auth.custom'] = 'Другие';

// Username
$labels['form.sieve.username'] = 'Имя пользователя';

// Password
$labels['form.sieve.password'] = 'Пароль';


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


// Security options
// None
$labels['form.security.none'] = 'Нет';

// STARTTLS
$labels['form.security.starttls'] = 'STARTTLS';

// SSL/TLS
$labels['form.security.ssl'] = 'SSL/TLS';

// None warning
$labels['form.security.none_warning'] = 'Незашифрованные соединения небезопасны. Ваши учётные данные и письма могут быть перехвачены. По возможности используйте SSL/TLS или STARTTLS.';


// Errors

// Value in \'Server host name\' field is too long (64 chars max).
$labels['err.host.long'] = '\'Адрес сервера\' должен быть не длинее 64 символов.';

// Value in \'Port\' field must be a number.
$labels['err.port.num'] = '\'Порт\' должен быть числом.';

// Value in \'Port\' field must be between 1 and 65535.
$labels['err.port.range'] = '\'Порт\' должен быть в диапазоне от 1 до 65535.';

// IMAP connection test failed.
$labels['err.imap.connect'] = 'Ошибка подключения к IMAP. Проверьте адрес сервера, порт и учётные данные.';

// SMTP connection test failed.
$labels['err.smtp.connect'] = 'Ошибка подключения к SMTP. Проверьте адрес сервера, порт и учётные данные.';

// Sieve connection test failed.
$labels['err.sieve.connect'] = 'Ошибка подключения к Sieve. Проверьте адрес сервера, порт и учётные данные.';