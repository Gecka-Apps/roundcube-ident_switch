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

require_once __DIR__ . '/lib/IdentSwitchPreconfig.php';
require_once __DIR__ . '/lib/IdentSwitchForm.php';
require_once __DIR__ . '/lib/IdentSwitchSwitcher.php';

class ident_switch extends rcube_plugin
{
	/** @var string Task regex: active on all tasks except login/logout. */
	public $task = '?(?!login|logout).*';

	/** @var string Database table name for this plugin. */
	public const TABLE = 'ident_switch';

	/** @var string Session variable suffix used to store/restore state. */
	public const MY_POSTFIX = '_iswitch';

	/** @var int Flag: account switching is enabled. */
	public const DB_ENABLED = 1;

	/** @var int Flag: use TLS for IMAP connection. */
	public const DB_SECURE_IMAP_TLS = 4;

	/** @var int SMTP authentication: use same credentials as IMAP. */
	public const SMTP_AUTH_IMAP = 1;

	/** @var int SMTP authentication: no authentication required. */
	public const SMTP_AUTH_NONE = 2;

	/** @var int Sieve authentication: use same credentials as IMAP. */
	public const SIEVE_AUTH_IMAP = 1;

	/** @var int Sieve authentication: no authentication required. */
	public const SIEVE_AUTH_NONE = 2;

	private IdentSwitchForm $form;
	private IdentSwitchSwitcher $switcher;
	private IdentSwitchPreconfig $preconfig;

	/**
	 * Initialize plugin: register hooks, actions, and save default folder config.
	 */
	public function init(): void
	{
		$this->form = new IdentSwitchForm($this);
		$this->switcher = new IdentSwitchSwitcher();
		$this->preconfig = new IdentSwitchPreconfig($this);

		$this->add_hook('startup', [$this, 'on_startup']);
		$this->add_hook('render_page', [$this, 'on_render_page']);
		$this->add_hook('smtp_connect', [$this, 'on_smtp_connect']);
		$this->add_hook('managesieve_connect', [$this, 'on_managesieve_connect']);
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
	 * @param array $args Hook arguments containing SMTP connection parameters.
	 * @return array Modified hook arguments with updated SMTP settings.
	 */
	public function on_smtp_connect(array $args): array
	{
		return $this->switcher->configure_smtp($args);
	}

	/**
	 * Handle managesieve_connect hook: configure Sieve settings for the active account.
	 *
	 * @param array $args Hook arguments containing Sieve connection parameters.
	 * @return array Modified hook arguments with updated Sieve settings.
	 */
	public function on_managesieve_connect(array $args): array
	{
		return $this->switcher->configure_managesieve($args);
	}

	/**
	 * Handle identity_form hook: add plugin-specific fields to the identity editor.
	 *
	 * @param array $args Hook arguments containing 'record' with identity data.
	 * @return array Modified hook arguments with added form sections.
	 */
	public function on_identity_form(array $args): array
	{
		return $this->form->on_identity_form($args, $this->preconfig);
	}

	/**
	 * Handle identity_update hook: validate and save plugin fields on identity edit.
	 *
	 * @param array $args Hook arguments containing 'id' and 'record' with identity data.
	 * @return array Modified hook arguments, with 'abort' set on validation failure.
	 */
	public function on_identity_update(array $args): array
	{
		return $this->form->on_identity_update($args);
	}

	/**
	 * Handle identity_create hook: validate plugin fields before identity creation.
	 *
	 * @param array $args Hook arguments containing 'record' with identity data.
	 * @return array Modified hook arguments, with 'abort' set on validation failure.
	 */
	public function on_identity_create(array $args): array
	{
		return $this->form->on_identity_create($args);
	}

	/**
	 * Handle identity_create_after hook: persist plugin data after identity creation.
	 *
	 * @param array $args Hook arguments containing 'id' (new identity_id) and 'record'.
	 * @return array Unmodified hook arguments.
	 */
	public function on_identity_create_after(array $args): array
	{
		return $this->form->on_identity_create_after($args);
	}

	/**
	 * Handle identity_delete hook: remove plugin data when an identity is deleted.
	 *
	 * @param array $args Hook arguments containing 'id' of the identity being deleted.
	 * @return array Unmodified hook arguments.
	 */
	public function on_identity_delete(array $args): array
	{
		return $this->form->on_identity_delete($args);
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
	 * @param array $args Hook arguments containing 'section' and 'blocks' with form data.
	 * @return array Modified hook arguments with updated folder selections.
	 */
	public function on_special_folders_form(array $args): array
	{
		return $this->switcher->get_special_folders_form($args);
	}

	/**
	 * Handle preferences_save hook: persist special folder assignments for remote accounts.
	 *
	 * @param array $args Hook arguments containing 'section' and 'prefs' with folder data.
	 * @return array Modified hook arguments, with 'abort' set to prevent default save.
	 */
	public function on_special_folders_update(array $args): array
	{
		return $this->switcher->save_special_folders($args);
	}

	/**
	 * Handle the account switch action (AJAX).
	 */
	public function on_switch(): void
	{
		$this->switcher->switch_account();
	}

	/**
	 * Trim a string, returning null if the result is empty.
	 *
	 * @param string|null $str Input string.
	 * @return string|null Trimmed string or null if empty.
	 */
	public static function ntrim(?string $str): ?string
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
	public static function write_log(string $txt): void
	{
		rcmail::get_instance()->write_log('ident_switch', $txt);
	}
}
