# ident_switch
ident_switch plugin for Roundcube

This plugin allows users to switch between different accounts (including remote) in single Roundcube session like this:

![Screenshot example](https://i.imgur.com/rRIqtA8.jpg)

*Inspired by identities_imap plugin that is no longer supported.*

### Installation ###

#### With Composer (recommended) ####

```sh
composer require gecka/roundcube-ident_switch
bin/updatedb.sh --package=ident_switch --dir=plugins/ident_switch/SQL
```

The plugin is automatically registered. Enable it by adding `'ident_switch'` to `$config['plugins']` in your Roundcube `config/config.inc.php`.

#### Manual ####

1. Copy or symlink the plugin to `plugins/ident_switch` in your Roundcube installation.
2. Add `'ident_switch'` to the `$config['plugins']` array in your Roundcube `config/config.inc.php`.
3. Initialize the database schema:
```sh
bin/updatedb.sh --package=ident_switch --dir=plugins/ident_switch/SQL
```

#### Configuration ####

Optionally copy `plugins/ident_switch/config.inc.php.dist` to `plugins/ident_switch/config.inc.php` and edit it to preconfigure mail settings per domain.

### Where to start ###
* In settings interface create new identity.
* For all identities except default you will see new section of settings - "Plugin ident_switch" (see screenshot below). Enter data required to connect to  remote server. Don't forget to check Enabled check box.
* After you have created at least one identity with active plugin you will see combobox in the top right corner instead of plain text field with account name. It will allows you to switch to another account.

### Settings ###

![Plugin settings](https://i.imgur.com/rFaHUbR.jpg)

* **Enabled** - enables plugin (i.e. account switcing) for this identity.
* **Label** - text that will be displayed in drop down list for this identity. If left blank email will be used.
* **IMAP**
    * **Server host name** - host name for imap server. If left blank 'localhost' will be used.
    * **Port** - port on server to connect to. If left blank 143 will be used.
    * **Secure connection** - enabled secure connection (TLS) *for both IMAP and SMTP*.
    * **Username** - login used *for IMAP and SMTP servers*.
    * **Password** - password used *for IMAP and SMTP servers*. It's stored encrypted in database.
* **SMTP**
    * **Server host name** - host name for imap server. If left blank 'localhost' will be used.
    * **Port** - port on server to connect to. If left blank 587 will be used.

### Migrating from the original plugin ###

If you are upgrading from the original `boressoft/ident_switch` or another fork:

1. Replace the plugin files in `plugins/ident_switch/`.
2. Run the database migration to update the schema:
```sh
bin/updatedb.sh --package=ident_switch --dir=plugins/ident_switch/SQL
```
3. If you use `config.inc.php`, update it to the new format. The `'host'` key has been replaced by separate `'imap_host'` and `'smtp_host'` keys (the old `'host'` key still works as fallback):
```php
'domain.tld' => [
    'imap_host' => 'ssl://mail.domain.tld:993',
    'smtp_host' => 'tls://mail.domain.tld:587',
    'user' => 'email',
    'readonly' => true,
],
```
4. If installed via Composer, update `composer.json` to use `gecka/roundcube-ident_switch` instead of `boressoft/ident_switch`.

### Version compatibility ###
* Versions 1.X (not supported any more) - for Roundcube v1.1
* Versions 2.X (not supported any more) - for Roundcube v1.2
* Versions 3.X (not supported any more) - for Roundcube v1.3
* Versions 4.x - for Roundcube v1.3, 1.4 and 1.5.
* Versions 5.x - for Roundcube v1.6+ (PHP 8.1+)

This is a fork of the [original plugin](https://bitbucket.org/BoresExpress/ident_switch) by Boris Gulay, maintained at [Gecka-apps/ident_switch](https://github.com/Gecka-apps/ident_switch).

### Contributors ###
* **Boris Gulay** - Original developer (2016-2022)
* **Christian Landvogt** - Special folders support (2019)
* **Gergely Papp** - Bug fixes (2021)
* **Mickael** - SMTP compatibility fix (2022)
* **Laurent Dinclaux - Gecka** - Current maintainer (2026)
