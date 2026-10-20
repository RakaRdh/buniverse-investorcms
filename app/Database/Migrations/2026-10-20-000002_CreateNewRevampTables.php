<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewRevampTables extends Migration
{
    public function up()
    {
        // 1. tblarticlepoints
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblarticlepoints` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT NOT NULL,
            `point_text` TEXT NOT NULL,
            `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `created_at` DATETIME NULL,
            INDEX `idx_points_article` (`article_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. tblarticleheadlinehistory
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblarticleheadlinehistory` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `articleID` INT NOT NULL,
            `category` VARCHAR(50) NOT NULL DEFAULT 'home',
            `action` VARCHAR(20) NOT NULL,
            `position` TINYINT NULL DEFAULT NULL,
            `user` VARCHAR(100) NOT NULL DEFAULT 'system',
            `timestamp` DATETIME NOT NULL,
            `notes` VARCHAR(255) NULL DEFAULT NULL,
            INDEX `idx_art_cat` (`articleID`, `category`),
            INDEX `idx_timestamp` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. tblseo_scores
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblseo_scores` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT NOT NULL,
            `final_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `rule_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `ai_score` DECIMAL(5,2) NULL,
            `score_category` VARCHAR(50) NOT NULL DEFAULT 'poor',
            `details` JSON NULL,
            `ai_feedback` TEXT NULL,
            `recommendations` JSON NULL,
            `model_used` VARCHAR(100) NULL,
            `scored_by` INT NULL,
            `used_by` INT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            INDEX `idx_seo_article` (`article_id`),
            INDEX `idx_score_cat` (`score_category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. tblseoscoring
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblseoscoring` LIKE `tblseo_scores`");

        // 5. tblai_generated_logs
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblai_generated_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `article_id` INT NULL,
            `user_id` INT NOT NULL DEFAULT 1,
            `action_type` VARCHAR(50) NOT NULL DEFAULT 'general',
            `provider_code` VARCHAR(50) NOT NULL DEFAULT 'gemini',
            `model_name` VARCHAR(100) NOT NULL DEFAULT 'gemini-3.5-flash-lite',
            `input_title` VARCHAR(255) NULL,
            `input_body_snapshot` LONGTEXT NULL,
            `generated_result` LONGTEXT NULL,
            `was_accepted` TINYINT(1) NULL,
            `latency_ms` INT NULL,
            `error_message` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            INDEX `idx_ai_article` (`article_id`),
            INDEX `idx_ai_user` (`user_id`),
            INDEX `idx_ai_action` (`action_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 6. tblaigenerate
        $this->db->query("CREATE TABLE IF NOT EXISTS `tblaigenerate` LIKE `tblai_generated_logs`");

        // Compatibility Views
        $this->db->query("CREATE OR REPLACE VIEW `articlepoints` AS SELECT * FROM `tblarticlepoints`");
        $this->db->query("CREATE OR REPLACE VIEW `articleheadlinehistory` AS SELECT * FROM `tblarticleheadlinehistory`");
        $this->db->query("CREATE OR REPLACE VIEW `seo_scores` AS SELECT * FROM `tblseo_scores`");
        $this->db->query("CREATE OR REPLACE VIEW `seoscoring` AS SELECT * FROM `tblseoscoring`");
        $this->db->query("CREATE OR REPLACE VIEW `ai_generated_logs` AS SELECT * FROM `tblai_generated_logs`");
        $this->db->query("CREATE OR REPLACE VIEW `aigenerate` AS SELECT * FROM `tblaigenerate`");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS `aigenerate`");
        $this->db->query("DROP VIEW IF EXISTS `ai_generated_logs`");
        $this->db->query("DROP VIEW IF EXISTS `seoscoring`");
        $this->db->query("DROP VIEW IF EXISTS `seo_scores`");
        $this->db->query("DROP VIEW IF EXISTS `articleheadlinehistory`");
        $this->db->query("DROP VIEW IF EXISTS `articlepoints`");

        $this->db->query("DROP TABLE IF EXISTS `tblaigenerate`");
        $this->db->query("DROP TABLE IF EXISTS `tblai_generated_logs`");
        $this->db->query("DROP TABLE IF EXISTS `tblseoscoring`");
        $this->db->query("DROP TABLE IF EXISTS `tblseo_scores`");
        $this->db->query("DROP TABLE IF EXISTS `tblarticleheadlinehistory`");
        $this->db->query("DROP TABLE IF EXISTS `tblarticlepoints`");
    }
}
