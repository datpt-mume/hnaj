-- Chạy MỘT LẦN khi volume MySQL còn trống (docker-entrypoint-initdb.d).
--
-- MySQL image chỉ tự tạo database trong MYSQL_DATABASE (= hnaj, database
-- production). Test dùng RefreshDatabase => `migrate:fresh` => DROP toàn bộ
-- bảng, nên PHẢI có database riêng; xem phpunit.xml và tests/TestCase.php.
--
-- Cấp quyền cho mọi user ứng dụng do entrypoint tạo (bỏ qua user hệ thống),
-- nhờ vậy không phải hardcode giá trị MYSQL_USER ở đây.
CREATE DATABASE IF NOT EXISTS `hnaj_test`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE grant_hnaj_test()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE u VARCHAR(255);
    DECLARE h VARCHAR(255);
    DECLARE cur CURSOR FOR
        SELECT user, host FROM mysql.user
        WHERE user NOT IN (
            'root', 'mysql.sys', 'mysql.session', 'mysql.infoschema', 'healthchecker'
        );
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    grant_loop: LOOP
        FETCH cur INTO u, h;
        IF done THEN
            LEAVE grant_loop;
        END IF;
        SET @stmt := CONCAT('GRANT ALL PRIVILEGES ON `hnaj_test`.* TO ', QUOTE(u), '@', QUOTE(h));
        PREPARE grant_stmt FROM @stmt;
        EXECUTE grant_stmt;
        DEALLOCATE PREPARE grant_stmt;
    END LOOP;
    CLOSE cur;
END //
DELIMITER ;

CALL grant_hnaj_test();
DROP PROCEDURE grant_hnaj_test;
FLUSH PRIVILEGES;
