<?php
/**
 * ident_switch - Identity form handling.
 *
 * Builds the identity settings form, validates user input,
 * and persists account configuration to the database.
 *
 * Copyright (C) 2016-2022 Boris Gulay
 * Copyright (C) 2019      Christian Landvogt
 * Copyright (C) 2026      Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */
class IdentSwitchForm
{
	private ident_switch $plugin;

	public function __construct(ident_switch $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * Build the common form fields for identity settings.
	 *
	 * @param array $record Identity record data used for placeholders.
	 * @return array Form field definitions for enabled, label, and readonly.
	 */
	public function get_common_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.common.';

		$labelInput = new html_inputfield([
			'name' => "_{$prefix}label",
			'type' => 'text',
			'size' => 32,
			'placeholder' => $record['email'] ?? '',
		]);
		$labelHtml = $labelInput->show($record["{$prefix}label"] ?? '')
			. html::span(
				['class' => 'form-text'],
				rcube::Q($this->plugin->gettext('form.common.label.hint'))
			);

		return [
			$prefix . 'enabled' => ['type' => 'checkbox', 'onchange' => 'plugin_switchIdent_enabled_onChange();'],
			$prefix . 'label' => ['value' => $labelHtml],
			$prefix . 'readonly' => ['type' => 'hidden'],
		];
	}

