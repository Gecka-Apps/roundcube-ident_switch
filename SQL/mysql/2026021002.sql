ALTER TABLE `ident_switch`
	ADD `notify_check`
		smallint
		NOT NULL
		DEFAULT 1
		AFTER `sieve_auth`;

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

ALTER TABLE `ident_switch`
	ADD `notify_sound_url`
		varchar(255)
		DEFAULT NULL
		AFTER `notify_desktop`;
