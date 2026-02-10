ALTER TABLE ident_switch
	ADD sieve_host varchar(64);

ALTER TABLE ident_switch
	ADD sieve_port
		integer
		CHECK(sieve_port > 0 AND sieve_port <= 65535);

ALTER TABLE ident_switch
	ADD sieve_auth
		smallint
		NOT NULL
		DEFAULT(1);