	/**
	 * Build the IMAP form fields for identity settings.
	 *
	 * @param array $record Identity record data used for placeholders.
	 * @return array Form field definitions for IMAP host, port, security, username, password, delimiter.
	 */
	public function get_imap_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.imap.';
		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'security' => ['value' => $this->build_security_select($prefix, $record, 'ssl')],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 993],
			$prefix . 'username' => ['type' => 'text', 'size' => 64, 'placeholder' => $record['email'] ?? ''],
			$prefix . 'password' => ['type' => 'password', 'size' => 64, 'autocomplete' => 'new-password'],
			$prefix . 'delimiter' => ['value' => $this->build_delimiter_field($prefix, $record)],
		];
	}

	/**
	 * Build the SMTP form fields for identity settings.
	 *
	 * @param array $record Identity record data used for default auth type selection.
	 * @return array Form field definitions for SMTP host, port, and auth type.
	 */
	public function get_smtp_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.smtp.';

		$authType = new html_select(['name' => "_{$prefix}auth"]);
		$authType->add($this->plugin->gettext('form.smtp.auth.imap'), ident_switch::SMTP_AUTH_IMAP);
		$authType->add($this->plugin->gettext('form.smtp.auth.none'), ident_switch::SMTP_AUTH_NONE);
		$authType->add($this->plugin->gettext('form.smtp.auth.custom'), ident_switch::SMTP_AUTH_CUSTOM);

		// Cast to int: html_select::show() uses strict comparison (===),
		// option values are integers (constants), but POST/DB may return strings.
		$authVal = isset($record[$prefix . 'auth']) ? (int)$record[$prefix . 'auth'] : null;

		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'security' => ['value' => $this->build_security_select($prefix, $record, 'tls')],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 587],
			$prefix . 'auth' => ['value' => $authType->show($authVal !== null ? [$authVal] : [])],
			$prefix . 'username' => ['type' => 'text', 'size' => 64, 'autocomplete' => 'off'],
			$prefix . 'password' => ['type' => 'password', 'size' => 64, 'autocomplete' => 'new-password'],
		];
	}

	/**
	 * Build the Sieve (managesieve) form fields for identity settings.
	 *
	 * @param array $record Identity record data used for default auth type selection.
	 * @return array Form field definitions for Sieve host, port, and auth type.
	 */
	public function get_sieve_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.sieve.';

		$authType = new html_select(['name' => "_{$prefix}auth"]);
		$authType->add($this->plugin->gettext('form.sieve.auth.imap'), ident_switch::SIEVE_AUTH_IMAP);
		$authType->add($this->plugin->gettext('form.sieve.auth.none'), ident_switch::SIEVE_AUTH_NONE);
		$authType->add($this->plugin->gettext('form.sieve.auth.custom'), ident_switch::SIEVE_AUTH_CUSTOM);

		// Cast to int: html_select::show() uses strict comparison (===)
		$authVal = isset($record[$prefix . 'auth']) ? (int)$record[$prefix . 'auth'] : null;

		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'security' => ['value' => $this->build_security_select($prefix, $record, 'tls')],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 4190],
			$prefix . 'auth' => ['value' => $authType->show($authVal !== null ? [$authVal] : [])],
			$prefix . 'username' => ['type' => 'text', 'size' => 64, 'autocomplete' => 'off'],
			$prefix . 'password' => ['type' => 'password', 'size' => 64, 'autocomplete' => 'new-password'],
		];
	}

	/**
	 * Build the notification form fields for identity settings.
	 *
	 * Shows the actual newmail_notifier default value in each tri-state select.
	 * Selecting the default option stores NULL so changes to global defaults propagate.
	 *
	 * @param array $record Identity record data used for default values.
	 * @return array Form field definitions for notification preferences.
	 */
	public function get_notification_fields(array &$record): array
	{
		$rc = rcmail::get_instance();
		$prefix = 'ident_switch.form.notify.';

		$defaultKeys = [
			'basic' => 'newmail_notifier_basic',
			'sound' => 'newmail_notifier_sound',
			'desktop' => 'newmail_notifier_desktop',
		];

		$triState = function (string $name) use ($prefix, &$record, $rc, $defaultKeys): string {
			$defaultVal = $rc->config->get($defaultKeys[$name] ?? '', false);
			$defaultLabel = $defaultVal
				? $this->plugin->gettext('form.notify.on')
				: $this->plugin->gettext('form.notify.off');
			$defaultLabel .= ' (' . strtolower($this->plugin->gettext('form.notify.default')) . ')';

			$select = new html_select(['name' => "_{$prefix}{$name}"]);
			$select->add($defaultLabel, '');
			$select->add($this->plugin->gettext('form.notify.on'), '1');
			$select->add($this->plugin->gettext('form.notify.off'), '0');
			return $select->show($record[$prefix . $name] ?? '');
		};

		return [
			$prefix . 'check' => ['type' => 'checkbox'],
			$prefix . 'basic' => ['value' => $triState('basic')],
			$prefix . 'sound' => ['value' => $triState('sound')],
			$prefix . 'desktop' => ['value' => $triState('desktop')],
		];
	}

	/**
	 * Build a security dropdown select for a protocol section.
	 *
	 * @param string $prefix  Form field prefix (e.g. 'ident_switch.form.imap.').
	 * @param array  $record  Identity record data.
	 * @param string $default Default security value ('', 'tls', 'ssl').
	 * @return string Rendered HTML select element.
	 */
	private function build_security_select(string $prefix, array &$record, string $default): string
	{
		$select = new html_select(['name' => "_{$prefix}security"]);
		$select->add($this->plugin->gettext('form.security.none'), '');
		$select->add($this->plugin->gettext('form.security.starttls'), 'tls');
		$select->add($this->plugin->gettext('form.security.ssl'), 'ssl');

		$current = $record[$prefix . 'security'] ?? $default;
		$proto = str_replace('ident_switch.form.', '', rtrim($prefix, '.'));
		$hidden = $current !== '' ? ' style="display:none"' : '';
		$warning = '<div id="ident-switch-security-warning-' . $proto . '" class="boxwarning"' . $hidden . '>'
			. rcube::Q($this->plugin->gettext('form.security.none_warning'))
			. '</div>';

		return $select->show($current) . $warning;
	}

	/**
	 * Build the delimiter field with Auto/Manual mode select.
	 *
	 * @param string $prefix  Form field prefix (e.g. 'ident_switch.form.imap.').
	 * @param array  $record  Identity record data.
	 * @return string Rendered HTML select + text input.
	 */
	private function build_delimiter_field(string $prefix, array &$record): string
	{
		$delimValue = $record[$prefix . 'delimiter'] ?? '';
		$mode = ($delimValue !== '' && $delimValue !== null) ? 'manual' : 'auto';

		$select = new html_select(['name' => "_{$prefix}delimiter_mode"]);
		$select->add($this->plugin->gettext('form.imap.delimiter.auto'), 'auto');
		$select->add($this->plugin->gettext('form.imap.delimiter.manual'), 'manual');

		$hidden = $mode === 'auto' ? ' style="display:none"' : '';
		$input = new html_inputfield(['name' => "_{$prefix}delimiter", 'size' => 1]);

		return $select->show($mode)
			. ' <span id="ident-switch-delimiter-input"' . $hidden . '>'
			. $input->show($delimValue)
			. '</span>';
	}

	/**
	 * Check if a domain is allowed for ident_switch configuration.
	 *
	 * When 'ident_switch.preconfig_only' is enabled, only domains with
	 * a matching preconfig entry are allowed.
	 *
	 * @param string $email Email address to check.
	 * @return bool True if allowed, false if blocked by preconfig_only.
	 */
	private function is_domain_allowed(string $email): bool
	{
		$rc = rcmail::get_instance();
		if (!$rc->config->get('ident_switch.preconfig_only', false)) {
			return true;
		}
		$preconfig = new IdentSwitchPreconfig($this->plugin);
		return $preconfig->get($email) !== false;
	}

	/**
	 * Pass preconfig data and settings to JS environment.
	 *
	 * Parses each domain's protocol URLs into host/security/port components
	 * so the client can dynamically populate form fields on email change.
	 *
	 * @param rcmail $rc Roundcube instance.
	 */
	private function pass_preconfig_to_js(rcmail $rc): void
	{
		$this->plugin->load_config();
		$allPreconfig = $rc->config->get('ident_switch.preconfig', []);
		$preconfigOnly = $rc->config->get('ident_switch.preconfig_only', false);

		$jsPreconfig = [];
		foreach ($allPreconfig as $domain => $cfg) {
			$entry = [];

			$protocols = [
				'imap' => $cfg['imap_host'] ?? $cfg['host'] ?? '',
				'smtp' => $cfg['smtp_host'] ?? $cfg['host'] ?? '',
				'sieve' => $cfg['sieve_host'] ?? '',
			];

			foreach ($protocols as $proto => $url) {
				if (empty($url)) {
					continue;
				}
				$urlArr = parse_url($url);
				if (!is_array($urlArr)) {
					continue;
				}
				$scheme = strtolower($urlArr['scheme'] ?? '');
				$entry[$proto] = [
					'host' => $urlArr['host'] ?? '',
					'security' => in_array($scheme, ['ssl', 'tls']) ? $scheme : '',
					'port' => !empty($urlArr['port']) ? intval($urlArr['port']) : '',
				];
			}

			$entry['user'] = $cfg['user'] ?? '';
			$entry['readonly'] = !empty($cfg['readonly']);
			$jsPreconfig[$domain] = $entry;
		}

		$rc->output->set_env('ident_switch_preconfig', $jsPreconfig);
		$rc->output->set_env('ident_switch_preconfig_only', $preconfigOnly);
		$rc->output->set_env('ident_switch_warning_tpl', $this->plugin->gettext('form.preconfig_only_warning'));
	}

	/**
	 * Parse scheme prefix from a host string.
	 *
	 * @param string $host Host string, optionally prefixed with ssl:// or tls://.
	 * @return array{scheme: string, host: string} Parsed scheme and bare host.
	 */
	private static function parse_host_scheme(string $host): array
	{
		$lower = strtolower($host);
		if (str_starts_with($lower, 'ssl://')) {
			return ['scheme' => 'ssl', 'host' => substr($host, 6)];
		}
		if (str_starts_with($lower, 'tls://')) {
			return ['scheme' => 'tls', 'host' => substr($host, 6)];
		}
		return ['scheme' => '', 'host' => $host];
	}

	/**
	 * Compose a host string with scheme prefix.
	 *
	 * @param string|null $host     Bare host name.
	 * @param string|null $security Security type ('', 'tls', 'ssl').
	 * @return string|null Host with scheme prefix, or null if host is empty.
	 */
	private static function compose_host_scheme(?string $host, ?string $security): ?string
	{
		if ($host === null || $host === '') {
			return $host;
		}
		if ($security === 'ssl' || $security === 'tls') {
			return $security . '://' . $host;
		}
		return $host;
	}

	/**
	 * Restore plugin form field values from POST data after a save error.
	 *
	 * When the identity save is aborted (validation or connection error),
	 * Roundcube re-renders the form. Core fields are preserved from POST by RC,
	 * but plugin fields would revert to DB values without this restoration.
	 *
	 * @param array &$record Identity record to overlay POST values onto.
	 */
	private function restore_post_values(array &$record): void
	{
		// Text and select fields (trimmed)
		$fields = [
			['common', 'label'],
			['imap', 'host'],
			['imap', 'port'],
			['imap', 'username'],
			['imap', 'delimiter'],
			['smtp', 'host'],
			['smtp', 'port'],
			['smtp', 'auth'],
			['smtp', 'username'],
			['sieve', 'host'],
			['sieve', 'port'],
			['sieve', 'auth'],
			['sieve', 'username'],
			['notify', 'basic'],
			['notify', 'sound'],
			['notify', 'desktop'],
		];

		foreach ($fields as [$section, $field]) {
			$rawVal = self::get_field_value($section, $field, false);
			if ($rawVal !== null) {
				$record["ident_switch.form.{$section}.{$field}"] = self::get_field_value($section, $field);
			}
		}

		// Security selects: empty string means "None", must not be trimmed to null
		foreach (['imap', 'smtp', 'sieve'] as $proto) {
			$rawVal = self::get_field_value($proto, 'security', false);
			if ($rawVal !== null) {
				$record["ident_switch.form.{$proto}.security"] = $rawVal;
			}
		}

		// Password fields (raw, no trim)
		foreach (['imap', 'smtp', 'sieve'] as $proto) {
			$rawVal = self::get_field_value($proto, 'password', false, true);
			if ($rawVal !== null) {
				$record["ident_switch.form.{$proto}.password"] = $rawVal;
			}
		}

		// Checkboxes: absent from POST means unchecked
		$record['ident_switch.form.common.enabled'] = !empty(self::get_field_value('common', 'enabled', false));
		$record['ident_switch.form.notify.check'] = !empty(self::get_field_value('notify', 'check', false));
	}

	/**
	 * Test IMAP, SMTP, and Sieve connections using validated form data.
	 *
	 * @param array  $data     Validated data from validate().
	 * @param string $email    Email address (fallback username).
	 * @param string $imapPass Raw IMAP password (not encrypted).
	 * @return string|null Error key on failure, null on success.
	 */
	private function test_connections(array $data, string $email, string $imapPass): ?string
	{
		$rc = rcmail::get_instance();

		// --- IMAP test ---
		$imapHostFull = $data['imap.host'] ?: 'localhost';
		$parsed = self::parse_host_scheme($imapHostFull);
		$imapHost = $parsed['host'];
		$imapSsl = $parsed['scheme'] ?: null;
		$imapDefPort = ($imapSsl === 'ssl') ? 993 : 143;
		$imapPort = $data['imap.port'] ?: $imapDefPort;
		$imapUser = $data['imap.user'] ?: $email;

		$imap = new rcube_imap_generic();
		$result = $imap->connect($imapHost, $imapUser, $imapPass, [
			'port' => (int)$imapPort,
			'ssl_mode' => $imapSsl,
			'timeout' => 10,
		]);
		if (!$result) {
			ident_switch::write_log("IMAP connection test failed: {$imap->error}");
			return 'imap.connect';
		}
		$imap->closeConnection();

		// --- SMTP test ---
		$smtpAuth = (int)($data['smtp.auth'] ?? ident_switch::SMTP_AUTH_IMAP);
		if ($smtpAuth !== ident_switch::SMTP_AUTH_NONE) {
			$smtpHostFull = $data['smtp.host'] ?: 'localhost';
			$smtpParsed = self::parse_host_scheme($smtpHostFull);
			$smtpDefPort = ($smtpParsed['scheme'] === 'ssl') ? 465 : 587;
			$smtpPort = $data['smtp.port'] ?: $smtpDefPort;
			// Compose host with scheme for rcube_smtp (expects ssl://host:port or tls://host:port)
			$smtpConnHost = self::compose_host_scheme($smtpParsed['host'], $smtpParsed['scheme'] ?: null);
			$smtpConnHost .= ':' . $smtpPort;

			if ($smtpAuth === ident_switch::SMTP_AUTH_CUSTOM) {
				$smtpUser = $data['smtp.user'] ?: '';
				$smtpPass = $data['smtp.pass'] ?: '';
			} else {
				$smtpUser = $imapUser;
				$smtpPass = $imapPass;
			}

			$smtp = new rcube_smtp();
			$result = $smtp->connect($smtpConnHost, null, $smtpUser, $smtpPass);
			if (!$result) {
				ident_switch::write_log("SMTP connection test failed");
				return 'smtp.connect';
			}
			$smtp->disconnect();
		}

		// --- Sieve test (only if host is configured and auth is not None) ---
		$sieveAuth = (int)($data['sieve.auth'] ?? ident_switch::SIEVE_AUTH_IMAP);
		$sieveHostFull = $data['sieve.host'] ?? '';
		if (!empty($sieveHostFull) && $sieveAuth !== ident_switch::SIEVE_AUTH_NONE) {
			$sieveParsed = self::parse_host_scheme($sieveHostFull);
			$sievePort = $data['sieve.port'] ?: 4190;
			$useTls = ($sieveParsed['scheme'] === 'tls');
			$sieveHost = $sieveParsed['host'];
			if ($sieveParsed['scheme'] === 'ssl') {
				$sieveHost = 'ssl://' . $sieveHost;
			}

			if ($sieveAuth === ident_switch::SIEVE_AUTH_CUSTOM) {
				$sieveUser = $data['sieve.user'] ?: '';
				$sievePass = $data['sieve.pass'] ?: '';
			} else {
				$sieveUser = $imapUser;
				$sievePass = $imapPass;
			}

			if (class_exists('rcube_sieve')) {
				$sieve = new rcube_sieve($sieveUser, $sievePass, $sieveHost, (int)$sievePort, null, $useTls);
				if ($sieve->error()) {
					ident_switch::write_log("Sieve connection test failed (error code: {$sieve->error()})");
					return 'sieve.connect';
				}
			}
		}

		return null;
	}

	/**
	 * Handle identity_form hook: add plugin-specific fields to the identity editor.
	 *
	 * Loads existing account data from the database (if editing) or applies
	 * preconfigured settings, then adds Common/IMAP/SMTP/Sieve sections to the form.
	 *
	 * @param array                $args      Hook arguments containing 'record' with identity data.
	 * @param IdentSwitchPreconfig $preconfig Preconfiguration handler.
	 * @return array Modified hook arguments with added form sections.
	 */
	public function on_identity_form(array $args, IdentSwitchPreconfig $preconfig): array
	{
		$rc = rcmail::get_instance();

		// When creating a new identity, record may be null
		if ($args['record'] === null) {
			$args['record'] = [];
		}

		// Do not show options for default identity
		if (!empty($args['record']['email']) && strcasecmp($args['record']['email'], $rc->user->data['username']) === 0) {
			return $args;
		}

		$this->plugin->add_texts('localization');

		// Build info section with description and domain warning
		$preconfigOnly = $rc->config->get('ident_switch.preconfig_only', false);
		$domainAllowed = empty($args['record']['email']) || $this->is_domain_allowed($args['record']['email']);

		$warningVisible = $preconfigOnly && !$domainAllowed;

		// Extract domain from email for warning message
		$email = $args['record']['email'] ?? '';
		$domain = '';
		if (!empty($email) && str_contains($email, '@')) {
			$domain = substr($email, strpos($email, '@') + 1);
		}

		$infoContent = html::div(
			['class' => 'boxinformation', 'id' => 'ident-switch-info'],
			rcube::Q($this->plugin->gettext('form.description'))
		);
		$warningContent = html::div(
			['class' => 'boxwarning', 'id' => 'ident-switch-domain-warning',
			 'style' => $warningVisible ? '' : 'display:none'],
			rcube::Q(sprintf($this->plugin->gettext('form.preconfig_only_warning'), $domain))
		);

		$args['form']['ident_switch'] = [
			'name' => $this->plugin->gettext('form.caption'),
			'content' => $infoContent . $warningContent,
		];

		// Pass preconfig data to JS for dynamic form updates
		$this->pass_preconfig_to_js($rc);

		// When preconfig_only is enabled, hide field sections for non-preconfigured domains
		if (!$domainAllowed) {
			return $args;
		}

		$row = null;
		if (isset($args['record']['identity_id'])) {
			$sql = 'SELECT * FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $args['record']['identity_id'], $rc->user->ID);
			$row = $rc->db->fetch_assoc($q);
		}

		$record = &$args['record'];

		// Tell JS whether this identity has an existing ident_switch record
		$rc->output->set_env('ident_switch_has_record', !empty($row));

		// Load data if exists
		if ($row) {
			$dbToForm = [
				'label' => 'common.label',
				'imap_host' => 'imap.host',
				'imap_port' => 'imap.port',
				'imap_delimiter' => 'imap.delimiter',
				'username' => 'imap.username',
				'password' => 'imap.password',
				'smtp_host' => 'smtp.host',
				'smtp_port' => 'smtp.port',
				'smtp_auth' => 'smtp.auth',
				'smtp_username' => 'smtp.username',
				'smtp_password' => 'smtp.password',
				'sieve_host' => 'sieve.host',
				'sieve_port' => 'sieve.port',
				'sieve_auth' => 'sieve.auth',
				'sieve_username' => 'sieve.username',
				'sieve_password' => 'sieve.password',
				'notify_check' => 'notify.check',
				'notify_basic' => 'notify.basic',
				'notify_sound' => 'notify.sound',
				'notify_desktop' => 'notify.desktop',
				];
			foreach ($row as $k => $v) {
				if (isset($dbToForm[$k])) {
					$record['ident_switch.form.' . $dbToForm[$k]] = $v;
				}
			}

			// Parse flags
			$record['ident_switch.form.common.enabled'] = (bool)($row['flags'] & ident_switch::DB_ENABLED);

			// Parse host schemes into separate security fields
			foreach (['imap', 'smtp', 'sieve'] as $proto) {
				$key = "ident_switch.form.{$proto}.host";
				$hostVal = $record[$key] ?? '';
				if ($hostVal !== '') {
					$parsed = self::parse_host_scheme($hostVal);
					$record[$key] = $parsed['host'];
					$record["ident_switch.form.{$proto}.security"] = $parsed['scheme'];
				}
			}

			// Backward compat: if IMAP host had no scheme but TLS flag was set
			if (empty($record['ident_switch.form.imap.security']) && ($row['flags'] & ident_switch::DB_SECURE_IMAP_TLS)) {
				$record['ident_switch.form.imap.security'] = 'tls';
			}

			// Set readonly if needed
			$cfg = $preconfig->get($record['email']);
			if (is_array($cfg) && ($cfg['readonly'] ?? false)) {
				$record['ident_switch.form.common.readonly'] = 1;
				if (in_array(strtoupper($cfg['user'] ?? ''), ['EMAIL', 'MBOX'])) {
					$record['ident_switch.form.common.readonly'] = 2;
				}
				// Override delimiter from preconfig (auto-detect if not specified)
				$record['ident_switch.form.imap.delimiter'] = $cfg['delimiter'] ?? null;
			}
		} else {
			$preconfig->apply($record);
		}

		// Restore POST values when form is re-displayed after a save error
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->restore_post_values($record);
		}

		$args['form']['ident_switch.common'] = [
			'name' => $this->plugin->gettext('form.common.general'),
			'content' => $this->get_common_fields($record),
		];
		$args['form']['ident_switch.imap'] = [
			'name' => $this->plugin->gettext('form.imap.caption'),
			'content' => $this->get_imap_fields($record),
		];
		$args['form']['ident_switch.smtp'] = [
			'name' => $this->plugin->gettext('form.smtp.caption'),
			'content' => $this->get_smtp_fields($record),
		];
		$args['form']['ident_switch.sieve'] = [
			'name' => $this->plugin->gettext('form.sieve.caption'),
			'content' => $this->get_sieve_fields($record),
		];
		if (!$rc->config->get('ident_switch.check_mail', true)) {
			// Admin disabled background mail checking
		} elseif ($rc->plugins->get_plugin('newmail_notifier')) {
			$args['form']['ident_switch.notify'] = [
				'name' => $this->plugin->gettext('form.notify.caption'),
				'content' => $this->get_notification_fields($record),
			];
		} elseif (!$rc->config->get('ident_switch.hide_notifier_warning', false)) {
			$args['form']['ident_switch.notify'] = [
				'name' => $this->plugin->gettext('form.notify.caption'),
				'content' => html::div(
					['class' => 'boxinformation'],
					rcube::Q($this->plugin->gettext('form.notify.requires_newmail_notifier'))
				),
			];
		}

		return $args;
	}

	/**
	 * Handle identity_update hook: validate and save plugin fields on identity edit.
	 *
	 * @param array $args Hook arguments containing 'id' and 'record' with identity data.
	 * @return array Modified hook arguments, with 'abort' set on validation failure.
	 */
	public function on_identity_update(array $args): array
	{
		$rc = rcmail::get_instance();

		// Do not do anything for default identity
		if (strcasecmp($args['record']['email'], $rc->user->data['username']) === 0) {
			return $args;
		}

		// Block save for non-preconfigured domains when preconfig_only is enabled
		if (!$this->is_domain_allowed($args['record']['email'])) {
			$this->disable($args['id']);
			return $args;
		}

		if (!self::get_field_value('common', 'enabled', false)) {
			$this->disable($args['id']);
			return $args;
		}

		$data = $this->validate();
		if (!empty($data['err'])) {
			$this->plugin->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $data['err'];
			return $args;
		}

		$this->apply_readonly_preconfig($data, $args['record']['email']);

		// Test connections before saving
		$connErr = $this->test_connections($data, $args['record']['email'], $data['imap.pass']);
		if ($connErr) {
			$this->plugin->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $connErr;
			return $args;
		}

		$data['id'] = $args['id'];
		$this->save($rc, $data);

		return $args;
	}

	/**
	 * Handle identity_create hook: validate plugin fields before identity creation.
	 *
	 * Stores validated data in session since identity_id is not yet available.
	 *
	 * @param array $args Hook arguments containing 'record' with identity data.
	 * @return array Modified hook arguments, with 'abort' set on validation failure.
	 */
	public function on_identity_create(array $args): array
	{
		$rc = rcmail::get_instance();

		// Do not do anything for default identity
		if (strcasecmp($args['record']['email'], $rc->user->data['username']) === 0) {
			return $args;
		}

		// Block creation for non-preconfigured domains when preconfig_only is enabled
		if (!$this->is_domain_allowed($args['record']['email'])) {
			return $args;
		}

		if (!self::get_field_value('common', 'enabled', false)) {
			return $args;
		}

		$data = $this->validate();
		if (!empty($data['err'])) {
			$this->plugin->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $data['err'];
			return $args;
		}

		$this->apply_readonly_preconfig($data, $args['record']['email']);

		// Test connections before saving
		$connErr = $this->test_connections($data, $args['record']['email'], $data['imap.pass']);
		if ($connErr) {
			$this->plugin->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $connErr;
			return $args;
		}

		// Save data for _after (cannot pass with $args)
		$_SESSION['createData' . ident_switch::MY_POSTFIX] = $data;

		return $args;
	}

	/**
	 * Handle identity_create_after hook: persist plugin data after identity creation.
	 *
	 * Retrieves validated data from session and saves it with the new identity_id.
	 *
	 * @param array $args Hook arguments containing 'id' (new identity_id) and 'record'.
	 * @return array Unmodified hook arguments.
	 */
	public function on_identity_create_after(array $args): array
	{
		$rc = rcmail::get_instance();

		// Do not do anything for default identity
		if (strcasecmp($args['record']['email'], $rc->user->data['username']) === 0) {
			return $args;
		}

		$data = $_SESSION['createData' . ident_switch::MY_POSTFIX] ?? null;

		unset($_SESSION['createData' . ident_switch::MY_POSTFIX]);
		if (!$data || count($data) === 0) {
			ident_switch::write_log("Object with ident_switch values not found in session for ID = {$args['id']}.");
		} else {
			$data['id'] = $args['id'];
			$this->save($rc, $data);
		}

		return $args;
	}

	/**
	 * Handle identity_delete hook: remove plugin data when an identity is deleted.
	 *
	 * @param array $args Hook arguments containing 'id' of the identity being deleted.
	 * @return array Unmodified hook arguments.
	 */
	public function on_identity_delete(array $args): array
	{
		$rc = rcmail::get_instance();

		$sql = 'DELETE FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $args['id'], $rc->user->ID);

		if ($rc->db->affected_rows($q)) {
			ident_switch::write_log("Deleted associated information for identity with ID = {$args['id']}.");
		}

		return $args;
	}

	/**
	 * Fill in missing validated data from preconfig when identity is readonly.
	 *
	 * When a preconfig has readonly=true, form fields are disabled in the
	 * browser and not submitted via POST. This fills the missing values
	 * from the preconfig on the server side.
	 *
	 * @param array  $data  Validated data from validate(), modified in place.
	 * @param string $email Email address of the identity.
	 */
	private function apply_readonly_preconfig(array &$data, string $email): void
	{
		$preconfig = new IdentSwitchPreconfig($this->plugin);
		$cfg = $preconfig->get($email);
		if (!is_array($cfg) || empty($cfg['readonly'])) {
			return;
		}

		// Build temporary record to get preconfig values
		$record = ['email' => $email];
		$preconfig->apply($record);

		// Map preconfig record keys to validated data keys
		$map = [
			'ident_switch.form.imap.host' => 'imap.host',
			'ident_switch.form.imap.port' => 'imap.port',
			'ident_switch.form.imap.delimiter' => 'imap.delimiter',
			'ident_switch.form.imap.username' => 'imap.user',
			'ident_switch.form.smtp.host' => 'smtp.host',
			'ident_switch.form.smtp.port' => 'smtp.port',
			'ident_switch.form.sieve.host' => 'sieve.host',
			'ident_switch.form.sieve.port' => 'sieve.port',
		];

		foreach ($map as $recordKey => $dataKey) {
			if (empty($data[$dataKey]) && isset($record[$recordKey])) {
				$data[$dataKey] = $record[$recordKey];
			}
		}
	}

	/**
	 * Validate all plugin form field values from POST data.
	 *
	 * Checks length constraints, port ranges, and parses flags.
	 * Also retrieves and includes the password.
	 *
	 * @return array Validated data with field values and optional 'err' key on failure.
	 */
	public function validate(): array
	{
		$retVal = [];

		$retVal['label'] = self::get_field_value('common', 'label');
		if (strlen($retVal['label'] ?? '') > 32) {
			$retVal['err'] = 'label.long';
			return $retVal;
		}

		// Validate and compose IMAP host with security scheme
		$imapBareHost = self::get_field_value('imap', 'host');
		$retVal['imap.host'] = $imapBareHost;
		$imapSecurity = self::get_field_value('imap', 'security') ?? '';
		$retVal['imap.host'] = self::compose_host_scheme($retVal['imap.host'], $imapSecurity);
		if (strlen($retVal['imap.host'] ?? '') > 64) {
			$retVal['err'] = 'host.long';
			return $retVal;
		}

		$retVal['imap.port'] = self::get_field_value('imap', 'port');
		if ($retVal['imap.port'] && !ctype_digit($retVal['imap.port'])) {
			$retVal['err'] = 'port.num';
			return $retVal;
		}
		if ($retVal['imap.port'] && ($retVal['imap.port'] <= 0 || $retVal['imap.port'] > 65535)) {
			$retVal['err'] = 'port.range';
			return $retVal;
		}

		$delimMode = self::get_field_value('imap', 'delimiter_mode');
		if ($delimMode === 'manual') {
			$retVal['imap.delimiter'] = self::get_field_value('imap', 'delimiter');
			if (strlen($retVal['imap.delimiter'] ?? '') > 1) {
				$retVal['err'] = 'delim.long';
				return $retVal;
			}
		} else {
			$retVal['imap.delimiter'] = null;
		}

		$retVal['imap.user'] = self::get_field_value('imap', 'username');
		if (strlen($retVal['imap.user'] ?? '') > 64) {
			$retVal['err'] = 'user.long';
			return $retVal;
		}

		// Validate and compose SMTP host with security scheme (fallback to IMAP host)
		$retVal['smtp.host'] = self::get_field_value('smtp', 'host') ?: $imapBareHost;
		$smtpSecurity = self::get_field_value('smtp', 'security') ?? '';
		$retVal['smtp.host'] = self::compose_host_scheme($retVal['smtp.host'], $smtpSecurity);
		if (strlen($retVal['smtp.host'] ?? '') > 64) {
			$retVal['err'] = 'host.long';
			return $retVal;
		}

		$retVal['smtp.port'] = self::get_field_value('smtp', 'port');
		if ($retVal['smtp.port'] && !ctype_digit($retVal['smtp.port'])) {
			$retVal['err'] = 'port.num';
			return $retVal;
		}
		if ($retVal['smtp.port'] && ($retVal['smtp.port'] <= 0 || $retVal['smtp.port'] > 65535)) {
			$retVal['err'] = 'port.range';
			return $retVal;
		}

		$retVal['smtp.auth'] = self::get_field_value('smtp', 'auth');
		if (!ctype_digit($retVal['smtp.auth'] ?? '')) {
			$retVal['err'] = 'auth.num';
			return $retVal;
		}

		// Custom SMTP credentials
		if ((int)$retVal['smtp.auth'] === ident_switch::SMTP_AUTH_CUSTOM) {
			$retVal['smtp.user'] = self::get_field_value('smtp', 'username');
			if (strlen($retVal['smtp.user'] ?? '') > 64) {
				$retVal['err'] = 'user.long';
				return $retVal;
			}
			$retVal['smtp.pass'] = self::get_field_value('smtp', 'password', false, true);
		} else {
			$retVal['smtp.user'] = null;
			$retVal['smtp.pass'] = null;
		}

		// Validate and compose Sieve host with security scheme (fallback to IMAP host)
		$retVal['sieve.host'] = self::get_field_value('sieve', 'host') ?: $imapBareHost;
		$sieveSecurity = self::get_field_value('sieve', 'security') ?? '';
		$retVal['sieve.host'] = self::compose_host_scheme($retVal['sieve.host'], $sieveSecurity);
		if (strlen($retVal['sieve.host'] ?? '') > 64) {
			$retVal['err'] = 'host.long';
			return $retVal;
		}

		$retVal['sieve.port'] = self::get_field_value('sieve', 'port');
		if ($retVal['sieve.port'] && !ctype_digit($retVal['sieve.port'])) {
			$retVal['err'] = 'port.num';
			return $retVal;
		}
		if ($retVal['sieve.port'] && ($retVal['sieve.port'] <= 0 || $retVal['sieve.port'] > 65535)) {
			$retVal['err'] = 'port.range';
			return $retVal;
		}

		$retVal['sieve.auth'] = self::get_field_value('sieve', 'auth');
		if (!ctype_digit($retVal['sieve.auth'] ?? '')) {
			$retVal['err'] = 'auth.num';
			return $retVal;
		}

		// Custom Sieve credentials
		if ((int)$retVal['sieve.auth'] === ident_switch::SIEVE_AUTH_CUSTOM) {
			$retVal['sieve.user'] = self::get_field_value('sieve', 'username');
			if (strlen($retVal['sieve.user'] ?? '') > 64) {
				$retVal['err'] = 'user.long';
				return $retVal;
			}
			$retVal['sieve.pass'] = self::get_field_value('sieve', 'password', false, true);
		} else {
			$retVal['sieve.user'] = null;
			$retVal['sieve.pass'] = null;
		}

		// Notification settings
		$retVal['notify.check'] = self::get_field_value('notify', 'check', false) ? 1 : 0;

		$notifyBasic = self::get_field_value('notify', 'basic');
		$retVal['notify.basic'] = ($notifyBasic !== null && $notifyBasic !== '') ? (int)$notifyBasic : null;

		$notifySound = self::get_field_value('notify', 'sound');
		$retVal['notify.sound'] = ($notifySound !== null && $notifySound !== '') ? (int)$notifySound : null;

		$notifyDesktop = self::get_field_value('notify', 'desktop');
		$retVal['notify.desktop'] = ($notifyDesktop !== null && $notifyDesktop !== '') ? (int)$notifyDesktop : null;

		// Get also password
		$retVal['imap.pass'] = self::get_field_value('imap', 'password', false, true);

		// Flags: only enabled, security is now in host field scheme
		$retVal['flags'] = ident_switch::DB_ENABLED;

		return $retVal;
	}

	/**
	 * Retrieve a form field value from POST input.
	 *
	 * @param string $section Form section name (common, imap, smtp).
	 * @param string $field   Field name within the section.
	 * @param bool   $trim    Whether to trim and nullify empty values.
	 * @param bool   $html    Whether to allow HTML in the value.
	 * @return string|null The field value, or null if empty and trimmed.
	 */
	public static function get_field_value(string $section, string $field, bool $trim = true, bool $html = false): ?string
	{
		$retVal = rcube_utils::get_input_value(
			"_ident_switch_form_{$section}_{$field}",
			rcube_utils::INPUT_POST,
			$html
		);
		if (!$trim) {
			return $retVal;
		}

		return ident_switch::ntrim($retVal);
	}

	/**
	 * Persist plugin field values to the database (insert or update).
	 *
	 * Handles password encryption and determines whether to create a new
	 * record or update an existing one.
	 *
	 * @param rcmail $rc   Roundcube instance for DB access and encryption.
	 * @param array  $data Validated field data including 'id' (identity_id).
	 * @return bool True if a query was executed, false otherwise.
	 */
	public function save(rcmail $rc, array $data): bool
	{
		$sql = 'SELECT id, password, smtp_password, sieve_password FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $data['id'], $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if ($r) {
			// Record already exists, will update it
			$sql = 'UPDATE ' .
				$rc->db->table_name(ident_switch::TABLE) .
				' SET flags = ?, label = ?, imap_host = ?, imap_port = ?, imap_delimiter = ?, username = ?, password = ?,' .
				' smtp_host = ?, smtp_port = ?, smtp_auth = ?, smtp_username = ?, smtp_password = ?,' .
				' sieve_host = ?, sieve_port = ?, sieve_auth = ?, sieve_username = ?, sieve_password = ?,' .
				' notify_check = ?, notify_basic = ?, notify_sound = ?, notify_desktop = ?,' .
				' user_id = ?, iid = ?' .
				' WHERE id = ?';
		} elseif ($data['flags'] & ident_switch::DB_ENABLED) {
			// No record exists, create new one
			$sql = 'INSERT INTO ' .
				$rc->db->table_name(ident_switch::TABLE) .
				'(flags, label, imap_host, imap_port, imap_delimiter, username, password,' .
				' smtp_host, smtp_port, smtp_auth, smtp_username, smtp_password,' .
				' sieve_host, sieve_port, sieve_auth, sieve_username, sieve_password,' .
				' notify_check, notify_basic, notify_sound, notify_desktop,' .
				' user_id, iid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		} else {
			return false;
		}

		// Encrypt IMAP password (compare raw POST value with decrypted DB value)
		$existingImapPass = !empty($r['password']) ? $rc->decrypt($r['password']) : null;
		if ($data['imap.pass'] === $existingImapPass && $existingImapPass !== false) {
			$data['imap.pass'] = $r['password'];
		} else {
			$data['imap.pass'] = $rc->encrypt($data['imap.pass']);
		}

		// Encrypt SMTP password
		$existingSmtpPass = !empty($r['smtp_password']) ? $rc->decrypt($r['smtp_password']) : null;
		if ($data['smtp.pass'] === $existingSmtpPass && $existingSmtpPass !== false) {
			$data['smtp.pass'] = $r['smtp_password'] ?? null;
		} else {
			$data['smtp.pass'] = $data['smtp.pass'] ? $rc->encrypt($data['smtp.pass']) : null;
		}

		// Encrypt Sieve password
		$existingSievePass = !empty($r['sieve_password']) ? $rc->decrypt($r['sieve_password']) : null;
		if ($data['sieve.pass'] === $existingSievePass && $existingSievePass !== false) {
			$data['sieve.pass'] = $r['sieve_password'] ?? null;
		} else {
			$data['sieve.pass'] = $data['sieve.pass'] ? $rc->encrypt($data['sieve.pass']) : null;
		}

		$rc->db->query(
			$sql,
			$data['flags'],
			$data['label'],
			$data['imap.host'],
			$data['imap.port'],
			$data['imap.delimiter'],
			$data['imap.user'],
			$data['imap.pass'],
			$data['smtp.host'],
			$data['smtp.port'],
			$data['smtp.auth'],
			$data['smtp.user'],
			$data['smtp.pass'],
			$data['sieve.host'],
			$data['sieve.port'],
			$data['sieve.auth'],
			$data['sieve.user'],
			$data['sieve.pass'],
			$data['notify.check'] ?? 1,
			$data['notify.basic'] ?? null,
			$data['notify.sound'] ?? null,
			$data['notify.desktop'] ?? null,
			$rc->user->ID,
			$data['id'],
			$r['id'] ?? null
		);

		return true;
	}

	/**
	 * Disable the ident_switch flag for a given identity.
	 *
	 * @param int $iid Identity ID to disable.
	 */
	public function disable(int $iid): void
	{
		$rc = rcmail::get_instance();

		$sql = 'UPDATE ' . $rc->db->table_name(ident_switch::TABLE) . ' SET flags = flags & ? WHERE iid = ? AND user_id = ?';
		$rc->db->query($sql, ~ident_switch::DB_ENABLED, $iid, $rc->user->ID);
	}
}
