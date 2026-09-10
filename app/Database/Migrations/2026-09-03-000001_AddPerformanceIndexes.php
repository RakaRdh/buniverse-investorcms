<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        $queries = [
            // article
            "CREATE INDEX idx_article_status ON article(status)",
            "CREATE INDEX idx_article_slug ON article(slug)",
            "CREATE INDEX idx_article_date ON article(articleDate)",
            "CREATE INDEX idx_article_acc ON article(userID)",

            // pivot tables (JOIN performance)
            "CREATE INDEX idx_ncl_nano ON articlecategory(articleID)",
            "CREATE INDEX idx_ncl_charlie ON articlecategory(categoryID)",
            "CREATE INDEX idx_nal_nano ON articleauthor(articleID)",
            "CREATE INDEX idx_nal_aubert ON articleauthor(authorID)",
            "CREATE INDEX idx_ntl_nano ON articletag(articleId)",
            "CREATE INDEX idx_nkl_nano ON articlekeyword(articleID)",
            "CREATE INDEX idx_mnl_media ON mediaarticle(mediaID)",
            "CREATE INDEX idx_mnl_nano ON mediaarticle(articleID)",

            // hierarki kategori
            "CREATE INDEX idx_category_parent ON category(parent)",

            // data pasar
            "CREATE INDEX idx_summary_date ON saham_summary(Date)",
            "CREATE INDEX idx_summary_code ON saham_summary(StockCode)",

            // headline
            "CREATE INDEX idx_headline_article ON articleheadline(articleID)",
            "CREATE INDEX idx_headline_status ON articleheadline(status)",

            // log aktivitas
            "CREATE INDEX idx_editor_user ON editorlog(editorId)",

            // notifikasi
            "CREATE INDEX idx_notif_dispatch_push ON pushtodevice(idPush)"
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
            "ALTER TABLE article DROP INDEX idx_article_status",
            "ALTER TABLE article DROP INDEX idx_article_slug",
            "ALTER TABLE article DROP INDEX idx_article_date",
            "ALTER TABLE article DROP INDEX idx_article_acc",

            "ALTER TABLE articlecategory DROP INDEX idx_ncl_nano",
            "ALTER TABLE articlecategory DROP INDEX idx_ncl_charlie",
            "ALTER TABLE articleauthor DROP INDEX idx_nal_nano",
            "ALTER TABLE articleauthor DROP INDEX idx_nal_aubert",
            "ALTER TABLE articletag DROP INDEX idx_ntl_nano",
            "ALTER TABLE articlekeyword DROP INDEX idx_nkl_nano",
            "ALTER TABLE mediaarticle DROP INDEX idx_mnl_media",
            "ALTER TABLE mediaarticle DROP INDEX idx_mnl_nano",

            "ALTER TABLE category DROP INDEX idx_category_parent",

            "ALTER TABLE saham_summary DROP INDEX idx_summary_date",
            "ALTER TABLE saham_summary DROP INDEX idx_summary_code",

            "ALTER TABLE articleheadline DROP INDEX idx_headline_article",
            "ALTER TABLE articleheadline DROP INDEX idx_headline_status",

            "ALTER TABLE editorlog DROP INDEX idx_editor_user",

            "ALTER TABLE pushtodevice DROP INDEX idx_notif_dispatch_push"
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
