ALTER TABLE ident_switch
	ALTER COLUMN password TYPE varchar(255);

CREATE INDEX IF NOT EXISTS IX_ident_switch_iid ON ident_switch(iid);
