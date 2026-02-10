<?php

/**
 * ident_switch - Background mail checker.
 *
 * Checks new mail across secondary identities during the refresh
 * cycle and sends unread counts + notifications to the client.
 * When impersonating a secondary account, also checks the primary account.
 *
 * Copyright (C) 2026 Gecka
 *
 * Licensed under AGPL-3.0+.
 *
 * @url https://github.com/Gecka-apps/ident_switch
 */
class IdentSwitchChecker
{
    /**
     * Called on each refresh cycle.
     *
     * Builds the list of identities to check (excluding the currently active one,
     * including the primary account when impersonating), then checks them
     * in round-robin or all-at-once mode.
     *
     * @param array $args Hook arguments (empty for refresh hook).
     * @return array Unmodified hook arguments.
     */
    public function check_new_mail(array $args): array
    {
        $rc = rcmail::get_instance();

        $identities = $this->get_checkable_identities($rc);

        // Exclude the currently active secondary identity (RC already checks it)
        $activeIid = (int)($_SESSION['iid' . ident_switch::MY_POSTFIX] ?? -1);
        $identities = array_values(array_filter($identities, function ($id) use ($activeIid) {
            return (int)$id['iid'] !== $activeIid;
        }));

        // When impersonating, also check the primary account
        $isImpersonating = strcasecmp($rc->user->data['username'], $_SESSION['username']) !== 0;
        if ($isImpersonating) {
            $primary = $this->get_primary_identity($rc);
            if ($primary) {
                $identities[] = $primary;
            }
        }

        if (empty($identities)) {
            $this->send_counts($rc);
            return $args;
        }

        if ($rc->config->get('ident_switch.round_robin', false)) {
            // Round-robin: check one identity per refresh cycle
            $index = ($_SESSION['ident_switch_check_index'] ?? -1) + 1;
            if ($index >= count($identities)) {
                $index = 0;
            }
            $_SESSION['ident_switch_check_index'] = $index;
            $this->check_identity($rc, $identities[$index]);
        } else {
            foreach ($identities as $identity) {
                $this->check_identity($rc, $identity);
            }
        }

        $this->send_counts($rc);

        return $args;
    }

    /**
     * Check a single identity for unseen messages and notify if new mail.
     *
     * @param rcmail $rc       Roundcube instance.
     * @param array  $identity Identity record from the database.
     */
    private function check_identity(rcmail $rc, array $identity): void
    {
        $counts = $_SESSION['ident_switch_counts'] ?? [];
        $previousCount = $counts[$identity['iid']]['unseen'] ?? 0;

        $count = $this->check_unseen($rc, $identity, $previousCount);

        ident_switch::write_log("Check identity {$identity['iid']} ({$identity['email']}): unseen={$count}, previous={$previousCount}");

        // Set baseline on first check; preserve it across subsequent checks
        $baseline = $counts[$identity['iid']]['baseline'] ?? $count;

        $counts[$identity['iid']] = [
            'unseen' => $count,
            'baseline' => $baseline,
            'checked_at' => time(),
        ];
        $_SESSION['ident_switch_counts'] = $counts;

        if ($count > $previousCount) {
            $this->send_notification($rc, $identity, $count);
        }
    }

    /**
     * Connect to an identity's IMAP server and return INBOX unseen count.
     *
     * @param rcmail  $rc            Roundcube instance.
     * @param array   $identity      Identity DB record.
     * @param integer $previousCount Previous unseen count (returned on error).
     * @return integer Unseen message count.
     */
    private function check_unseen(rcmail $rc, array $identity, int $previousCount): int
    {
        $imap = new rcube_imap_generic();

        $parsed = ident_switch::parse_host_scheme($identity['imap_host'] ?: 'localhost');
        $host = $parsed['host'];
        $ssl = $parsed['scheme'] ?: null;

        if (!$ssl && !empty($identity['flags']) && ($identity['flags'] & ident_switch::DB_SECURE_IMAP_TLS)) {
            $ssl = 'tls'; // Backward compat: old records without scheme in host
        }

        $def_port = ($ssl === 'ssl') ? 993 : 143;
        $port = $identity['imap_port'] ?: $def_port;

        $username = $identity['username'] ?: $identity['email'];
        $password = $rc->decrypt($identity['password']);
        if ($password === false) {
            ident_switch::write_log("Failed to decrypt password for identity {$identity['iid']}");
            return $previousCount;
        }

        $result = $imap->connect($host, $username, $password, [
            'port' => $port,
            'ssl_mode' => $ssl,
            'timeout' => 5,
        ]);

        if (!$result) {
            ident_switch::write_log("Failed to check mail for identity {$identity['iid']}: " . $imap->error);
            return $previousCount;
        }

        $status = $imap->status('INBOX', ['UNSEEN']);
        $unseen = $status['UNSEEN'] ?? 0;

        $imap->closeConnection();

        return $unseen;
    }

