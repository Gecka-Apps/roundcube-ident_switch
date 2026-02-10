-- Add alias support: parent_id links an alias identity to a parent account
ALTER TABLE `ident_switch`
	ADD `parent_id`
		int UNSIGNED
		DEFAULT NULL
		AFTER `iid`;

ALTER TABLE `ident_switch`
	ADD INDEX `IX_ident_switch_parent_id` (`parent_id`);
