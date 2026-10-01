<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MariaDbSchemaTest extends TestCase
{
    public function test_test_database_contains_the_imported_schema(): void
    {
        $database = DB::selectOne('SELECT DATABASE() AS databaseName');

        $this->assertSame('truyxuatthuoc_test', $database->databaseName);
        $this->assertSame(23, (int) DB::scalar(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
        ));
        $this->assertSame(37, (int) DB::scalar(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        ));
        $this->assertSame(8, (int) DB::scalar(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'CHECK'"
        ));
    }
}
