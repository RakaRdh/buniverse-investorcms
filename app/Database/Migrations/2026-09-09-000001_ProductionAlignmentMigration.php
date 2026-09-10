<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Production Alignment Migration
 * 
 * Aligns the database schema and data with production conventions:
 * - Renames article_points → articlepoints
 * - Updates articlestatus values (Draft, Publish, Hold, Deleted)
 * - Adjusts article defaults (source empty, editlock NULL)
 * - Updates existing article statuses
 */
class ProductionAlignmentMigration extends Migration
{
    public function up()
    {
        // 1. Rename article_points → articlepoints
        if ($this->db->tableExists('article_points') && !$this->db->tableExists('articlepoints')) {
            $this->db->query('RENAME TABLE `article_points` TO `articlepoints`');
        }

        // 2. Update articlestatus values to match production
        if ($this->db->tableExists('articlestatus')) {
            $this->db->query("DELETE FROM `articlestatus`");
            $this->db->table('articlestatus')->insertBatch([
                ['id' => 1, 'label' => 'Draft'],
                ['id' => 2, 'label' => 'Publish'],
                ['id' => 3, 'label' => 'Hold'],
                ['id' => 4, 'label' => 'Deleted'],
            ]);
        }

        // 3. Update existing article statuses to production conventions
        if ($this->db->tableExists('article')) {
            $this->db->query("UPDATE `article` SET `status` = 'Publish' WHERE LOWER(`status`) IN ('published', 'publish')");
            $this->db->query("UPDATE `article` SET `status` = 'Hold' WHERE LOWER(`status`) IN ('review', 'scheduled')");
            $this->db->query("UPDATE `article` SET `status` = 'Deleted' WHERE LOWER(`status`) = 'archived'");
            // Set source to empty string and editlock to NULL
            $this->db->query("UPDATE `article` SET `source` = '' WHERE `source` = 'cms'");
            $this->db->query("UPDATE `article` SET `editlock` = NULL");
        }

        // 4. Update articlemeta: rename metakey 'source' → 'news_source'
        if ($this->db->tableExists('articlemeta')) {
            $this->db->query("UPDATE `articlemeta` SET `metakey` = 'news_source' WHERE `metakey` = 'source'");
        }

        // 5. Update articleheadline category values to production conventions
        if ($this->db->tableExists('articleheadline')) {
            $this->db->query("UPDATE `articleheadline` SET `category` = 'home' WHERE LOWER(`category`) = 'headline'");
            $this->db->query("UPDATE `articleheadline` SET `category` = 'home' WHERE `category` NOT IN ('home', 'tropical', 'trending')");
        }

        // 6. Update pushnotif pushType to production values
        if ($this->db->tableExists('pushnotif')) {
            $this->db->query("UPDATE `pushnotif` SET `pushType` = 'NewsLanding' WHERE `pushType` = 'broadcast'");
            $this->db->query("UPDATE `pushnotif` SET `fcm_message_id` = NULL");
        }

        // 7. Update featurecontent type values
        if ($this->db->tableExists('featurecontent')) {
            $this->db->query("UPDATE `featurecontent` SET `type` = 'banner' WHERE `type` NOT IN ('tag', 'banner')");
        }

        // 8. Update authors: add *) suffix and set email to NULL
        if ($this->db->tableExists('authors')) {
            $this->db->query("UPDATE `authors` SET `authorName` = CONCAT(TRIM(TRAILING '*)' FROM `authorName`), '*)') WHERE `authorName` NOT LIKE '%*)'");
            $this->db->query("UPDATE `authors` SET `email` = NULL");
        }

        // 9. Update authors_current: set slug to NULL/empty and make column nullable
        if ($this->db->tableExists('authors_current')) {
            $this->db->query("ALTER TABLE `authors_current` MODIFY COLUMN `slug` VARCHAR(255) NULL DEFAULT NULL");
            $this->db->query("UPDATE `authors_current` SET `slug` = NULL");
        }

        // 10. Update mediaarticle: position starts from 0
        if ($this->db->tableExists('mediaarticle')) {
            $this->db->query("UPDATE `mediaarticle` SET `position` = `position` - 1 WHERE `position` > 0");
        }

        // 11. Update useradministrator roles to production conventions
        if ($this->db->tableExists('useradministrator')) {
            $this->db->query("UPDATE `useradministrator` SET `roleName` = 'administrator' WHERE LOWER(`roleName`) = 'admin'");
            $this->db->query("UPDATE `useradministrator` SET `roleName` = 'editor' WHERE LOWER(`roleName`) IN ('author', 'contributor')");
        }

        // 12. Hash sensitive push device data
        if ($this->db->tableExists('pushregistered')) {
            $this->db->query("UPDATE `pushregistered` SET `udid` = SHA2(`udid`, 256) WHERE `udid` NOT REGEXP '^[a-f0-9]{64}$'");
            $this->db->query("UPDATE `pushregistered` SET `fcm_token` = SHA2(`fcm_token`, 256) WHERE `fcm_token` NOT REGEXP '^[a-f0-9]{64}$'");
        }
        if ($this->db->tableExists('pushtodevice')) {
            $this->db->query("UPDATE `pushtodevice` SET `deviceToken` = SHA2(`deviceToken`, 256) WHERE `deviceToken` NOT REGEXP '^[a-f0-9]{64}$'");
        }
    }

    public function down()
    {
        // Rename back
        if ($this->db->tableExists('articlepoints') && !$this->db->tableExists('article_points')) {
            $this->db->query('RENAME TABLE `articlepoints` TO `article_points`');
        }

        // Restore articlestatus
        if ($this->db->tableExists('articlestatus')) {
            $this->db->query("DELETE FROM `articlestatus`");
            $this->db->table('articlestatus')->insertBatch([
                ['id' => 1, 'label' => 'Draft'],
                ['id' => 2, 'label' => 'Review'],
                ['id' => 3, 'label' => 'Scheduled'],
                ['id' => 4, 'label' => 'Published'],
                ['id' => 5, 'label' => 'Archived'],
            ]);
        }
    }
}
