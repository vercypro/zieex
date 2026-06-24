<?php
use Zieex\Database\DB;

return new class {
    public function up(): void
    {
        DB::raw("CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            created_at DATETIME,
            updated_at DATETIME
        )");

        DB::raw("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255),
            ran_at DATETIME
        )");
    }

    public function down(): void
    {
        DB::raw("DROP TABLE IF EXISTS users");
        DB::raw("DROP TABLE IF EXISTS migrations");
    }
};
