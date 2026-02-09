PRAGMA foreign_keys=off;
BEGIN TRANSACTION;

ALTER TABLE ident_switch RENAME TO ident_switch_old;

CREATE TABLE ident_switch
(
	id
		integer
		PRIMARY KEY,
	user_id
		integer
		NOT NULL
		REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
	iid
		integer
		NOT NULL
		REFERENCES identities(identity_id) ON DELETE CASCADE ON UPDATE CASCADE
		UNIQUE,
	username
		varchar(64),
	password
		varchar(255),
	imap_host
		varchar(64),
	imap_port
		integer
		CHECK(imap_port > 0 AND imap_port <= 65535),
	imap_delimiter
		char(1),
	label
		varchar(32),
	flags
		integer
		NOT NULL
		DEFAULT(0),
	smtp_host
		varchar(64),
	smtp_port
		integer
		CHECK(smtp_port > 0 AND smtp_port <= 65535),
	smtp_auth
		smallint
		NOT NULL
		DEFAULT 1,
	drafts_mbox
		varchar(64),
	sent_mbox
		varchar(64),
	junk_mbox
		varchar(64),
	trash_mbox
		varchar(64),
	UNIQUE (user_id, label)
);
CREATE INDEX IX_ident_switch_user_id ON ident_switch(user_id);
CREATE INDEX IX_ident_switch_iid ON ident_switch(iid);

INSERT OR ROLLBACK INTO
	ident_switch (
		id, user_id, iid, username, password, imap_host, imap_port,
		imap_delimiter, label, flags, smtp_host, smtp_port, smtp_auth,
		drafts_mbox, sent_mbox, junk_mbox, trash_mbox
	)
SELECT
	id, user_id, iid, username, password, imap_host, imap_port,
	imap_delimiter, label, flags, smtp_host, smtp_port, smtp_auth,
	drafts_mbox, sent_mbox, junk_mbox, trash_mbox
FROM
	ident_switch_old;

DROP TABLE ident_switch_old;

COMMIT;
PRAGMA foreign_keys=on;
