-- Add alias support: parent_id links an alias identity to a parent account
ALTER TABLE ident_switch
	ADD parent_id integer DEFAULT NULL;

CREATE INDEX IX_ident_switch_parent_id ON ident_switch(parent_id);
