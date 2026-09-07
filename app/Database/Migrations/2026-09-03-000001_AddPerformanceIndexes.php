<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        $queries = [
            // entity_nano (artikel)
            "CREATE INDEX idx_nano_status ON entity_nano(status_code)",
            "CREATE INDEX idx_nano_slug ON entity_nano(slug_value)",
            "CREATE INDEX idx_nano_date ON entity_nano(entity_date)",
            "CREATE INDEX idx_nano_acc ON entity_nano(ref_entity_acc_id)",

            // pivot tables (JOIN performance)
            "CREATE INDEX idx_ncl_nano ON entity_nano_charlie_link(ref_entity_nano_id)",
            "CREATE INDEX idx_ncl_charlie ON entity_nano_charlie_link(ref_entity_charlie_id)",
            "CREATE INDEX idx_nal_nano ON entity_nano_aubert_link(ref_entity_nano_id)",
            "CREATE INDEX idx_nal_aubert ON entity_nano_aubert_link(ref_entity_aubert_id)",
            "CREATE INDEX idx_ntl_nano ON entity_nano_teta_link(ref_entity_nano_id)",
            "CREATE INDEX idx_nkl_nano ON entity_nano_keyword_link(ref_entity_nano_id)",
            "CREATE INDEX idx_mnl_media ON media_entity_nano_link(ref_entity_media_id)",
            "CREATE INDEX idx_mnl_nano ON media_entity_nano_link(ref_entity_nano_id)",

            // hierarki kategori
            "CREATE INDEX idx_charlie_parent ON entity_charlie(parent_id)",

            // data pasar
            "CREATE INDEX idx_summary_date ON entity_summary(record_date)",
            "CREATE INDEX idx_summary_code ON entity_summary(item_code)",

            // headline
            "CREATE INDEX idx_headline_nano ON entity_nano_headline(ref_entity_nano_id)",
            "CREATE INDEX idx_headline_status ON entity_nano_headline(status_code)",

            // log aktivitas
            "CREATE INDEX idx_epsilon_user ON entity_epsilon_log(ref_entity_epsilon_id)",

            // notifikasi
            "CREATE INDEX idx_notif_dispatch_push ON notif_device_dispatch(ref_push_id)"
        ];

        foreach ($queries as $sql) {
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
                // Ignore if index already exists
            }
        }
    }

    public function down()
    {
        $drops = [
            "ALTER TABLE entity_nano DROP INDEX idx_nano_status",
            "ALTER TABLE entity_nano DROP INDEX idx_nano_slug",
            "ALTER TABLE entity_nano DROP INDEX idx_nano_date",
            "ALTER TABLE entity_nano DROP INDEX idx_nano_acc",

            "ALTER TABLE entity_nano_charlie_link DROP INDEX idx_ncl_nano",
            "ALTER TABLE entity_nano_charlie_link DROP INDEX idx_ncl_charlie",
            "ALTER TABLE entity_nano_aubert_link DROP INDEX idx_nal_nano",
            "ALTER TABLE entity_nano_aubert_link DROP INDEX idx_nal_aubert",
            "ALTER TABLE entity_nano_teta_link DROP INDEX idx_ntl_nano",
            "ALTER TABLE entity_nano_keyword_link DROP INDEX idx_nkl_nano",
            "ALTER TABLE media_entity_nano_link DROP INDEX idx_mnl_media",
            "ALTER TABLE media_entity_nano_link DROP INDEX idx_mnl_nano",

            "ALTER TABLE entity_charlie DROP INDEX idx_charlie_parent",

            "ALTER TABLE entity_summary DROP INDEX idx_summary_date",
            "ALTER TABLE entity_summary DROP INDEX idx_summary_code",

            "ALTER TABLE entity_nano_headline DROP INDEX idx_headline_nano",
            "ALTER TABLE entity_nano_headline DROP INDEX idx_headline_status",

            "ALTER TABLE entity_epsilon_log DROP INDEX idx_epsilon_user",

            "ALTER TABLE notif_device_dispatch DROP INDEX idx_notif_dispatch_push"
        ];

        foreach ($drops as $sql) {
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
                // Ignore if index already dropped
            }
        }
    }
}
