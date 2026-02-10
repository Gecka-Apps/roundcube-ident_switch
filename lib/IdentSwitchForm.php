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
	public static function get_common_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.common.';
		return [
			$prefix . 'enabled' => ['type' => 'checkbox', 'onchange' => 'plugin_switchIdent_enabled_onChange();'],
			$prefix . 'label' => ['type' => 'text', 'size' => 32, 'placeholder' => $record['email'] ?? ''],
			$prefix . 'readonly' => ['type' => 'hidden'],
		];
	}

	/**
	 * Build the IMAP form fields for identity settings.
	 *
	 * @param array $record Identity record data used for placeholders.
	 * @return array Form field definitions for IMAP host, port, TLS, username, password, delimiter.
	 */
	public static function get_imap_fields(array &$record): array
	{
		$prefix = 'ident_switch.form.imap.';
		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 143],
			$prefix . 'tls' => ['type' => 'checkbox'],
			$prefix . 'username' => ['type' => 'text', 'size' => 64, 'placeholder' => $record['email'] ?? ''],
			$prefix . 'password' => ['type' => 'password', 'size' => 64],
			$prefix . 'delimiter' => ['type' => 'text', 'size' => 1, 'placeholder' => 'Auto'],
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

		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 587],
			$prefix . 'auth' => ['value' => $authType->show([$record['ident_switch.form.smtp.auth'] ?? null])],
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

		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 4190],
			$prefix . 'auth' => ['value' => $authType->show([$record['ident_switch.form.sieve.auth'] ?? null])],
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

		$row = null;
		if (isset($args['record']['identity_id'])) {
			$sql = 'SELECT * FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $args['record']['identity_id'], $rc->user->ID);
			$row = $rc->db->fetch_assoc($q);
		}

		$record = &$args['record'];

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
				'sieve_host' => 'sieve.host',
				'sieve_port' => 'sieve.port',
				'sieve_auth' => 'sieve.auth',
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
			$record['ident_switch.form.imap.tls'] = (bool)($row['flags'] & ident_switch::DB_SECURE_IMAP_TLS);

			// Set readonly if needed
			$cfg = $preconfig->get($record['email']);
			if (is_array($cfg) && ($cfg['readonly'] ?? false)) {
				$record['ident_switch.form.common.readonly'] = 1;
				if (in_array(strtoupper($cfg['user'] ?? ''), ['EMAIL', 'MBOX'])) {
					$record['ident_switch.form.common.readonly'] = 2;
				}
			}
		} else {
			$preconfig->apply($record);
		}

		$args['form']['ident_switch.common'] = [
			'name' => $this->plugin->gettext('form.common.caption'),
			'content' => self::get_common_fields($record),
		];
		$args['form']['ident_switch.imap'] = [
			'name' => $this->plugin->gettext('form.imap.caption'),
			'content' => self::get_imap_fields($record),
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

		if (!self::get_field_value('common', 'enabled', false)) {
			return $args;
		}

		$data = $this->validate();
		if (!empty($data['err'])) {
			$this->plugin->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $data['err'];
		}

		$this->apply_readonly_preconfig($data, $args['record']['email']);

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

		// Handle TLS flag from preconfig
		if (!empty($record['ident_switch.form.imap.tls'])) {
			$data['flags'] |= ident_switch::DB_SECURE_IMAP_TLS;
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

		$retVal['imap.host'] = self::get_field_value('imap', 'host');
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

		$retVal['imap.delimiter'] = self::get_field_value('imap', 'delimiter');
		if (strlen($retVal['imap.delimiter'] ?? '') > 1) {
			$retVal['err'] = 'delim.long';
			return $retVal;
		}

		$retVal['imap.user'] = self::get_field_value('imap', 'username');
		if (strlen($retVal['imap.user'] ?? '') > 64) {
			$retVal['err'] = 'user.long';
			return $retVal;
		}

		$retVal['smtp.host'] = self::get_field_value('smtp', 'host');
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

		$retVal['sieve.host'] = self::get_field_value('sieve', 'host');
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

		// Parse secure settings
		$retVal['flags'] = ident_switch::DB_ENABLED;

		$tls = self::get_field_value('imap', 'tls', false);
		if ($tls) {
			$retVal['flags'] |= ident_switch::DB_SECURE_IMAP_TLS;
		}

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
		$sql = 'SELECT id, password FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $data['id'], $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if ($r) {
			// Record already exists, will update it
			$sql = 'UPDATE ' .
				$rc->db->table_name(ident_switch::TABLE) .
				' SET flags = ?, label = ?, imap_host = ?, imap_port = ?, imap_delimiter = ?, username = ?, password = ?,' .
				' smtp_host = ?, smtp_port = ?, smtp_auth = ?, sieve_host = ?, sieve_port = ?, sieve_auth = ?,' .
				' notify_check = ?, notify_basic = ?, notify_sound = ?, notify_desktop = ?,' .
				' user_id = ?, iid = ?' .
				' WHERE id = ?';
		} elseif ($data['flags'] & ident_switch::DB_ENABLED) {
			// No record exists, create new one
			$sql = 'INSERT INTO ' .
				$rc->db->table_name(ident_switch::TABLE) .
				'(flags, label, imap_host, imap_port, imap_delimiter, username, password,' .
				' smtp_host, smtp_port, smtp_auth, sieve_host, sieve_port, sieve_auth,' .
				' notify_check, notify_basic, notify_sound, notify_desktop,' .
				' user_id, iid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		} else {
			return false;
		}

		// Do we need to update pwd?
		if ($data['imap.pass'] !== ($r['password'] ?? null)) {
			$data['imap.pass'] = $rc->encrypt($data['imap.pass']);
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
			$data['sieve.host'],
			$data['sieve.port'],
			$data['sieve.auth'],
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
