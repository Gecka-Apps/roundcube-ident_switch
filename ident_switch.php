<?php
/**
 * ident_switch - Roundcube plugin for fast switching between accounts.
 *
 * Copyright (C) 2016-2022 Boris Gulay
 * Copyright (C) 2019      Christian Landvogt
 * Copyright (C) 2021      Gergely Papp
 * Copyright (C) 2022      Mickael
 * Copyright (C) 2026      Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */
class ident_switch extends rcube_plugin
{
	/** @var string Task regex: active on all tasks except login/logout. */
	public $task = '?(?!login|logout).*';

	/** @var string Database table name for this plugin. */
	private const TABLE = 'ident_switch';

	/** @var string Session variable suffix used to store/restore state. */
	private const MY_POSTFIX = '_iswitch';

	/** @var int Flag: account switching is enabled. */
	private const DB_ENABLED = 1;

	/** @var int Flag: use TLS for IMAP connection. */
	private const DB_SECURE_IMAP_TLS = 4;

	/** @var int SMTP authentication: use same credentials as IMAP. */
	private const SMTP_AUTH_IMAP = 1;

	/** @var int SMTP authentication: no authentication required. */
	private const SMTP_AUTH_NONE = 2;

	/**
	 * Initialize plugin: register hooks, actions, and save default folder config.
	 */
	public function init(): void
	{
		$this->add_hook('startup', [$this, 'on_startup']);
		$this->add_hook('render_page', [$this, 'on_render_page']);
		$this->add_hook('smtp_connect', [$this, 'on_smtp_connect']);
		$this->add_hook('identity_form', [$this, 'on_identity_form']);
		$this->add_hook('identity_update', [$this, 'on_identity_update']);
		$this->add_hook('identity_create', [$this, 'on_identity_create']);
		$this->add_hook('identity_create_after', [$this, 'on_identity_create_after']);
		$this->add_hook('identity_delete', [$this, 'on_identity_delete']);
		$this->add_hook('template_object_composeheaders', [$this, 'on_template_object_composeheaders']);
		$this->add_hook('preferences_list', [$this, 'on_special_folders_form']);
		$this->add_hook('preferences_save', [$this, 'on_special_folders_update']);

		$this->register_action('plugin.ident_switch.switch', [$this, 'on_switch']);

		$rc = rcmail::get_instance();
		foreach (rcube_storage::$folder_types as $type) {
			$key = $type . '_mbox_default' . self::MY_POSTFIX;
			if (empty($_SESSION[$key])) {
				$_SESSION[$key] = $rc->config->get($type . '_mbox');
			}
		}
	}

	/**
	 * Handle startup hook: detect impersonation, disable caches, restore folder config.
	 *
	 * @param array $args Hook arguments containing 'task' and other startup data.
	 * @return array Modified hook arguments.
	 */
	public function on_startup(array $args): array
	{
		$rc = rcmail::get_instance();

		if (strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0) {
			// We are impersonating
			$rc->config->set('imap_cache', null);
			$rc->config->set('messages_cache', false);

			if ($args['task'] === 'mail') {
				$this->add_texts('localization/');
				$rc->config->set('create_default_folders', false);
			}
		}

		foreach (rcube_storage::$folder_types as $type) {
			$defaultKey = $type . '_mbox_default' . self::MY_POSTFIX;
			$otherKey = $type . '_mbox' . self::MY_POSTFIX;
			$val = $_SESSION[$otherKey] ?? $_SESSION[$defaultKey];
			$rc->config->set($type . '_mbox', $val);
		}

		return $args;
	}

	/**
	 * Handle render_page hook: inject account switcher or settings form script.
	 *
	 * @param array $args Hook arguments containing page rendering data.
	 * @return array Modified hook arguments.
	 */
	public function on_render_page(array $args): array
	{
		$rc = rcmail::get_instance();

		match ($rc->task) {
			'mail' => $this->render_switch($rc, $args),
			'settings' => $this->include_script('ident_switch-form.js'),
			default => null,
		};

		return $args;
	}

