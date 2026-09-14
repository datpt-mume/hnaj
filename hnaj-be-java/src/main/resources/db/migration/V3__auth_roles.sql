-- Reference roles required by auth and bootstrap. No credentials or users are provisioned.
INSERT INTO `roles` (`name`, `description`)
SELECT 'user', 'Người dùng thông thường, có thể bookmark, review, comment.'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'user');
INSERT INTO `roles` (`name`, `description`)
SELECT 'sub_admin', 'Quản lý địa điểm, có quyền quản lý place được gán.'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'sub_admin');
INSERT INTO `roles` (`name`, `description`)
SELECT 'admin', 'Quản trị viên, có toàn quyền hệ thống.'
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'admin');
