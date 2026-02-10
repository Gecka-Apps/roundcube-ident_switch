-- Upgrade from v4.x to v5.x
-- Increase password column for encrypted values
ALTER TABLE `ident_switch`
	MODIFY `password` varchar(255);

-- Add unique constraint on identity ID
ALTER TABLE `ident_switch`
	ADD UNIQUE (`iid`);

-- Add Sieve support
ALTER TABLE `ident_switch`
	ADD `sieve_host` varchar(64) AFTER `smtp_auth`;

ALTER TABLE `ident_switch`
	ADD `sieve_port`
		int
		CHECK(`sieve_port` > 0 AND `sieve_port` <= 65535)
		AFTER `sieve_host`;

ALTER TABLE `ident_switch`
	ADD `sieve_auth`
		smallint
		NOT NULL
		DEFAULT 1
		AFTER `sieve_port`;

-- Add custom SMTP/Sieve credentials
ALTER TABLE `ident_switch`
	ADD `smtp_username`
		varchar(64)
		DEFAULT NULL
		AFTER `smtp_auth`;

ALTER TABLE `ident_switch`
	ADD `smtp_password`
		varchar(255)
		DEFAULT NULL
		AFTER `smtp_username`;

ALTER TABLE `ident_switch`
	ADD `sieve_username`
		varchar(64)
		DEFAULT NULL
		AFTER `sieve_auth`;

ALTER TABLE `ident_switch`
	ADD `sieve_password`
		varchar(255)
		DEFAULT NULL
		AFTER `sieve_username`;

-- Add notification settings
ALTER TABLE `ident_switch`
	ADD `notify_check`
		smallint
		NOT NULL
		DEFAULT 1
		AFTER `sieve_password`;

ALTER TABLE `ident_switch`
	ADD `notify_basic`
		smallint
		DEFAULT NULL
		AFTER `notify_check`;

ALTER TABLE `ident_switch`
	ADD `notify_sound`
		smallint
		DEFAULT NULL
		AFTER `notify_basic`;

ALTER TABLE `ident_switch`
	ADD `notify_desktop`
		smallint
		DEFAULT NULL
		AFTER `notify_sound`;

-- Add alias support: parent_id links an alias identity to a parent account
ALTER TABLE `ident_switch`
	ADD `parent_id`
		int UNSIGNED
		DEFAULT NULL
		AFTER `iid`;

ALTER TABLE `ident_switch`
	ADD INDEX `IX_ident_switch_parent_id` (`parent_id`);
