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
