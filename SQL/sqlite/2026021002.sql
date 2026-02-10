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

ALTER TABLE ident_switch
	ADD notify_sound_url
		varchar(255)
		DEFAULT NULL;
