-- Upgrade from v4.x to v5.x
-- Increase password column for encrypted values
ALTER TABLE ident_switch
	ALTER COLUMN password TYPE varchar(255);

-- Add unique constraint on identity ID
ALTER TABLE ident_switch
	ADD CONSTRAINT ident_switch_iid_unique UNIQUE (iid);

CREATE INDEX IF NOT EXISTS IX_ident_switch_iid ON ident_switch(iid);

-- Add Sieve support
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

-- Add custom SMTP/Sieve credentials
ALTER TABLE ident_switch ADD COLUMN smtp_username varchar(64) DEFAULT NULL;
ALTER TABLE ident_switch ADD COLUMN smtp_password varchar(255) DEFAULT NULL;
ALTER TABLE ident_switch ADD COLUMN sieve_username varchar(64) DEFAULT NULL;
ALTER TABLE ident_switch ADD COLUMN sieve_password varchar(255) DEFAULT NULL;

-- Add notification settings
ALTER TABLE ident_switch
	ADD notify_check
		smallint
		NOT NULL
		DEFAULT 1;

ALTER TABLE ident_switch
	ADD notify_basic
		smallint
		DEFAULT NULL;

ALTER TABLE ident_switch
	ADD notify_sound
		smallint
		DEFAULT NULL;

ALTER TABLE ident_switch
	ADD notify_desktop
		smallint
		DEFAULT NULL;

-- Add alias support: parent_id links an alias identity to a parent account
ALTER TABLE ident_switch
	ADD parent_id integer DEFAULT NULL;

CREATE INDEX IX_ident_switch_parent_id ON ident_switch(parent_id);