	/**
	 * Render the account switcher dropdown in the mail view.
	 *
	 * Queries the database for all enabled alternative accounts and generates
	 * an HTML select element that is injected into the page footer.
	 *
	 * @param rcmail $rc    Roundcube instance.
	 * @param array  $args  Hook arguments for page rendering.
	 */
	private function render_switch(rcmail $rc, array $args): void
	{
		// Currently selected identity
		$iid_s = $_SESSION['iid' . self::MY_POSTFIX] ?? null;

		$iid = 0;
		if (is_int($iid_s)) {
			$iid = $iid_s;
		} elseif ($iid_s === '-1') {
			$iid = -1;
		} elseif (is_string($iid_s) && ctype_digit($iid_s)) {
			$iid = intval($iid_s);
		}

		$accNames = [$_SESSION['global_alias'] ?? $rc->user->data['username']];
		$accValues = [-1];
		$accSelected = -1;

		// Get list of alternative accounts
		$sql = "SELECT "
			. "isw.id, isw.iid, isw.label, isw.username, ii.email"
			. " FROM"
			. " {$rc->db->table_name(self::TABLE)} isw"
			. " INNER JOIN {$rc->db->table_name('identities')} ii ON isw.iid=ii.identity_id"
			. " WHERE isw.user_id = ? AND isw.flags & ? > 0";
		$qRec = $rc->db->query($sql, $rc->user->data['user_id'], self::DB_ENABLED);
		while ($r = $rc->db->fetch_assoc($qRec)) {
			$accValues[] = $r['id'];
			if ($iid == $r['iid']) {
				$accSelected = $r['id'];
			}

			// Make label
			$lbl = $r['label'];
			if (!$lbl) {
				$username = $r['username'] ?: $r['email'];

				$lbl = str_contains($username, '@')
					? $username
					: $username . '@' . ($r['host'] ?: 'localhost');
			}
			$accNames[] = rcube::Q($lbl);
		}

		// Render UI if user has extra accounts
		if (count($accValues) > 1) {
			$this->include_script('ident_switch-switch.js');

			$select = new html_select([
				'id' => 'plugin-ident_switch-account',
				'style' => 'display: none; padding: 0;',
				'onchange' => 'plugin_switchIdent_switch(this.value);',
			]);
			$select->add($accNames, $accValues);
			$rc->output->add_footer($select->show([$accSelected]));
		}
	}

	/**
	 * Handle smtp_connect hook: configure SMTP settings for the active account.
	 *
	 * Loads SMTP host, port, credentials, and TLS settings from the database
	 * for the currently selected identity.
	 *
	 * @param array $args Hook arguments containing SMTP connection parameters.
	 * @return array Modified hook arguments with updated SMTP settings.
	 */
	public function on_smtp_connect(array $args): array
	{
		$iid = $_SESSION['iid' . self::MY_POSTFIX] ?? null;
		if (!is_numeric($iid) || $iid == -1) {
			self::write_log('no identity switch is selected... trying to find related smtp server from the from header');
			$requestFrom = rcube_utils::get_input_value('_from', rcube_utils::INPUT_POST);
			if (empty($requestFrom)) {
				self::write_log('no _from post parameter found... falling back to original default config');
				return $args;
			}

			$iid = intval($requestFrom);
			if ($iid === 0) {
				self::write_log('falling back to original default config as _from post field is no integer: ' . $_POST['_from']);
				return $args;
			}
		}

		$rc = rcmail::get_instance();

		$sql = 'SELECT smtp_host, flags, smtp_port, username, smtp_auth, password FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $iid, $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if (is_array($r)) {
			if (!$r['username']) {
				// Load email from identity
				$sql = 'SELECT email FROM ' . $rc->db->table_name('identities') . ' WHERE identity_id = ?';
				$q = $rc->db->query($sql, $iid);
				$rIid = $rc->db->fetch_assoc($q);
				$r['username'] = $rIid['email'];
			}

			$args['smtp_user'] = $r['username'];
			$args['smtp_pass'] = $r['smtp_auth'] == self::SMTP_AUTH_IMAP ? $rc->decrypt($r['password']) : '';

			// In RC 1.6+ smtp_server was renamed to smtp_host and includes port
			$smtpHost = $r['smtp_host'] ?: 'localhost';

			if ($r['flags'] & self::DB_SECURE_IMAP_TLS) {
				if (str_contains($smtpHost, ':')) {
					self::write_log('SMTP server already contains protocol, ignoring TLS flag.');
				} else {
					$smtpHost = 'tls://' . $smtpHost;
				}
			}

			$smtpPort = $r['smtp_port'] ?: 587;
			$args['smtp_host'] = $smtpHost . ':' . $smtpPort;
		}

