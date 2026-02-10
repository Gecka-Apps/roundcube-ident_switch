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

    /**
     * Constructor.
     *
     * @param ident_switch $plugin Parent plugin instance.
     */
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
        $cfg = $cfg[$dom] ?? $cfg['*'] ?? null;

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
     * Parses IMAP, SMTP, and Sieve host URLs to extract scheme, host,
     * and port, then sets the username and delimiter based on config values.
     *
     * @param array $record Identity record to modify (passed by reference).
     * @return boolean True if the preconfig is readonly, false otherwise.
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

            // Parse each protocol URL into host, security, and port
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
                $host = !empty($urlArr['host']) ? rcube::Q($urlArr['host'], 'url') : '';
                $scheme = strtolower($urlArr['scheme'] ?? '');

                $record["ident_switch.form.{$proto}.host"] = $host;
                $record["ident_switch.form.{$proto}.security"] = in_array($scheme, ['ssl', 'tls']) ? $scheme : '';
                $record["ident_switch.form.{$proto}.port"] = !empty($urlArr['port']) ? intval($urlArr['port']) : '';
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

            // IMAP folder hierarchy delimiter (null or absent = auto-detect)
            if (isset($cfg['delimiter'])) {
                $record['ident_switch.form.imap.delimiter'] = $cfg['delimiter'];
            }

        // Notification defaults from preconfig
            if (isset($cfg['notify_check'])) {
                $record['ident_switch.form.notify.check'] = $cfg['notify_check'] ? 1 : 0;
            }
            foreach (['notify_basic', 'notify_sound', 'notify_desktop'] as $key) {
                if (isset($cfg[$key])) {
                    $formKey = 'ident_switch.form.notify.' . substr($key, 7);
                    $record[$formKey] = $cfg[$key] === null ? '' : ($cfg[$key] ? '1' : '0');
                }
            }

            return (bool)($cfg['readonly'] ?? false);
        }

        return false;
    }
}
