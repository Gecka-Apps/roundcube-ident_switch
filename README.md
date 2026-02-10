# Ultimate Identity Switcher for Roundcube

[![Version](https://img.shields.io/github/v/release/Gecka-Apps/roundcube-ident_switch?label=version)](https://github.com/Gecka-Apps/roundcube-ident_switch/releases)
[![PHP CodeSniffer](https://github.com/Gecka-Apps/roundcube-ident_switch/actions/workflows/phpcs.yml/badge.svg)](https://github.com/Gecka-Apps/roundcube-ident_switch/actions/workflows/phpcs.yml)
[![PHP Lint](https://github.com/Gecka-Apps/roundcube-ident_switch/actions/workflows/php-lint.yml/badge.svg)](https://github.com/Gecka-Apps/roundcube-ident_switch/actions/workflows/php-lint.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4)](https://www.php.net)
[![Roundcube](https://img.shields.io/badge/Roundcube-1.6+-37BEFF)](https://roundcube.net)

Switch between multiple IMAP accounts within a single Roundcube session.

## About

**ident_switch** is a Roundcube plugin that turns identities into full account shortcuts. Configure each identity with its own IMAP, SMTP and Sieve server, then switch between accounts with a single click from the toolbar dropdown. Aliases, notifications, and domain preconfig are all built in.

*Originally created by Boris Gulay. This fork is a major rewrite by [Gecka](https://gecka.nc) — modernized for PHP 8.2+, restructured codebase, and packed with new features: alias identities, Sieve support, background mail notifications, domain preconfig, connection testing, and per-protocol security.*

## Features

### Multi-Account Switching

- One-click account switching from a toolbar dropdown
- Each identity can be linked to a separate IMAP/SMTP server with its own credentials
- Automatic special folder mapping (Sent, Drafts, Junk, Trash) per account
- Encrypted password storage in database

### Alias Identities

- Link identities as aliases of any configured account (not just the primary)
- Aliases share the parent account's IMAP inbox, SMTP and Sieve configuration
- Compose "From" dropdown automatically filtered to show only the active account and its aliases

### Sieve (ManageSieve) Support

- Per-account Sieve server configuration with independent host, port and credentials
- Three authentication modes: same as IMAP, none, or custom credentials
- Sieve section only appears when the `managesieve` plugin is active

### New Mail Notifications

- Background mail checking across all secondary accounts
- Unread count badge in the account switcher dropdown
- Per-account notification settings: favicon, sound, desktop notifications
- Optional round-robin mode to reduce IMAP connections with many accounts
- Integrates with Roundcube's `newmail_notifier` plugin

### Domain Preconfig

- Pre-fill server settings based on the identity's email domain
- Wildcard domain support (`*`) for catch-all defaults
- Lock preconfigured fields as read-only to prevent user changes
- Restrict account creation to preconfigured domains only (`preconfig_only` mode)

### Security

- Per-protocol security selection: None, STARTTLS, or SSL/TLS (IMAP, SMTP, Sieve independently)
- Connection testing on save (IMAP, SMTP, Sieve) to catch misconfigurations early
- Passwords encrypted using Roundcube's built-in encryption
- Warning displayed when selecting unencrypted connections

## Requirements

- PHP 8.2+
- Roundcube 1.6+
- MySQL, PostgreSQL, or SQLite

## Installation

### With Composer (recommended)

Navigate to your Roundcube installation directory and run:

```bash
composer require gecka/ident-switch
```

The [roundcube/plugin-installer](https://github.com/roundcube/plugin-installer) will automatically place the plugin in the correct `plugins/` directory, initialize the database schema, and offer to enable it.

> **Don't have Composer?** See [getcomposer.org](https://getcomposer.org/download/) for installation instructions.

> **Running as root on a VPS?** Roundcube files are typically owned by `www-data`. Run Composer as the web server user to avoid permission issues:
> ```bash
> sudo -u www-data composer require gecka/ident-switch
> ```

### Manual

1. Place this plugin folder into the plugins directory of Roundcube:
   ```bash
   cd /path/to/roundcube/plugins/
   git clone https://github.com/Gecka-Apps/roundcube-ident_switch.git ident_switch
   ```

2. Add `ident_switch` to `$config['plugins']` in your Roundcube config:
   ```php
   $config['plugins'] = array('ident_switch', /* other plugins */);
   ```

3. Initialize the database schema:
   ```bash
   bin/updatedb.sh --package=ident_switch --dir=plugins/ident_switch/SQL
   ```

## Configuration

Copy the sample configuration file and edit it to match your environment:

```bash
cp plugins/ident_switch/config.inc.php.dist plugins/ident_switch/config.inc.php
```

### Domain Preconfig

Pre-fill server settings per email domain so users don't have to enter them manually:

```php
$config['ident_switch.preconfig'] = [
    'domain.tld' => [
        'imap_host' => 'ssl://mail.domain.tld:993',
        'smtp_host' => 'tls://mail.domain.tld:587',
        'sieve_host' => 'tls://mail.domain.tld:4190',
        'user' => 'email',        // 'email' = full address, 'mbox' = local part
        'readonly' => true,       // lock fields in UI
    ],
    '*' => [                      // wildcard: default for unlisted domains
        'imap_host' => 'ssl://mail.example.com:993',
        'smtp_host' => 'tls://mail.example.com:587',
        'user' => 'email',
    ],
];
```

### Options

| Setting | Default | Description |
|---------|---------|-------------|
| `ident_switch.check_mail` | `true` | Enable background mail checking across secondary accounts |
| `ident_switch.round_robin` | `false` | Check one account per refresh cycle instead of all at once |
| `ident_switch.hide_notifier_warning` | `false` | Hide the warning when `newmail_notifier` plugin is not active |
| `ident_switch.preconfig_only` | `false` | Restrict account creation to preconfigured domains only |
| `ident_switch.debug` | `false` | Log SMTP/Sieve routing and alias resolution to `logs/ident_switch.log` |

## Usage

1. Create a new identity in **Settings > Identities**
2. Choose the account mode:
   - **Primary account** — default identity, no server config needed
   - **Separate account** — configure IMAP, SMTP, and optionally Sieve servers
   - **Alias of [account]** — link to an existing account's servers
3. For separate accounts, fill in server details (or let preconfig handle it)
4. The account switcher dropdown appears in the toolbar once at least one separate account is configured

## Updating

```bash
composer update gecka/ident-switch
```

Database migrations are applied automatically by the Roundcube plugin installer.

## Migrating from the Original Plugin

If you are upgrading from `boressoft/ident_switch`, `toteph42/identity_switch`, or another fork:

> **Important:** This version (5.x) requires a **v4.x database schema** as its starting point. If you are running v1.x–v3.x, you must first upgrade to v4.x (`boressoft/ident_switch`) before migrating to this fork. This applies to all supported databases (MySQL, PostgreSQL, SQLite).

### With Composer

1. Replace the old package in `composer.json` with `gecka/ident-switch` and run `composer update`
2. Database migrations are applied automatically

### Manual

1. Replace the plugin files in `plugins/ident_switch/`
2. Run the database migration:
   ```bash
   bin/updatedb.sh --package=ident_switch --dir=plugins/ident_switch/SQL
   ```

### Configuration

If you use `config.inc.php`, update it to the new format — the `'host'` key has been replaced by separate `'imap_host'` and `'smtp_host'` keys (old `'host'` key still works as fallback).

## Localization

Available in 7 languages: English, French, German, Italian, Dutch, Russian, Slovenian.

## Version Compatibility

| Version | Roundcube | PHP |
|---------|-----------|-----|
| 5.x | 1.6+ | 8.2+ |
| 4.x | 1.3 — 1.5 | 7.x — 8.1 |
| 1.x — 3.x | 1.1 — 1.3 | *discontinued* |

## License

This plugin is released under the [GNU Affero General Public License Version 3](https://www.gnu.org/licenses/agpl-3.0.html).

Original code by Boris Gulay licensed under GPL-3.0+. New contributions licensed under AGPL-3.0+.

## Authors

- **Boris Gulay** — Original developer (2016–2022)
- **Christian Landvogt** — Special folders support
- **Gergely Papp** — Bug fixes
- **Laurent Dinclaux** — Current maintainer ([Gecka](https://gecka.nc))

---

Built with 🥥 and ☕ by [Gecka](https://gecka.nc) — Kanaky-New Caledonia 🇳🇨
