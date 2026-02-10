<?php
/**
 * ident_switch - Preconfiguration handler.
 *
 * Loads and applies domain-based preconfigured mail settings
 * from the plugin configuration file.
 *
 * Copyright (C) 2016-2022 Boris Gulay
 * Copyright (C) 2026      Gecka
 *
 * Original code licensed under GPL-3.0+.
 * New contributions licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */
class IdentSwitchPreconfig
{
	private ident_switch $plugin;

	public function __construct(ident_switch $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * Load preconfigured settings for a domain from config.
	 *
	 * @param string $email Email address to extract domain from.
	 * @return array|false Preconfig array for the domain, or false if not found.
	 */
	public function get(string $email): array|false
	{
		$dom = substr(strstr($email, '@'), 1);
		if (!$dom) {
			return false;
		}

		$this->plugin->load_config();

		$cfg = rcmail::get_instance()->config->get('ident_switch.preconfig', []);
		$cfg = $cfg[$dom] ?? null;

		if ($cfg) {
			if (empty($cfg['imap_host']) && empty($cfg['host'])) {
				return false;
			}
		}
		return $cfg ?: false;
	}

	/**
	 * Apply preconfigured settings to an identity form record.
	 *
	 * Parses IMAP and SMTP host URLs separately to extract scheme, host,
	 * and port, then sets the username based on the config's 'user' setting.
	 *
	 * Supports both new format (imap_host + smtp_host) and legacy format
	 * (single host for both). Schemes ssl:// and tls:// are handled:
	 * - IMAP: ssl:// is stored in host field, tls:// sets the TLS checkbox.
	 * - SMTP: scheme is stored directly in host field.
	 *
	 * @param array $record Identity record to modify (passed by reference).
	 * @return bool True if the preconfig is readonly, false otherwise.
	 */
	public function apply(array &$record): bool
	{
		$email = $record['email'] ?? '';
		if (empty($email)) {
			return false;
		}

		$cfg = $this->get($email);
		if (is_array($cfg)) {
			ident_switch::write_log("Applying predefined configuration for '{$email}'.");

			// IMAP: use imap_host, fallback to host
			$imapUrl = $cfg['imap_host'] ?? $cfg['host'] ?? '';
			if (!empty($imapUrl)) {
				$urlArr = parse_url($imapUrl);
				$host = !empty($urlArr['host']) ? rcube::Q($urlArr['host'], 'url') : '';
				$scheme = strtolower($urlArr['scheme'] ?? '');

				if ($scheme === 'ssl') {
					$record['ident_switch.form.imap.host'] = 'ssl://' . $host;
					$record['ident_switch.form.imap.tls'] = false;
				} elseif ($scheme === 'tls') {
					$record['ident_switch.form.imap.host'] = $host;
					$record['ident_switch.form.imap.tls'] = true;
				} else {
					$record['ident_switch.form.imap.host'] = $host;
					$record['ident_switch.form.imap.tls'] = false;
				}

				$record['ident_switch.form.imap.port'] = !empty($urlArr['port']) ? intval($urlArr['port']) : '';
			}

			// SMTP: use smtp_host, fallback to host
			$smtpUrl = $cfg['smtp_host'] ?? $cfg['host'] ?? '';
			if (!empty($smtpUrl)) {
				$urlArr = parse_url($smtpUrl);
				$host = !empty($urlArr['host']) ? rcube::Q($urlArr['host'], 'url') : '';
				$scheme = strtolower($urlArr['scheme'] ?? '');

				if ($scheme === 'tls' || $scheme === 'ssl') {
					$record['ident_switch.form.smtp.host'] = $scheme . '://' . $host;
				} else {
					$record['ident_switch.form.smtp.host'] = $host;
				}

				$record['ident_switch.form.smtp.port'] = !empty($urlArr['port']) ? intval($urlArr['port']) : '';
			}

			// Sieve: use sieve_host only (no fallback — sieve is optional)
			$sieveUrl = $cfg['sieve_host'] ?? '';
			if (!empty($sieveUrl)) {
				$urlArr = parse_url($sieveUrl);
				$host = !empty($urlArr['host']) ? rcube::Q($urlArr['host'], 'url') : '';
				$scheme = strtolower($urlArr['scheme'] ?? '');

				if ($scheme === 'tls' || $scheme === 'ssl') {
					$record['ident_switch.form.sieve.host'] = $scheme . '://' . $host;
				} else {
					$record['ident_switch.form.sieve.host'] = $host;
				}

				$record['ident_switch.form.sieve.port'] = !empty($urlArr['port']) ? intval($urlArr['port']) : '';
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
}
