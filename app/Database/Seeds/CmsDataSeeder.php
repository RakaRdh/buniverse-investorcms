<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\DataRepository;

class CmsDataSeeder extends Seeder
{
    public function run()
    {
        DataRepository::initialize();

        // 1. entity_nano_status (Master status artikel)
        $statuses = [
            ['record_id' => 1, 'label_name' => 'Draft'],
            ['record_id' => 2, 'label_name' => 'Review'],
            ['record_id' => 3, 'label_name' => 'Scheduled'],
            ['record_id' => 4, 'label_name' => 'Published'],
            ['record_id' => 5, 'label_name' => 'Archived'],
        ];
        foreach ($statuses as $st) {
            $existing = $this->db->table('entity_nano_status')->where('label_name', $st['label_name'])->get()->getRow();
            if (!$existing) {
                $this->db->table('entity_nano_status')->insert($st);
            }
        }

        // 2. sys_principal_entity (Admin & Staff users)
        $users = DataRepository::get('users') ?? [];
        $userMap = []; // username => ref_entity_principal_id
        foreach ($users as $u) {
            $existing = $this->db->table('sys_principal_entity')->where('attr_identifier', $u['username'])->get()->getRow();
            if (!$existing) {
                $this->db->table('sys_principal_entity')->insert([
                    'attr_display_name'    => $u['name'],
                    'attr_identifier'      => $u['username'],
                    'attr_alt_label'       => $u['email'],
                    'attr_slug_token'      => url_title($u['name'], '-', true),
                    'attr_credential_hash' => password_hash($u['password'] ?? 'admin123', PASSWORD_BCRYPT),
                    'attr_credential_salt' => '',
                    'attr_contact_val'     => $u['email'],
                    'attr_is_active'       => 1,
                    'attr_role_code'       => $u['role'],
                    'attr_last_access_at'  => date('Y-m-d H:i:s'),
                ]);
                $userMap[$u['username']] = $this->db->insertID();
            } else {
                $userMap[$u['username']] = $existing->ref_entity_principal_id;
            }
        }

        // 3. entity_charlie (Kanals & Sub-Kanals)
        $kanals = DataRepository::get('kanals') ?? [];
        $categoryMap = []; // 'Market' => id, 'Market Stock' => id, 'Corporate Action' => id, etc.
        $kOrder = 1;
        foreach ($kanals as $k) {
            $existingParent = $this->db->table('entity_charlie')->where('charlie_name_a', $k['name'])->get()->getRow();
            if (!$existingParent) {
                $this->db->table('entity_charlie')->insert([
                    'charlie_name_a' => $k['name'],
                    'field_label_a'  => $k['slug'],
                    'parent_id'      => null,
                    'status_code'    => 1,
                    'numeric_val'    => $kOrder++,
                ]);
                $parentId = $this->db->insertID();
            } else {
                $parentId = $existingParent->ref_entity_charlie_id;
            }

            $categoryMap[$k['name']] = $parentId;
            $categoryMap[$k['slug']] = $parentId;

            // Sub-kanals
            $subOrder = 1;
            foreach ($k['sub_kanals'] ?? [] as $sub) {
                $existingSub = $this->db->table('entity_charlie')->where('charlie_name_a', $sub['name'])->get()->getRow();
                if (!$existingSub) {
                    $this->db->table('entity_charlie')->insert([
                        'charlie_name_a' => $sub['name'],
                        'field_label_a'  => $sub['slug'],
                        'parent_id'      => $parentId,
                        'status_code'    => 1,
                        'numeric_val'    => $subOrder++,
                    ]);
                    $subId = $this->db->insertID();
                } else {
                    $subId = $existingSub->ref_entity_charlie_id;
                }

                $categoryMap[$sub['name']] = $subId;
                $categoryMap[$sub['slug']] = $subId;
                $categoryMap[$k['name'] . ' ' . $sub['name']] = $subId;
            }
        }

        // Sync backup entity_charlie_backup
        $allCharlies = $this->db->table('entity_charlie')->get()->getResultArray();
        foreach ($allCharlies as $row) {
            $existsBackup = $this->db->table('entity_charlie_backup')->where('ref_entity_charlie_id', $row['ref_entity_charlie_id'])->get()->getRow();
            if (!$existsBackup) {
                $this->db->table('entity_charlie_backup')->insert($row);
            }
        }

        // 4. entity_aubert (Authors)
        $posts = DataRepository::get('posts') ?? [];
        $authorMap = []; // author_name => ref_entity_aubert_id
        foreach ($posts as $p) {
            $authorName = trim($p['author'] ?? 'Redaksi');
            if (!empty($authorName) && !isset($authorMap[$authorName])) {
                $existingAuthor = $this->db->table('entity_aubert')->where('aubert_name_a', $authorName)->get()->getRow();
                if (!$existingAuthor) {
                    $this->db->table('entity_aubert')->insert([
                        'aubert_name_a' => $authorName,
                        'contact_val'   => '',
                        'slug_value'    => url_title($authorName, '-', true) ?: 'author-' . uniqid(),
                        'status_flag'   => 'STATE_1',
                    ]);
                    $authorMap[$authorName] = $this->db->insertID();
                } else {
                    $authorMap[$authorName] = $existingAuthor->ref_entity_aubert_id;
                }
            }
        }

        // 5. entity_nano & pivot tables
        $articleMap = []; // old id => new ref_entity_nano_id
        foreach ($posts as $p) {
            $pubDate = !empty($p['publish_date']) ? date('Y-m-d H:i:s', strtotime($p['publish_date'])) : date('Y-m-d H:i:s');
            $createDate = !empty($p['create_date']) ? date('Y-m-d H:i:s', strtotime($p['create_date'])) : date('Y-m-d H:i:s');
            $postByUsername = $p['post_by'] ?? 'raka_h';
            $accId = $userMap[$postByUsername] ?? 1;

            $this->db->table('entity_nano')->insert([
                'ref_entity_acc_id' => $accId,
                'source_channel'    => 'cms',
                'headline_text'     => $p['title'],
                'slug_value'        => $p['slug'] ?? url_title($p['title'], '-', true),
                'entity_date'       => $pubDate,
                'post_type'         => 'Nano',
                'view_count'        => (int)($p['views'] ?? 0),
                'status_code'       => $p['status'] ?? 'Published',
                'summary_text'      => $p['summary'] ?? '',
                'body_text'         => $p['content'] ?? '',
                'created_at'        => $createDate,
                'edit_lock'         => 0,
            ]);
            $nanoId = $this->db->insertID();
            $articleMap[$p['id']] = $nanoId;

            // Link ke kategori
            $catKey = $p['category'] ?? ($p['kanal'] ?? '');
            $charlieId = $categoryMap[$catKey] ?? ($categoryMap[$p['category_slug'] ?? ''] ?? 1);
            $this->db->table('entity_nano_charlie_link')->insert([
                'ref_entity_nano_id'    => $nanoId,
                'ref_entity_charlie_id' => $charlieId,
            ]);

            // Link ke penulis
            $aubertId = $authorMap[$p['author'] ?? ''] ?? 1;
            $this->db->table('entity_nano_aubert_link')->insert([
                'ref_entity_nano_id'   => $nanoId,
                'ref_entity_aubert_id' => $aubertId,
            ]);

            // Save meta for is_premium, thumbnail, upper_deck
            if (isset($p['is_premium'])) {
                $this->db->table('entity_nano_meta')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'meta_key'           => 'is_premium',
                    'meta_value'         => (string)$p['is_premium'],
                ]);
            }
            if (!empty($p['thumbnail'])) {
                $this->db->table('entity_nano_meta')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'meta_key'           => 'thumbnail',
                    'meta_value'         => $p['thumbnail'],
                ]);
            }
            if (!empty($p['headline_type'])) {
                $this->db->table('entity_nano_meta')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'meta_key'           => 'headline_type',
                    'meta_value'         => $p['headline_type'],
                ]);
            }
        }

        // 6. entity_nano_headline (Active headlines)
        $activeHeadlines = DataRepository::get('active_headlines') ?? [];
        $hPos = 1;
        foreach ($activeHeadlines as $h) {
            $mappedNanoId = $articleMap[$h['id']] ?? null;
            if ($mappedNanoId) {
                $this->db->table('entity_nano_headline')->insert([
                    'charlie_label'      => 'Headline',
                    'ref_entity_nano_id' => $mappedNanoId,
                    'numeric_val'        => $hPos++,
                    'event_timestamp'    => date('Y-m-d H:i:s'),
                    'status_code'        => 1,
                ]);
            }
        }

        // 7. asset_item_record (Media Library)
        $mediaList = DataRepository::get('media_extended') ?? [];
        $imgPool = [
            '/img/article1.jpg',
            '/img/article2.jpg',
            '/img/article3.jpg',
            '/img/article4.jpg',
            '/img/article5.jpg',
            '/img/article6.jpg',
        ];
        foreach ($mediaList as $idx => $m) {
            $userKey = $m['user'] ?? 'raka_h';
            $principalId = $userMap[$userKey] ?? 1;
            $imgFile = $imgPool[$idx % count($imgPool)];

            $this->db->table('asset_item_record')->insert([
                'attr_short_description' => $m['caption'] ?? '',
                'attr_full_description'  => $m['caption'] ?? '',
                'attr_resource_name'     => $m['image'] ?? $imgFile,
                'attr_resource_title'    => $m['title'] ?? 'Media Item ' . ($idx + 1),
                'attr_origin_source'     => $m['source'] ?? 'Investor.id / Riset',
                'attr_aubert_name'       => $userKey,
                'ref_entity_principal_id'=> $principalId,
            ]);
        }

        // 8. storage_item_record (Files)
        $filesList = DataRepository::get('files_extended') ?? [];
        foreach ($filesList as $f) {
            $userKey = $f['upload_by'] ?? 'raka_h';
            $principalId = $userMap[$userKey] ?? 1;

            $this->db->table('storage_item_record')->insert([
                'attr_header_title'      => $f['name'] ?? 'Document',
                'attr_resource_name'     => $f['url'] ?? '/files/download',
                'ref_entity_principal_id'=> $principalId,
            ]);
        }

        // 9. chart_config (Embed Charts)
        $charts = DataRepository::get('embed_charts') ?? [];
        foreach ($charts as $c) {
            $this->db->table('chart_config')->insert([
                'chart_title'  => $c['title'] ?? 'Market Chart',
                'x_axis_title' => $c['symbol'] ?? 'IDX:STOCK',
                'x_axis_data'  => $c['embed_code'] ?? '',
                'created_at'   => date('Y-m-d H:i:s'),
                'created_by'   => 1,
                'updated_at'   => date('Y-m-d H:i:s'),
                'updated_by'   => 1,
                'source_val'   => $c['source'] ?? 'Investor Market Feed',
                'chart_style'  => $c['type'] ?? 'TradingView Widget',
            ]);
        }

        // 10. content_feature (Feature Content)
        $features = DataRepository::get('feature_content') ?? [];
        foreach ($features as $fc) {
            $this->db->table('content_feature')->insert([
                'content_type'  => $fc['type'] ?? 'Feature',
                'content_title' => $fc['title'] ?? '',
                'content_tag'   => $fc['badge'] ?? '',
                'set_by'        => 'raka_h',
                'set_date'      => date('Y-m-d H:i:s'),
                'image_url'     => $fc['image'] ?? '/img/article1.jpg',
                'link_url'      => $fc['target_url'] ?? '/post/1',
                'position_val'  => 'top',
                'numeric_val'   => $fc['order'] ?? 1,
            ]);
        }

        // 11. notif_message (Push Notification History - CRUD only)
        $pushes = DataRepository::get('push_history') ?? [];
        foreach ($pushes as $p) {
            $pDate = !empty($p['push_date']) ? date('Y-m-d H:i:s', strtotime($p['push_date'])) : date('Y-m-d H:i:s');
            $this->db->table('notif_message')->insert([
                'posted_by'        => 1,
                'push_date'        => $pDate,
                'push_type'        => 'broadcast',
                'push_url'         => '/news',
                'target_direction' => 'outbound',
                'push_slug'        => url_title($p['content'] ?? 'notif', '-', true),
                'push_title'       => $p['content'] ?? 'Breaking Alert',
                'push_body'        => $p['content'] ?? 'Push notification body',
                'push_image'       => '/img/article1.jpg',
                'push_message_ref' => 'REF-' . rand(1000, 9999),
                'status_code'      => 'state_1',
            ]);
        }

        // 12. Dummy activity log (entity_epsilon_log)
        $this->db->table('entity_epsilon_log')->insert([
            'ref_entity_epsilon_id' => 1,
            'action_type_a'        => 'SYSTEM',
            'action_type_b'        => 'SEED',
            'log_data'             => 'Initial database seeding executed successfully.',
        ]);

        // 13. client_account_entity & client_account_backup (Client / Member Subscribers)
        $members = [
            [
                'name'    => 'Budi Hartono Wijaya',
                'email'   => 'budi.hartono@djarumcapital.co.id',
                'company' => 'PT Djarum Capital Investama (Corporate VIP)',
                'status'  => 'STATUS_1',
                'reg'     => '2025-08-15 10:00:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                'name'    => 'Sri Mulyani Handayani',
                'email'   => 'sri.mulyani@mandirisekuritas.co.id',
                'company' => 'Mandiri Sekuritas Research (Institutional)',
                'status'  => 'STATUS_1',
                'reg'     => '2025-09-01 09:30:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'name'    => 'Anthony Salim Sutanto',
                'email'   => 'anthony.s@indofoodasset.com',
                'company' => 'Salim Asset Management (Corporate VIP)',
                'status'  => 'STATUS_1',
                'reg'     => '2025-10-12 14:15:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'name'    => 'Felicia Gunawan',
                'email'   => 'felicia.gunawan@trimegah.com',
                'company' => 'Trimegah Securities Equity Analyst (Priority)',
                'status'  => 'STATUS_1',
                'reg'     => '2026-01-10 11:20:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-5 hours')),
            ],
            [
                'name'    => 'Michael Bambang Pratama',
                'email'   => 'michael.pratama@sucorasset.com',
                'company' => 'Sucor Asset Management (Priority)',
                'status'  => 'STATUS_1',
                'reg'     => '2026-02-01 08:45:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-12 hours')),
            ],
            [
                'name'    => 'Denny Chandra Kusuma',
                'email'   => 'denny.kusuma@gmail.com',
                'company' => 'Retail Trader Community (Retail)',
                'status'  => 'STATUS_1',
                'reg'     => '2026-03-15 16:00:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-1 week')),
            ],
            [
                'name'    => 'Hendra Setiawan',
                'email'   => 'hendra.setiawan@bca.co.id',
                'company' => 'PT Bank Central Asia Tbk Treasury (Institutional)',
                'status'  => 'STATUS_1',
                'reg'     => '2025-11-20 13:10:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-4 hours')),
            ],
            [
                'name'    => 'Clarissa Tanoesoedibjo',
                'email'   => 'clarissa.t@mncgroup.com',
                'company' => 'MNC Kapital Indonesia (Corporate VIP)',
                'status'  => 'STATUS_2',
                'reg'     => date('Y-m-d H:i:s', strtotime('-2 days')),
                'login'   => null,
            ],
            [
                'name'    => 'Aditya Wardhana',
                'email'   => 'aditya.wardhana@indopremier.com',
                'company' => 'Indo Premier Investment (Priority)',
                'status'  => 'STATUS_3',
                'reg'     => '2025-05-10 10:00:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-20 days')),
            ],
            [
                'name'    => 'Rina Wulandari Putri',
                'email'   => 'rina.wulandari@yahoo.com',
                'company' => 'Universitas Indonesia Akademisi (Retail)',
                'status'  => 'STATUS_1',
                'reg'     => '2026-04-01 14:00:00',
                'login'   => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
        ];

        foreach ($members as $m) {
            $exist = $this->db->table('client_account_entity')->where('attr_contact_val', $m['email'])->get()->getRow();
            if (!$exist) {
                $record = [
                    'uniq_id'              => md5($m['email'] . 'salt_seed'),
                    'attr_contact_val'     => $m['email'],
                    'attr_full_name'       => $m['name'],
                    'attr_slug_token'      => url_title($m['name'], '-', true),
                    'attr_credential_hash' => md5('member123'),
                    'attr_registration_at' => $m['reg'],
                    'attr_last_access_at'  => $m['login'],
                    'attr_status_code'     => $m['status'],
                    'attr_profile_summary' => $m['company'],
                    'attr_modified_at'     => date('Y-m-d H:i:s'),
                ];
                $this->db->table('client_account_entity')->insert($record);
                $newId = $this->db->insertID();

                // Sync into client_account_backup
                $record['ref_entity_client_id'] = $newId;
                $this->db->table('client_account_backup')->insert($record);
            }
        }

        echo "CmsDataSeeder finished successfully!\n";
    }
}
