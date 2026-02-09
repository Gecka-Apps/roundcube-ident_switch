ALTER TABLE `ident_switch`
	MODIFY `password` varchar(255);

ALTER TABLE `ident_switch`
	ADD UNIQUE (`iid`);
