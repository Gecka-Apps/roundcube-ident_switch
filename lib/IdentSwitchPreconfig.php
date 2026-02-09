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
	public function apply(array &$record): bool
	{
		$email = $record['email'] ?? '';
		if (empty($email)) {
			return false;
		}

		$cfg = $this->get($email);
		if (is_array($cfg)) {
			ident_switch::write_log("Applying predefined configuration for '{$email}'.");

			if (!empty($cfg['host'])) {
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
}
