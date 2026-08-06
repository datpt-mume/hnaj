<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tên database được phép chạy test.
     *
     * RefreshDatabase gọi `migrate:fresh`, tức DROP toàn bộ bảng trước khi
     * migrate lại. Nếu cấu hình vô tình trỏ về database production thì toàn
     * bộ dữ liệu sẽ mất. Guard này chặn ở runtime, không phụ thuộc phpunit.xml.
     */
    private const ALLOWED_TEST_DATABASES = ['hnaj_test', ':memory:'];

    /**
     * Chặn NGAY sau khi application khởi tạo và TRƯỚC khi trait setup chạy.
     *
     * `setUpTheTestEnvironment()` gọi `refreshApplication()` rồi mới tới
     * `setUpTraits()` — nơi RefreshDatabase thực thi `migrate:fresh`. Vì vậy
     * guard phải nằm ở đây; đặt trong `setUp()` sau `parent::setUp()` là quá
     * muộn, database đã bị DROP xong rồi.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->guardAgainstProductionDatabase();
    }

    private function guardAgainstProductionDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (in_array($database, self::ALLOWED_TEST_DATABASES, true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Test bị chặn: connection "%s" đang trỏ tới database "%s", không nằm '
            .'trong danh sách cho phép (%s). Test dùng RefreshDatabase sẽ DROP '
            .'toàn bộ bảng. Kiểm tra lại DB_DATABASE trong phpunit.xml.',
            $connection,
            $database === '' ? '(rỗng)' : $database,
            implode(', ', self::ALLOWED_TEST_DATABASES),
        ));
    }
}