		return $args;
	}

	/**
	 * Build the common form fields for identity settings.
	 *
	 * @param array $record Identity record data used for placeholders.
	 * @return array Form field definitions for enabled, label, and readonly.
	 */
	private static function get_common_form(array &$record): array
	{
		$prefix = 'ident_switch.form.common.';
		return [
			$prefix . 'enabled' => ['type' => 'checkbox', 'onchange' => 'plugin_switchIdent_enabled_onChange();'],
			$prefix . 'label' => ['type' => 'text', 'size' => 32, 'placeholder' => $record['email']],
			$prefix . 'readonly' => ['type' => 'hidden'],
		];
	}

	/**
	 * Build the IMAP form fields for identity settings.
	 *
	 * @param array $record Identity record data used for placeholders.
	 * @return array Form field definitions for IMAP host, port, TLS, username, password, delimiter.
	 */
	private static function get_imap_form(array &$record): array
	{
		$prefix = 'ident_switch.form.imap.';
		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 143],
			$prefix . 'tls' => ['type' => 'checkbox'],
			$prefix . 'username' => ['type' => 'text', 'size' => 64, 'placeholder' => $record['email']],
			$prefix . 'password' => ['type' => 'password', 'size' => 64],
			$prefix . 'delimiter' => ['type' => 'text', 'size' => 1, 'placeholder' => '.'],
		];
	}

	/**
	 * Build the SMTP form fields for identity settings.
	 *
	 * @param array $record Identity record data used for default auth type selection.
	 * @return array Form field definitions for SMTP host, port, and auth type.
	 */
	private function get_smtp_form(array &$record): array
	{
		$prefix = 'ident_switch.form.smtp.';

		$authType = new html_select(['name' => "_{$prefix}auth"]);
		$authType->add($this->gettext('form.smtp.auth.imap'), self::SMTP_AUTH_IMAP);
		$authType->add($this->gettext('form.smtp.auth.none'), self::SMTP_AUTH_NONE);

		return [
			$prefix . 'host' => ['type' => 'text', 'size' => 64, 'placeholder' => 'localhost'],
			$prefix . 'port' => ['type' => 'text', 'size' => 5, 'placeholder' => 587],
			$prefix . 'auth' => ['value' => $authType->show([$record['ident_switch.form.smtp.auth']])],
		];
	}

	/**
	 * Handle identity_form hook: add plugin-specific fields to the identity editor.
	 *
	 * Loads existing account data from the database (if editing) or applies
	 * preconfigured settings, then adds Common/IMAP/SMTP sections to the form.
	 *
	 * @param array $args Hook arguments containing 'record' with identity data.
	 * @return array Modified hook arguments with added form sections.
	 */
	public function on_identity_form(array $args): array
	{
		$rc = rcmail::get_instance();

		// Do not show options for default identity
		if (strcasecmp($args['record']['email'], $rc->user->data['username']) === 0) {
			return $args;
		}

		$this->add_texts('localization');

		$row = null;
		if (isset($args['record']['identity_id'])) {
			$sql = 'SELECT * FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
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
			];
			foreach ($row as $k => $v) {
				if (isset($dbToForm[$k])) {
					$record['ident_switch.form.' . $dbToForm[$k]] = $v;
				}
			}

			// Parse flags
			$record['ident_switch.form.common.enabled'] = (bool)($row['flags'] & self::DB_ENABLED);
			$record['ident_switch.form.imap.tls'] = (bool)($row['flags'] & self::DB_SECURE_IMAP_TLS);

			// Set readonly if needed
			$cfg = $this->get_preconfig($record['email']);
			if (is_array($cfg) && ($cfg['readonly'] ?? false)) {
				$record['ident_switch.form.common.readonly'] = 1;
				if (in_array(strtoupper($cfg['user'] ?? ''), ['EMAIL', 'MBOX'])) {
					$record['ident_switch.form.common.readonly'] = 2;
				}
			}
		} else {
			$this->apply_preconfig($record);
		}

		$args['form']['ident_switch.common'] = [
			'name' => $this->gettext('form.common.caption'),
			'content' => self::get_common_form($record),
		];
		$args['form']['ident_switch.imap'] = [
			'name' => $this->gettext('form.imap.caption'),
			'content' => self::get_imap_form($record),
		];
		$args['form']['ident_switch.smtp'] = [
			'name' => $this->gettext('form.smtp.caption'),
			'content' => $this->get_smtp_form($record),
		];

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
			self::sw_imap_off($args['id']);
			return $args;
		}

		$data = self::check_field_values();
		if ($data['err']) {
			$this->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $data['err'];
			return $args;
		}

		$data['id'] = $args['id'];
		self::save_field_values($rc, $data);

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

		$data = self::check_field_values();
		if ($data['err']) {
			$this->add_texts('localization');
			$args['abort'] = true;
			$args['message'] = 'ident_switch.err.' . $data['err'];
		}

		// Save data for _after (cannot pass with $args)
		$_SESSION['createData' . self::MY_POSTFIX] = $data;

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

		$data = $_SESSION['createData' . self::MY_POSTFIX] ?? null;

		unset($_SESSION['createData' . self::MY_POSTFIX]);
		if (!$data || count($data) === 0) {
			self::write_log("Object with ident_switch values not found in session for ID = {$args['id']}.");
		} else {
			$data['id'] = $args['id'];
			self::save_field_values($rc, $data);
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

		$sql = 'DELETE FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $args['id'], $rc->user->ID);

		if ($rc->db->affected_rows($q)) {
			self::write_log("Deleted associated information for identity with ID = {$args['id']}.");
		}

		return $args;
	}

	/**
	 * Handle template_object_composeheaders hook: fix identity selection in compose view.
	 *
	 * When impersonating, pre-selects the correct identity in the "From" dropdown.
	 *
	 * @param array $args Hook arguments containing form element 'id'.
	 */
	public function on_template_object_composeheaders(array $args): void
	{
		if ($args['id'] === '_from') {
			$rc = rcmail::get_instance();
			if (strcasecmp($_SESSION['username'], $rc->user->data['username']) !== 0) {
				if (isset($_SESSION['iid' . self::MY_POSTFIX])) {
					$iid = $_SESSION['iid' . self::MY_POSTFIX];
					$rc->output->add_script("plugin_switchIdent_fixIdent({$iid});", 'docready');
				} else {
					self::write_log('Special session variable with active identity ID not found.');
				}
			}
		}
	}

	/**
	 * Handle preferences_list hook: customize special folders form for remote accounts.
	 *
	 * When viewing folder preferences while impersonating, shows the remote account's
	 * special folder assignments instead of the default ones.
	 *
	 * @param array $args Hook arguments containing 'section' and 'blocks' with form data.
	 * @return array Modified hook arguments with updated folder selections.
	 */
	public function on_special_folders_form(array $args): array
	{
		$rc = rcmail::get_instance();

		if ($args['section'] === 'folders'
			&& strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0
		) {
			$no_override = array_flip((array)$rc->config->get('dont_override'));
			$onchange = "if ($(this).val() == 'INBOX') $(this).val('')";
			$select = $rc->folder_selector([
				'noselection' => '---',
				'realnames' => true,
				'maxlength' => 30,
				'folder_filter' => 'mail',
				'folder_rights' => 'w',
			]);

			$sql = 'SELECT label FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $_SESSION['iid' . self::MY_POSTFIX], $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			$args['blocks']['main']['name'] .= ' (' . ($r['label'] ? rcube::Q($rc->gettext('server')) . ': ' . $r['label'] : 'remote') . ')';

			foreach (rcube_storage::$folder_types as $type) {
				if (isset($no_override[$type . '_mbox'])) {
					continue;
				}

				$defaultKey = $type . '_mbox_default' . self::MY_POSTFIX;
				$otherKey = $type . '_mbox' . self::MY_POSTFIX;
				$selected = $_SESSION[$otherKey] ?? $_SESSION[$defaultKey] ?? '';
				$attr = ['id' => '_' . $type . '_mbox', 'name' => '_' . $type . '_mbox', 'onchange' => $onchange];
				$args['blocks']['main']['options'][$type . '_mbox']['content'] = $select->show($selected, $attr);
			}
		}

		return $args;
	}

	/**
	 * Handle preferences_save hook: persist special folder assignments for remote accounts.
	 *
	 * Saves folder preferences to the plugin's database table instead of the default
	 * Roundcube preferences when impersonating a remote account.
	 *
	 * @param array $args Hook arguments containing 'section' and 'prefs' with folder data.
	 * @return array Modified hook arguments, with 'abort' set to prevent default save.
	 */
	public function on_special_folders_update(array $args): array
	{
		$rc = rcmail::get_instance();

		if ($args['section'] === 'folders'
			&& strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0
		) {
			$sql = 'SELECT id FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $_SESSION['iid' . self::MY_POSTFIX], $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			if ($r) {
				$sql = 'UPDATE ' .
					$rc->db->table_name(self::TABLE) .
					' SET drafts_mbox = ?, sent_mbox = ?, junk_mbox = ?, trash_mbox = ?' .
					' WHERE id = ?';

				$rc->db->query(
					$sql,
					$args['prefs']['drafts_mbox'],
					$args['prefs']['sent_mbox'],
					$args['prefs']['junk_mbox'],
					$args['prefs']['trash_mbox'],
					$r['id']
				);

				// Abort to prevent RC from saving prefs to default storage
				$args['abort'] = true;
				$args['result'] = true;

				foreach (rcube_storage::$folder_types as $type) {
					if (!empty($args['prefs'][$type . '_mbox'])) {
						$otherKey = $type . '_mbox' . self::MY_POSTFIX;
						$_SESSION[$otherKey] = $args['prefs'][$type . '_mbox'];
					}
				}
				return $args;
			}

			$args['abort'] = true;
			$args['result'] = false;
			return $args;
		}

		foreach (rcube_storage::$folder_types as $type) {
			if (!empty($args['prefs'][$type . '_mbox'])) {
				$key = $type . '_mbox_default' . self::MY_POSTFIX;
				$_SESSION[$key] = $args['prefs'][$type . '_mbox'];
			}
		}
		return $args;
	}

	/**
	 * Validate all plugin form field values from POST data.
	 *
	 * Checks length constraints, port ranges, and parses flags.
	 * Also retrieves and includes the password.
	 *
	 * @return array Validated data with field values and optional 'err' key on failure.
	 */
	private static function check_field_values(): array
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

		// Get also password
		$retVal['imap.pass'] = self::get_field_value('imap', 'password', false, true);

		// Parse secure settings
		$retVal['flags'] = self::DB_ENABLED;

		$tls = self::get_field_value('imap', 'tls', false);
		if ($tls) {
			$retVal['flags'] |= self::DB_SECURE_IMAP_TLS;
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
	private static function get_field_value(string $section, string $field, bool $trim = true, bool $html = false): ?string
	{
		$retVal = rcube_utils::get_input_value(
			"_ident_switch_form_{$section}_{$field}",
			rcube_utils::INPUT_POST,
			$html
		);
		if (!$trim) {
			return $retVal;
		}

		return self::ntrim($retVal);
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
	private static function save_field_values(rcmail $rc, array $data): bool
	{
		$sql = 'SELECT id, password FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $data['id'], $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if ($r) {
			// Record already exists, will update it
			$sql = 'UPDATE ' .
				$rc->db->table_name(self::TABLE) .
				' SET flags = ?, label = ?, imap_host = ?, imap_port = ?, imap_delimiter = ?, username = ?, password = ?, smtp_host = ?, smtp_port = ?, smtp_auth = ?, user_id = ?, iid = ?' .
				' WHERE id = ?';
		} elseif ($data['flags'] & self::DB_ENABLED) {
			// No record exists, create new one
			$sql = 'INSERT INTO ' .
				$rc->db->table_name(self::TABLE) .
				'(flags, label, imap_host, imap_port, imap_delimiter, username, password, smtp_host, smtp_port, smtp_auth, user_id, iid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
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
			$rc->user->ID,
			$data['id'],
			$r['id'] ?? null
		);

		return true;
	}

	/**
	 * Handle the account switch action (AJAX).
	 *
	 * Saves current account state to session, loads the target account's
	 * IMAP/SMTP configuration, and redirects to INBOX.
	 * When switching back to default (id=-1), restores the original session state.
	 */
	public function on_switch(): void
	{
		$rc = rcmail::get_instance();

		$my_postfix_len = strlen(self::MY_POSTFIX);
		$identId = rcube_utils::get_input_value('_ident-id', rcube_utils::INPUT_POST);

		$rc->session->remove('folders');
		$rc->session->remove('unseen_count');

		if ($identId == -1) {
			// Switch to main account
			self::write_log('Switching mailbox back to default.');

			// Restore everything with STORAGE*my_postfix
			foreach ($_SESSION as $k => $v) {
				if (str_starts_with(strtolower($k), 'storage') && str_ends_with($k, self::MY_POSTFIX)) {
					$realKey = substr($k, 0, -$my_postfix_len);
					$_SESSION[$realKey] = $_SESSION[$k];
					$rc->session->remove($k);
				}
			}

			$_SESSION['imap_delimiter'] = $_SESSION['imap_delimiter' . self::MY_POSTFIX] ?? '.';
			$_SESSION['username'] = $rc->user->data['username'];
			$_SESSION['password'] = $_SESSION['password' . self::MY_POSTFIX];
			$_SESSION['iid' . self::MY_POSTFIX] = -1;

			foreach (rcube_storage::$folder_types as $type) {
				$otherKey = $type . '_mbox' . self::MY_POSTFIX;
				if (isset($_SESSION[$otherKey])) {
					$rc->session->remove($otherKey);
				}
			}
		} else {
			$sql = 'SELECT imap_host, flags, imap_port, imap_delimiter, drafts_mbox, sent_mbox, junk_mbox, trash_mbox, username, password, iid FROM ' . $rc->db->table_name(self::TABLE) . ' WHERE id = ? AND user_id = ?';
			$q = $rc->db->query($sql, $identId, $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			if (is_array($r)) {
				if (!$r['username']) {
					// Load email from identity
					$sql = 'SELECT email FROM ' . $rc->db->table_name('identities') . ' WHERE identity_id = ?';
					$q = $rc->db->query($sql, $r['iid']);
					$rIid = $rc->db->fetch_assoc($q);
					$r['username'] = $rIid['email'];
				}

				self::write_log("Switching mailbox to one for identity with ID = {$r['iid']} (username = '{$r['username']}').");

				if ($_SESSION['username'] === $rc->user->data['username']) {
					// If we are in default account now - save values
					foreach ($_SESSION as $k => $v) {
						if (str_starts_with(strtolower($k), 'storage') && !str_ends_with($k, self::MY_POSTFIX)) {
							if (!isset($_SESSION[$k . self::MY_POSTFIX])) {
								$_SESSION[$k . self::MY_POSTFIX] = $_SESSION[$k];
							}
							$rc->session->remove($k);
						}
					}

					foreach (['password', 'imap_delimiter'] as $k) {
						if (!isset($_SESSION[$k . self::MY_POSTFIX])) {
							$_SESSION[$k . self::MY_POSTFIX] = $_SESSION[$k];
						}
						$rc->session->remove($k);
					}
				}

				$def_port = 143;
				$ssl = null;
				if ($r['flags'] & self::DB_SECURE_IMAP_TLS) {
					$ssl = 'tls';
				}
				$port = $r['imap_port'] ?: $def_port;

				$host = $r['imap_host'] ?: 'localhost';
				if ($ssl && !str_starts_with(strtolower($host), "{$ssl}://")) {
					$host = "{$ssl}://" . $host;
				}

				$delimiter = $r['imap_delimiter'] ?: '.';

				$_SESSION['storage_host'] = $host;
				$_SESSION['storage_ssl'] = $ssl;
				$_SESSION['storage_port'] = $port;
				$_SESSION['imap_delimiter'] = $delimiter;
				$_SESSION['username'] = $r['username'];
				$_SESSION['password'] = $r['password'];
				$_SESSION['iid' . self::MY_POSTFIX] = $r['iid'];

				foreach (rcube_storage::$folder_types as $type) {
					if (!empty($r[$type . '_mbox'])) {
						$otherKey = $type . '_mbox' . self::MY_POSTFIX;
						$_SESSION[$otherKey] = $r[$type . '_mbox'];
					}
				}
			} else {
				self::write_log("Requested remote mailbox with ID = {$identId} not found.");
				return;
			}
		}

		$rc->output->redirect([
			'_task' => 'mail',
			'_mbox' => 'INBOX',
		]);
	}

	/**
	 * Disable the ident_switch flag for a given identity.
	 *
	 * @param int $iid Identity ID to disable.
	 */
	private static function sw_imap_off(int $iid): void
	{
		$rc = rcmail::get_instance();

		$sql = 'UPDATE ' . $rc->db->table_name(self::TABLE) . ' SET flags = flags & ? WHERE iid = ? AND user_id = ?';
		$rc->db->query($sql, ~self::DB_ENABLED, $iid, $rc->user->ID);
	}

	/**
	 * Load preconfigured settings for a domain from config.
	 *
	 * @param string $email Email address to extract domain from.
	 * @return array|false Preconfig array for the domain, or false if not found.
	 */
	private function get_preconfig(string $email): array|false
	{
		$dom = substr(strstr($email, '@'), 1);
		if (!$dom) {
			return false;
		}

		$this->load_config();

		$cfg = rcmail::get_instance()->config->get('ident_switch.preconfig', []);
		$cfg = $cfg[$dom] ?? null;

		if ($cfg) {
			if (empty($cfg['host'])) {
				return false;
			}
		}
		return $cfg ?: false;
	}

	/**
	 * Apply preconfigured settings to an identity form record.
	 *
	 * Parses the host URL to extract scheme, host, and port, then sets
	 * the username based on the config's 'user' setting (email or mbox).
	 *
	 * @param array $record Identity record to modify (passed by reference).
	 * @return bool True if the preconfig is readonly, false otherwise.
	 */
	private function apply_preconfig(array &$record): bool
	{
		$email = $record['email'];
		$cfg = $this->get_preconfig($email);
		if (is_array($cfg)) {
			self::write_log("Applying predefined configuration for '{$email}'.");

			if (!empty($cfg['host'])) {
				// Parse and set host and related
				$urlArr = parse_url($cfg['host']);

				$record['ident_switch.form.imap.host'] = $record['ident_switch.form.smtp.host'] = !empty($urlArr['host']) ? rcube::Q($urlArr['host'], 'url') : '';
				$record['ident_switch.form.imap.port'] = $record['ident_switch.form.smtp.port'] = !empty($urlArr['port']) ? intval($urlArr['port']) : '';

				$record['ident_switch.form.imap.tls'] = strcasecmp($urlArr['scheme'] ?? '', 'tls') === 0;
			}

			$loginSet = false;
			if (!empty($cfg['user'])) {
				match (strtoupper($cfg['user'])) {
					'EMAIL' => ($record['ident_switch.form.imap.username'] = $email) && ($loginSet = true),
					'MBOX' => ($record['ident_switch.form.imap.username'] = strstr($email, '@', true)) && ($loginSet = true),
					default => null,
				};
			}

			if (!empty($cfg['readonly'])) {
				$record['ident_switch.form.common.readonly'] = $loginSet ? 2 : 1;
			}

			return (bool)($cfg['readonly'] ?? false);
		}

		return false;
	}

	/**
	 * Trim a string, returning null if the result is empty.
	 *
	 * @param string|null $str Input string.
	 * @return string|null Trimmed string or null if empty.
	 */
	private static function ntrim(?string $str): ?string
	{
		if ($str === null) {
			return null;
		}

		$s = trim($str);
		return $s !== '' ? $s : null;
	}

	/**
	 * Write a message to the plugin's log file.
	 *
	 * @param string $txt Log message.
	 */
	private static function write_log(string $txt): void
	{
		rcmail::get_instance()->write_log('ident_switch', $txt);
	}
}
