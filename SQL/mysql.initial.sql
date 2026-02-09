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
	INDEX `IX_ident_switch_iid`(`iid`)
);
