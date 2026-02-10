<?php
/**
 * ident_switch - Account switching handler.
 *
 * Manages switching between mail accounts, SMTP connection
 * configuration, and special folder assignments.
 *
 * Copyright (C) 2016-2022 Boris Gulay
 * Copyright (C) 2019      Christian Landvogt
 * Copyright (C) 2022      Mickael
 * Copyright (C) 2026      Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */
class IdentSwitchSwitcher
{
	/**
	 * Handle the account switch action (AJAX).
	 *
	 * Saves current account state to session, loads the target account's
	 * IMAP/SMTP configuration, and redirects to INBOX.
	 * When switching back to default (id=-1), restores the original session state.
	 */
	public function switch_account(): void
	{
		$rc = rcmail::get_instance();

		$my_postfix_len = strlen(ident_switch::MY_POSTFIX);
		$identId = (int)rcube_utils::get_input_value('_ident-id', rcube_utils::INPUT_POST);

		$rc->session->remove('folders');
		$rc->session->remove('unseen_count');

		// Reset baseline for the target account so delta goes back to 0
		$this->reset_baseline($identId === -1 ? 0 : null, $rc, $identId);

		if ($identId === -1) {
			// Switch to main account
			ident_switch::write_log('Switching mailbox back to default.');

			// Restore everything with STORAGE*my_postfix
			foreach ($_SESSION as $k => $v) {
				if (str_starts_with(strtolower($k), 'storage') && str_ends_with($k, ident_switch::MY_POSTFIX)) {
					$realKey = substr($k, 0, -$my_postfix_len);
					$_SESSION[$realKey] = $_SESSION[$k];
					$rc->session->remove($k);
				}
			}

			$_SESSION['imap_delimiter'] = $_SESSION['imap_delimiter' . ident_switch::MY_POSTFIX] ?? null;
			$_SESSION['username'] = $rc->user->data['username'];
			$_SESSION['password'] = $_SESSION['password' . ident_switch::MY_POSTFIX];
			$_SESSION['iid' . ident_switch::MY_POSTFIX] = -1;

			foreach (rcube_storage::$folder_types as $type) {
				$otherKey = $type . '_mbox' . ident_switch::MY_POSTFIX;
				if (isset($_SESSION[$otherKey])) {
					$rc->session->remove($otherKey);
				}
			}
		} else {
			$sql = 'SELECT imap_host, flags, imap_port, imap_delimiter, drafts_mbox, sent_mbox, junk_mbox, trash_mbox, username, password, iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE id = ? AND user_id = ?';
			$q = $rc->db->query($sql, $identId, $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			if (is_array($r)) {
				$r['username'] = ident_switch::resolve_username((int)$r['iid'], $r['username']);

				ident_switch::write_log("Switching mailbox to one for identity with ID = {$r['iid']} (username = '{$r['username']}').");

				if ($_SESSION['username'] === $rc->user->data['username']) {
					// If we are in default account now - save values
					foreach ($_SESSION as $k => $v) {
						if (str_starts_with(strtolower($k), 'storage') && !str_ends_with($k, ident_switch::MY_POSTFIX)) {
							if (!isset($_SESSION[$k . ident_switch::MY_POSTFIX])) {
								$_SESSION[$k . ident_switch::MY_POSTFIX] = $_SESSION[$k];
							}
							$rc->session->remove($k);
						}
					}

					foreach (['password', 'imap_delimiter'] as $k) {
						if (!isset($_SESSION[$k . ident_switch::MY_POSTFIX])) {
							$_SESSION[$k . ident_switch::MY_POSTFIX] = $_SESSION[$k];
						}
						$rc->session->remove($k);
					}
				}

				$parsed = ident_switch::parse_host_scheme($r['imap_host'] ?: 'localhost');
				$host = $parsed['host'];
				$ssl = $parsed['scheme'] ?: null;

				if (!$ssl && ($r['flags'] & ident_switch::DB_SECURE_IMAP_TLS)) {
					$ssl = 'tls'; // Backward compat: old records without scheme in host
				}

				$def_port = ($ssl === 'ssl') ? 993 : 143;
				$port = $r['imap_port'] ?: $def_port;

				$delimiter = $r['imap_delimiter'] ?: null;

				$_SESSION['storage_host'] = $host;
				$_SESSION['storage_ssl'] = $ssl;
				$_SESSION['storage_port'] = $port;
				$_SESSION['imap_delimiter'] = $delimiter;
				$_SESSION['username'] = $r['username'];
				$_SESSION['password'] = $r['password'];
				$_SESSION['iid' . ident_switch::MY_POSTFIX] = $r['iid'];

				foreach (rcube_storage::$folder_types as $type) {
					if (!empty($r[$type . '_mbox'])) {
						$otherKey = $type . '_mbox' . ident_switch::MY_POSTFIX;
						$_SESSION[$otherKey] = $r[$type . '_mbox'];
					}
				}
			} else {
				ident_switch::write_log("Requested remote mailbox with ID = {$identId} not found.");
				return;
			}
		}

		$rc->output->redirect([
			'_task' => 'mail',
			'_mbox' => 'INBOX',
		]);
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
	public function configure_smtp(array $args): array
	{
		$iid = $_SESSION['iid' . ident_switch::MY_POSTFIX] ?? null;
		if (!is_numeric($iid) || (int)$iid === -1) {
			ident_switch::debug_log('SMTP: no active switch, resolving from _from header');
			$requestFrom = rcube_utils::get_input_value('_from', rcube_utils::INPUT_POST);
			if (empty($requestFrom)) {
				ident_switch::debug_log('SMTP: no _from parameter, using default config');
				return $args;
			}

			$iid = intval($requestFrom);
			if ($iid === 0) {
				ident_switch::debug_log('SMTP: _from is not an integer, using default config');
				return $args;
			}
		}

		$rc = rcmail::get_instance();

		$sql = 'SELECT parent_id, smtp_host, smtp_port, username, smtp_auth, smtp_username, smtp_password, password, iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $iid, $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if (is_array($r)) {
			// If this is an alias, follow parent_id to get the parent's SMTP config
			if (!empty($r['parent_id'])) {
				ident_switch::debug_log("SMTP: identity {$iid} is alias, following parent_id={$r['parent_id']}");
				$sql = 'SELECT smtp_host, smtp_port, username, smtp_auth, smtp_username, smtp_password, password, iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE id = ? AND user_id = ?';
				$q = $rc->db->query($sql, $r['parent_id'], $rc->user->ID);
				$r = $rc->db->fetch_assoc($q);
				if (!is_array($r)) {
					ident_switch::debug_log("SMTP: parent account not found, using default config");
					return $args;
				}
				$iid = $r['iid'];
			}

			$r['username'] = ident_switch::resolve_username($iid, $r['username']);

			$authMode = (int)$r['smtp_auth'];
			if ($authMode === ident_switch::SMTP_AUTH_CUSTOM) {
				$args['smtp_user'] = $r['smtp_username'] ?: '';
				$args['smtp_pass'] = $r['smtp_password'] ? ($rc->decrypt($r['smtp_password']) ?: '') : '';
			} elseif ($authMode === ident_switch::SMTP_AUTH_IMAP) {
				$args['smtp_user'] = $r['username'];
				$args['smtp_pass'] = $rc->decrypt($r['password']) ?: '';
			} else {
				$args['smtp_user'] = '';
				$args['smtp_pass'] = '';
			}

			// Host already contains scheme (ssl:// or tls://) from form
			$smtpHost = $r['smtp_host'] ?: 'localhost';
			$smtpPort = $r['smtp_port'] ?: 587;
			$args['smtp_host'] = $smtpHost . ':' . $smtpPort;

			$authLabel = match ($authMode) {
				ident_switch::SMTP_AUTH_IMAP => 'imap',
				ident_switch::SMTP_AUTH_NONE => 'none',
				ident_switch::SMTP_AUTH_CUSTOM => 'custom',
				default => "unknown({$authMode})",
			};
			ident_switch::debug_log("SMTP: iid={$iid}, host={$args['smtp_host']}, user={$args['smtp_user']}, auth={$authLabel}");
		}

		return $args;
	}

	/**
	 * Handle managesieve_connect hook: configure Sieve settings for the active account.
	 *
	 * Loads Sieve host, port, and credentials from the database
	 * for the currently selected identity.
	 *
	 * @param array $args Hook arguments containing Sieve connection parameters.
	 * @return array Modified hook arguments with updated Sieve settings.
	 */
	public function configure_managesieve(array $args): array
	{
		$iid = $_SESSION['iid' . ident_switch::MY_POSTFIX] ?? null;
		if (!is_numeric($iid) || (int)$iid === -1) {
			return $args;
		}

		$rc = rcmail::get_instance();

		$sql = 'SELECT parent_id, sieve_host, sieve_port, sieve_auth, sieve_username, sieve_password, username, password, iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
		$q = $rc->db->query($sql, $iid, $rc->user->ID);
		$r = $rc->db->fetch_assoc($q);
		if (is_array($r)) {
			// If this is an alias, follow parent_id to get the parent's Sieve config
			if (!empty($r['parent_id'])) {
				ident_switch::debug_log("Sieve: identity {$iid} is alias, following parent_id={$r['parent_id']}");
				$sql = 'SELECT sieve_host, sieve_port, sieve_auth, sieve_username, sieve_password, username, password, iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE id = ? AND user_id = ?';
				$q = $rc->db->query($sql, $r['parent_id'], $rc->user->ID);
				$r = $rc->db->fetch_assoc($q);
				if (!is_array($r)) {
					ident_switch::debug_log("Sieve: parent account not found, using default config");
					return $args;
				}
				$iid = $r['iid'];
			}

			if (empty($r['sieve_host'])) {
				return $args;
			}

			$r['username'] = ident_switch::resolve_username($iid, $r['username']);

			$sieveHost = $r['sieve_host'];
			$sievePort = $r['sieve_port'] ?: 4190;
			$args['host'] = $sieveHost . ':' . $sievePort;

			$authMode = (int)$r['sieve_auth'];
			if ($authMode === ident_switch::SIEVE_AUTH_CUSTOM) {
				$args['user'] = $r['sieve_username'] ?: '';
				$args['password'] = $r['sieve_password'] ? ($rc->decrypt($r['sieve_password']) ?: '') : '';
			} elseif ($authMode === ident_switch::SIEVE_AUTH_IMAP) {
				$args['user'] = $r['username'];
				$args['password'] = $rc->decrypt($r['password']) ?: '';
			} else {
				$args['user'] = '';
				$args['password'] = '';
			}

			ident_switch::debug_log("Sieve: iid={$iid}, host={$args['host']}, user={$args['user']}");
		}

		return $args;
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
	public function get_special_folders_form(array $args): array
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

			$sql = 'SELECT label FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $_SESSION['iid' . ident_switch::MY_POSTFIX], $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			$args['blocks']['main']['name'] .= ' (' . ($r['label'] ? rcube::Q($rc->gettext('server')) . ': ' . rcube::Q($r['label']) : 'remote') . ')';

			foreach (rcube_storage::$folder_types as $type) {
				if (isset($no_override[$type . '_mbox'])) {
					continue;
				}

				$defaultKey = $type . '_mbox_default' . ident_switch::MY_POSTFIX;
				$otherKey = $type . '_mbox' . ident_switch::MY_POSTFIX;
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
	public function save_special_folders(array $args): array
	{
		$rc = rcmail::get_instance();

		if ($args['section'] === 'folders'
			&& strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0
		) {
			$sql = 'SELECT id FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE iid = ? AND user_id = ?';
			$q = $rc->db->query($sql, $_SESSION['iid' . ident_switch::MY_POSTFIX], $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			if ($r) {
				$sql = 'UPDATE ' .
					$rc->db->table_name(ident_switch::TABLE) .
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
						$otherKey = $type . '_mbox' . ident_switch::MY_POSTFIX;
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
				$key = $type . '_mbox_default' . ident_switch::MY_POSTFIX;
				$_SESSION[$key] = $args['prefs'][$type . '_mbox'];
			}
		}
		return $args;
	}

	/**
	 * Reset the baseline for a target account so delta display resets to 0.
	 *
	 * For primary account (identId=-1), iid is 0.
	 * For secondary accounts, look up iid from the ident_switch table.
	 *
	 * @param int|null  $iid     Known iid (0 for primary), or null to look up.
	 * @param rcmail    $rc      Roundcube instance.
	 * @param mixed     $identId The ident_switch.id value for secondary accounts.
	 */
	private function reset_baseline(?int $iid, rcmail $rc, mixed $identId): void
	{
		if ($iid === null) {
			// Look up iid from ident_switch table for secondary account
			$sql = 'SELECT iid FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' WHERE id = ? AND user_id = ?';
			$q = $rc->db->query($sql, $identId, $rc->user->ID);
			$r = $rc->db->fetch_assoc($q);
			if (!$r) {
				return;
			}
			$iid = (int)$r['iid'];
		}

		$counts = $_SESSION['ident_switch_counts'] ?? [];
		if (isset($counts[$iid])) {
			unset($counts[$iid]['baseline']);
			$_SESSION['ident_switch_counts'] = $counts;
		}
	}
}
