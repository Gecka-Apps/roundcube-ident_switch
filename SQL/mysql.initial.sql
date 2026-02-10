CREATE TABLE IF NOT EXISTS `ident_switch`
(
	`id`
		int UNSIGNED
		NOT NULL
		AUTO_INCREMENT,
	`user_id`
		int UNSIGNED
		NOT NULL,
	`iid`
		int UNSIGNED
		NOT NULL
		UNIQUE,
	`parent_id`
		int UNSIGNED
		DEFAULT NULL,
	`username`
		varchar(64),
	`password`
		varchar(255),
	`imap_host`
		varchar(64),
	`imap_port`
		int
		CHECK(`imap_port` > 0 AND `imap_port` <= 65535),
	`imap_delimiter`
		char(1),
	`label`
		varchar(32),
	`flags`
		int
		NOT NULL
		DEFAULT 0,
	`smtp_host`
		varchar(64),
	`smtp_port`
		int
		CHECK(`smtp_port` > 0 AND `smtp_port` <= 65535),
	`smtp_auth`
		smallint
		NOT NULL
		DEFAULT 1,
	`smtp_username`
		varchar(64),
	`smtp_password`
		varchar(255),
	`sieve_host`
		varchar(64),
	`sieve_port`
		int
		CHECK(`sieve_port` > 0 AND `sieve_port` <= 65535),
	`sieve_auth`
		smallint
		NOT NULL
		DEFAULT 1,
	`sieve_username`
		varchar(64),
	`sieve_password`
		varchar(255),
	`notify_check`
		smallint
		NOT NULL
		DEFAULT 1,
	`notify_basic`
		smallint
		DEFAULT NULL,
	`notify_sound`
		smallint
		DEFAULT NULL,
	`notify_desktop`
		smallint
		DEFAULT NULL,
	`drafts_mbox`
		varchar(64),
	`sent_mbox`
		varchar(64),
	`junk_mbox`
		varchar(64),
	`trash_mbox`
		varchar(64),
	UNIQUE KEY `user_id_label` (`user_id`, `label`),
	CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT `fk_identity_id` FOREIGN KEY (`iid`) REFERENCES `identities`(`identity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY(`id`),
	INDEX `IX_ident_switch_user_id`(`user_id`),
	INDEX `IX_ident_switch_iid`(`iid`),
	INDEX `IX_ident_switch_parent_id`(`parent_id`)
);