    /**
     * Build a virtual identity record for the primary account.
     *
     * When the user has switched to a secondary account, the primary account's
     * connection details are saved in session with the MY_POSTFIX suffix.
     *
     * @param rcmail $rc Roundcube instance.
     * @return array|null Identity-like array, or null if session data is missing.
     */
    private function get_primary_identity(rcmail $rc): ?array
    {
        $postfix = ident_switch::MY_POSTFIX;

        if (!isset($_SESSION['password' . $postfix])) {
            return null;
        }

        $host = $_SESSION['storage_host' . $postfix] ?? 'localhost';
        $port = $_SESSION['storage_port' . $postfix] ?? 143;
        $ssl = $_SESSION['storage_ssl' . $postfix] ?? null;

        // Prepend protocol prefix so check_unseen() can parse it
        if ($ssl === 'ssl' && !str_starts_with(strtolower($host), 'ssl://')) {
            $host = 'ssl://' . $host;
        } elseif ($ssl === 'tls' && !str_starts_with(strtolower($host), 'tls://')) {
            $host = 'tls://' . $host;
        }

        return [
            'iid' => 0,
            'imap_host' => $host,
            'imap_port' => $port,
            'flags' => 0,
            'username' => $rc->user->data['username'],
            'password' => $_SESSION['password' . $postfix],
            'email' => $rc->user->data['username'],
            'label' => $_SESSION['global_alias'] ?? $rc->user->data['username'],
            'notify_basic' => null,
            'notify_sound' => null,
            'notify_desktop' => null,
        ];
    }

    /**
     * Get all enabled identities that have mail checking enabled.
     *
     * @param rcmail $rc Roundcube instance.
     * @return array List of identity records.
     */
    private function get_checkable_identities(rcmail $rc): array
    {
        $sql = 'SELECT isw.iid, isw.imap_host, isw.imap_port, isw.flags, '
            . 'isw.username, isw.password, isw.label, '
            . 'isw.notify_basic, isw.notify_sound, isw.notify_desktop, '
            . 'ii.email '
            . 'FROM ' . $rc->db->table_name(ident_switch::TABLE) . ' isw '
            . 'INNER JOIN ' . $rc->db->table_name('identities') . ' ii ON isw.iid = ii.identity_id '
            . 'WHERE isw.user_id = ? AND isw.flags & ? > 0 AND isw.notify_check = ? AND isw.parent_id IS NULL';

        $q = $rc->db->query($sql, $rc->user->ID, ident_switch::DB_ENABLED, ident_switch::NOTIFY_CHECK_ENABLED);

        $identities = [];
        while ($r = $rc->db->fetch_assoc($q)) {
            $identities[] = $r;
        }

        return $identities;
    }

    /**
     * Send all cached unseen counts to client JS.
     *
     * @param rcmail $rc Roundcube instance.
     */
    private function send_counts(rcmail $rc): void
    {
        $counts = $_SESSION['ident_switch_counts'] ?? [];
        $data = [];
        foreach ($counts as $iid => $info) {
            $data[$iid] = [
                'unseen' => $info['unseen'],
                'baseline' => $info['baseline'] ?? $info['unseen'],
            ];
        }

        $rc->output->command('plugin.ident_switch.update_counts', $data);
    }

    /**
     * Send notification command to client for a specific identity.
     *
     * @param rcmail  $rc       Roundcube instance.
     * @param array   $identity Identity record.
     * @param integer $count    New unseen count.
     */
    private function send_notification(rcmail $rc, array $identity, int $count): void
    {
        $basic = $identity['notify_basic'] ?? $rc->config->get('newmail_notifier_basic', false);
        $sound = $identity['notify_sound'] ?? $rc->config->get('newmail_notifier_sound', false);
        $desktop = $identity['notify_desktop'] ?? $rc->config->get('newmail_notifier_desktop', false);

        $label = $identity['label'] ?: $identity['email'];

        $rc->output->command('plugin.ident_switch.notify', [
            'iid' => $identity['iid'],
            'label' => $label,
            'count' => $count,
            'basic' => (bool)$basic,
            'sound' => (bool)$sound,
            'desktop' => (bool)$desktop,
        ]);
    }
}
