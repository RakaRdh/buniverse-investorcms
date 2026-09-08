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

        // 2. sys_principal_entity (Admin & Staff users - Login by Username: attr_identifier)
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
                    'attr_credential_hash' => password_hash($u['password'] ?? 'password123', PASSWORD_BCRYPT),
                    'attr_credential_salt' => '',
                    'attr_contact_val'     => $u['email'],
                    'attr_is_active'       => 1,
                    'attr_role_code'       => $u['role'],
                    'attr_last_access_at'  => date('Y-m-d H:i:s'),
                ]);
                $userMap[$u['username']] = $this->db->insertID();
            } else {
                // Synchronize demo credentials and profile
                if (!empty($u['password'])) {
                    $this->db->table('sys_principal_entity')
                        ->where('ref_entity_principal_id', $existing->ref_entity_principal_id)
                        ->update([
                            'attr_display_name'    => $u['name'],
                            'attr_alt_label'       => $u['email'],
                            'attr_contact_val'     => $u['email'],
                            'attr_credential_hash' => password_hash($u['password'], PASSWORD_BCRYPT),
                            'attr_credential_salt' => '', // Reset salt so AdminModel can re-authenticate & encode
                            'attr_is_active'       => 1,
                            'attr_role_code'       => $u['role'],
                        ]);
                }
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

        // 6. entity_nano_headline (Active headlines by Kanal)
        $activeHeadlines = DataRepository::get('active_headlines') ?? [];
        $kanalsPool = ['Headline', 'Market', 'Finance', 'Macroeconomy', 'Business'];
        foreach ($activeHeadlines as $idx => $h) {
            $mappedNanoId = $articleMap[$h['id']] ?? null;
            if ($mappedNanoId) {
                $kLabel = !empty($h['category']) ? $h['category'] : $kanalsPool[$idx % count($kanalsPool)];
                $this->db->table('entity_nano_headline')->insert([
                    'charlie_label'      => $kLabel,
                    'ref_entity_nano_id' => $mappedNanoId,
                    'numeric_val'        => ($idx % 3) + 1,
                    'event_timestamp'    => date('Y-m-d H:i:s'),
                    'status_code'        => 1,
                ]);
            }
        }

        // 7. asset_item_record (Media Library)
        $mediaList = DataRepository::get('media_extended') ?? [];
        $imgPool = [
            '/img/investor_placeholder.jpg',
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
                'image_url'     => $fc['image'] ?? '/img/investor_placeholder.jpg',
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
                'push_image'       => '/img/investor_placeholder.jpg',
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

        // 9. entity_registry (75+ Emiten Saham BEI)
        if ($this->db->table('entity_registry')->countAllResults() == 0) {
            $emitens = [
                ['item_code' => 'BBCA', 'entity_name' => 'PT Bank Central Asia Tbk'],
                ['item_code' => 'BBRI', 'entity_name' => 'PT Bank Rakyat Indonesia (Persero) Tbk'],
                ['item_code' => 'BMRI', 'entity_name' => 'PT Bank Mandiri (Persero) Tbk'],
                ['item_code' => 'BBNI', 'entity_name' => 'PT Bank Negara Indonesia (Persero) Tbk'],
                ['item_code' => 'TLKM', 'entity_name' => 'PT Telkom Indonesia (Persero) Tbk'],
                ['item_code' => 'ASII', 'entity_name' => 'PT Astra International Tbk'],
                ['item_code' => 'AMMN', 'entity_name' => 'PT Amman Mineral Internasional Tbk'],
                ['item_code' => 'BREN', 'entity_name' => 'PT Barito Renewables Energy Tbk'],
                ['item_code' => 'GOTO', 'entity_name' => 'PT GoTo Gojek Tokopedia Tbk'],
                ['item_code' => 'ICBP', 'entity_name' => 'PT Indofood CBP Sukses Makmur Tbk'],
                ['item_code' => 'INDF', 'entity_name' => 'PT Indofood Sukses Makmur Tbk'],
                ['item_code' => 'UNVR', 'entity_name' => 'PT Unilever Indonesia Tbk'],
                ['item_code' => 'KLBF', 'entity_name' => 'PT Kalbe Farma Tbk'],
                ['item_code' => 'ADRO', 'entity_name' => 'PT Adaro Energy Indonesia Tbk'],
                ['item_code' => 'PTBA', 'entity_name' => 'PT Bukit Asam Tbk'],
                ['item_code' => 'PGAS', 'entity_name' => 'PT Perusahaan Gas Negara Tbk'],
                ['item_code' => 'ANTM', 'entity_name' => 'PT Aneka Tambang Tbk'],
                ['item_code' => 'INCO', 'entity_name' => 'PT Vale Indonesia Tbk'],
                ['item_code' => 'BRIS', 'entity_name' => 'PT Bank Syariah Indonesia Tbk'],
                ['item_code' => 'CPIN', 'entity_name' => 'PT Charoen Pokphand Indonesia Tbk'],
                ['item_code' => 'JPFA', 'entity_name' => 'PT Japfa Comfeed Indonesia Tbk'],
                ['item_code' => 'SMGR', 'entity_name' => 'PT Semen Indonesia (Persero) Tbk'],
                ['item_code' => 'INTP', 'entity_name' => 'PT Indocement Tunggal Prakarsa Tbk'],
                ['item_code' => 'MDKA', 'entity_name' => 'PT Merdeka Copper Gold Tbk'],
                ['item_code' => 'ARTO', 'entity_name' => 'PT Bank Jago Tbk'],
                ['item_code' => 'ESSA', 'entity_name' => 'PT ESSA Industries Indonesia Tbk'],
                ['item_code' => 'MEDC', 'entity_name' => 'PT Medco Energi Internasional Tbk'],
                ['item_code' => 'TPIA', 'entity_name' => 'PT Chandra Asri Pacific Tbk'],
                ['item_code' => 'BRPT', 'entity_name' => 'PT Barito Pacific Tbk'],
                ['item_code' => 'CTRA', 'entity_name' => 'PT Ciputra Development Tbk'],
                ['item_code' => 'BSDE', 'entity_name' => 'PT Bumi Serpong Damai Tbk'],
                ['item_code' => 'PWON', 'entity_name' => 'PT Pakuwon Jati Tbk'],
                ['item_code' => 'SMRA', 'entity_name' => 'PT Summarecon Agung Tbk'],
                ['item_code' => 'ERAA', 'entity_name' => 'PT Erajaya Swasembada Tbk'],
                ['item_code' => 'MYOR', 'entity_name' => 'PT Mayora Indah Tbk'],
                ['item_code' => 'SIDO', 'entity_name' => 'PT Industri Jamu dan Farmasi Sido Muncul Tbk'],
                ['item_code' => 'TBIG', 'entity_name' => 'PT Tower Bersama Infrastructure Tbk'],
                ['item_code' => 'TOWR', 'entity_name' => 'PT Sarana Menara Nusantara Tbk'],
                ['item_code' => 'EXCL', 'entity_name' => 'PT XL Axiata Tbk'],
                ['item_code' => 'ISAT', 'entity_name' => 'PT Indosat Tbk'],
                ['item_code' => 'JSMR', 'entity_name' => 'PT Jasa Marga (Persero) Tbk'],
                ['item_code' => 'AKRA', 'entity_name' => 'PT AKR Corporindo Tbk'],
                ['item_code' => 'UNTR', 'entity_name' => 'PT United Tractors Tbk'],
                ['item_code' => 'ACES', 'entity_name' => 'PT Aspirasi Hidup Indonesia Tbk'],
                ['item_code' => 'MAPI', 'entity_name' => 'PT Mitra Adiperkasa Tbk'],
                ['item_code' => 'HEAL', 'entity_name' => 'PT Medikaloka Hermina Tbk'],
                ['item_code' => 'MIKA', 'entity_name' => 'PT Mitra Keluarga Karyasehat Tbk'],
                ['item_code' => 'SILO', 'entity_name' => 'PT Siloam International Hospitals Tbk'],
                ['item_code' => 'CMRY', 'entity_name' => 'PT Cisarua Mountain Dairy Tbk'],
                ['item_code' => 'BUMI', 'entity_name' => 'PT Bumi Resources Tbk'],
                ['item_code' => 'DEWA', 'entity_name' => 'PT Darma Henwa Tbk'],
                ['item_code' => 'ENRG', 'entity_name' => 'PT Energi Mega Persada Tbk'],
                ['item_code' => 'ADMR', 'entity_name' => 'PT Adaro Minerals Indonesia Tbk'],
                ['item_code' => 'MBMA', 'entity_name' => 'PT Merdeka Battery Materials Tbk'],
                ['item_code' => 'NCKL', 'entity_name' => 'PT Trimegah Bangun Persada Tbk'],
                ['item_code' => 'PGEO', 'entity_name' => 'PT Pertamina Geothermal Energy Tbk'],
                ['item_code' => 'AVIA', 'entity_name' => 'PT Avia Avian Tbk'],
                ['item_code' => 'BTPS', 'entity_name' => 'PT Bank BTPN Syariah Tbk'],
                ['item_code' => 'BBTN', 'entity_name' => 'PT Bank Tabungan Negara (Persero) Tbk'],
                ['item_code' => 'BDMN', 'entity_name' => 'PT Bank Danamon Indonesia Tbk'],
                ['item_code' => 'BNGA', 'entity_name' => 'PT Bank CIMB Niaga Tbk'],
                ['item_code' => 'BNII', 'entity_name' => 'PT Bank Maybank Indonesia Tbk'],
                ['item_code' => 'PNBN', 'entity_name' => 'PT Bank Pan Indonesia Tbk'],
                ['item_code' => 'BJBR', 'entity_name' => 'PT BPD Jawa Barat dan Banten Tbk'],
                ['item_code' => 'BJTM', 'entity_name' => 'PT BPD Jawa Timur Tbk'],
                ['item_code' => 'SRTG', 'entity_name' => 'PT Saratoga Investama Sedaya Tbk'],
                ['item_code' => 'EMTK', 'entity_name' => 'PT Elang Mahkota Teknologi Tbk'],
                ['item_code' => 'SCMA', 'entity_name' => 'PT Surya Citra Media Tbk'],
                ['item_code' => 'MNCN', 'entity_name' => 'PT Media Nusantara Citra Tbk'],
                ['item_code' => 'BMTR', 'entity_name' => 'PT Global Mediacom Tbk'],
                ['item_code' => 'FILM', 'entity_name' => 'PT MD Pictures Tbk'],
                ['item_code' => 'AMRT', 'entity_name' => 'PT Sumber Alfaria Trijaya Tbk'],
                ['item_code' => 'MIDI', 'entity_name' => 'PT Midi Utama Indonesia Tbk'],
                ['item_code' => 'MAPA', 'entity_name' => 'PT MAP Aktif Adiperkasa Tbk'],
            ];
            $this->db->table('entity_registry')->insertBatch($emitens);
        }

        // 10. Indices (entity_index_a, b, c, d)
        if ($this->db->table('entity_index_a')->countAllResults() == 0) {
            $indices = [
                ['index_code' => 'IHSG', 'prev_val' => 7242.43, 'high_val' => 7295.12, 'low_val' => 7235.80, 'last_val' => 7284.55, 'chg_val' => 42.12, 'chg_pct' => 0.58, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'LQ45', 'prev_val' => 906.60, 'high_val' => 915.20, 'low_val' => 905.10, 'last_val' => 912.40, 'chg_val' => 5.80, 'chg_pct' => 0.64, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'IDX30', 'prev_val' => 469.40, 'high_val' => 471.10, 'low_val' => 467.50, 'last_val' => 468.25, 'chg_val' => -1.15, 'chg_pct' => -0.24, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'KOMPAS100', 'prev_val' => 1145.20, 'high_val' => 1152.80, 'low_val' => 1142.30, 'last_val' => 1150.15, 'chg_val' => 4.95, 'chg_pct' => 0.43, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'ISSI', 'prev_val' => 218.40, 'high_val' => 220.10, 'low_val' => 217.90, 'last_val' => 219.85, 'chg_val' => 1.45, 'chg_pct' => 0.66, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'JII', 'prev_val' => 524.10, 'high_val' => 528.30, 'low_val' => 523.50, 'last_val' => 527.20, 'chg_val' => 3.10, 'chg_pct' => 0.59, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'IDX80', 'prev_val' => 128.50, 'high_val' => 129.80, 'low_val' => 128.10, 'last_val' => 129.40, 'chg_val' => 0.90, 'chg_pct' => 0.70, 'last_change_at' => date('Y-m-d H:i:s')],
                ['index_code' => 'SRI-KEHATI', 'prev_val' => 412.30, 'high_val' => 416.50, 'low_val' => 411.80, 'last_val' => 415.60, 'chg_val' => 3.30, 'chg_pct' => 0.80, 'last_change_at' => date('Y-m-d H:i:s')],
            ];
            $this->db->table('entity_index_a')->insertBatch($indices);
            $this->db->table('entity_index_b')->insertBatch($indices);
            $this->db->table('entity_index_c')->insertBatch($indices);
            $this->db->table('entity_index_d')->insertBatch($indices);
        }

        // 11. entity_metric_a, b, c (Top Emiten Metrics)
        if ($this->db->table('entity_metric_a')->countAllResults() == 0) {
            $metrics = [
                ['item_code' => 'BBCA', 'price_val' => 10250, 'change_val' => 275, 'percent_val' => 276, 'volume_val' => 84250000, 'value_val' => 863562500000, 'freq_val' => 18450, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'BBRI', 'price_val' => 4850, 'change_val' => 90, 'percent_val' => 189, 'volume_val' => 124500000, 'value_val' => 603825000000, 'freq_val' => 22100, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'BMRI', 'price_val' => 6850, 'change_val' => 150, 'percent_val' => 224, 'volume_val' => 62100000, 'value_val' => 425385000000, 'freq_val' => 14320, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'BREN', 'price_val' => 8925, 'change_val' => 325, 'percent_val' => 378, 'volume_val' => 45200000, 'value_val' => 403410000000, 'freq_val' => 12400, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'TLKM', 'price_val' => 2920, 'change_val' => -70, 'percent_val' => -234, 'volume_val' => 95400000, 'value_val' => 278568000000, 'freq_val' => 16250, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'ASII', 'price_val' => 5100, 'change_val' => 90, 'percent_val' => 180, 'volume_val' => 38900000, 'value_val' => 198390000000, 'freq_val' => 9840, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'AMMN', 'price_val' => 9800, 'change_val' => 225, 'percent_val' => 235, 'volume_val' => 28400000, 'value_val' => 278320000000, 'freq_val' => 8920, 'last_update_at' => date('Y-m-d H:i:s')],
                ['item_code' => 'GOTO', 'price_val' => 52, 'change_val' => -2, 'percent_val' => -370, 'volume_val' => 985000000, 'value_val' => 51220000000, 'freq_val' => 19500, 'last_update_at' => date('Y-m-d H:i:s')],
            ];
            $this->db->table('entity_metric_a')->insertBatch($metrics);
            $this->db->table('entity_metric_b')->insertBatch($metrics);
            $this->db->table('entity_metric_c')->insertBatch($metrics);
        }

        // 12. entity_summary (Realistic Market Daily Summary)
        if ($this->db->table('entity_summary')->countAllResults() == 0) {
            $summaries = [
                [
                    'record_date' => date('Y-m-d H:i:s'), 'item_code' => 'BBCA',
                    'prev_price' => 10000, 'open_price' => 10050, 'first_trade_price' => 10050,
                    'high_price' => 10300, 'low_price' => 9950, 'change_val' => 250,
                    'volume_val' => 84250000, 'value_val' => 863562500000, 'freq_val' => 18450,
                    'index_individual_val' => 12450, 'offer_price' => 10275, 'offer_volume' => 1250000,
                    'bid_price' => 10250, 'bid_volume' => 1890000, 'listed_share_val' => 123275050000,
                    'tradable_share_val' => 61637525000, 'weight_index_val' => 985000000,
                    'foreign_sell_val' => 120500000000, 'foreign_buy_val' => 245800000000,
                    'non_regular_volume' => 15000000, 'non_regular_value' => 153750000000,
                    'non_regular_freq' => 45, 'percentage_val' => 3
                ],
                [
                    'record_date' => date('Y-m-d H:i:s'), 'item_code' => 'BBRI',
                    'prev_price' => 4760, 'open_price' => 4780, 'first_trade_price' => 4780,
                    'high_price' => 4880, 'low_price' => 4750, 'change_val' => 90,
                    'volume_val' => 124500000, 'value_val' => 603825000000, 'freq_val' => 22100,
                    'index_individual_val' => 9820, 'offer_price' => 4860, 'offer_volume' => 3200000,
                    'bid_price' => 4850, 'bid_volume' => 4100000, 'listed_share_val' => 151559000000,
                    'tradable_share_val' => 75779500000, 'weight_index_val' => 745000000,
                    'foreign_sell_val' => 95000000000, 'foreign_buy_val' => 182000000000,
                    'non_regular_volume' => 8500000, 'non_regular_value' => 41225000000,
                    'non_regular_freq' => 32, 'percentage_val' => 2
                ],
                [
                    'record_date' => date('Y-m-d H:i:s'), 'item_code' => 'BMRI',
                    'prev_price' => 6700, 'open_price' => 6725, 'first_trade_price' => 6725,
                    'high_price' => 6900, 'low_price' => 6700, 'change_val' => 150,
                    'volume_val' => 62100000, 'value_val' => 425385000000, 'freq_val' => 14320,
                    'index_individual_val' => 8450, 'offer_price' => 6875, 'offer_volume' => 1850000,
                    'bid_price' => 6850, 'bid_volume' => 2200000, 'listed_share_val' => 93333333333,
                    'tradable_share_val' => 46666666666, 'weight_index_val' => 635000000,
                    'foreign_sell_val' => 72000000000, 'foreign_buy_val' => 145000000000,
                    'non_regular_volume' => 4500000, 'non_regular_value' => 30825000000,
                    'non_regular_freq' => 21, 'percentage_val' => 2
                ],
            ];
            $this->db->table('entity_summary')->insertBatch($summaries);
        }

        // 13. index_identifier
        if ($this->db->table('index_identifier')->countAllResults() == 0) {
            $identifiers = [
                ['search_index_name' => 'article_search_index', 'ref_entity_max_id' => 205, 'created_at' => date('Y-m-d H:i:s')],
                ['search_index_name' => 'tag_search_index', 'ref_entity_max_id' => 85, 'created_at' => date('Y-m-d H:i:s')],
                ['search_index_name' => 'author_search_index', 'ref_entity_max_id' => 12, 'created_at' => date('Y-m-d H:i:s')],
                ['search_index_name' => 'company_search_index', 'ref_entity_max_id' => 75, 'created_at' => date('Y-m-d H:i:s')],
                ['search_index_name' => 'portal_global_index', 'ref_entity_max_id' => 300, 'created_at' => date('Y-m-d H:i:s')],
            ];
            $this->db->table('index_identifier')->insertBatch($identifiers);
        }

        // 14. entity_kilo & entity_nano_keyword_link (SEO Dictionary)
        if ($this->db->table('entity_kilo')->countAllResults() == 0) {
            $keywords = [
                ['kilo_text_a' => 'Dividen Saham Big Banks', 'slug_value' => 'dividen-saham-big-banks'],
                ['kilo_text_a' => 'Rekomendasi Saham Hari Ini', 'slug_value' => 'rekomendasi-saham-hari-ini'],
                ['kilo_text_a' => 'Kinerja Laporan Keuangan Q2 2026', 'slug_value' => 'kinerja-laporan-keuangan-q2-2026'],
                ['kilo_text_a' => 'Analisa IHSG & Prediksi Pasar', 'slug_value' => 'analisa-ihsg-prediksi-pasar'],
                ['kilo_text_a' => 'Suku Bunga Bank Indonesia BI-Rate', 'slug_value' => 'suku-bunga-bank-indonesia-bi-rate'],
                ['kilo_text_a' => 'Stock Split & Right Issue BEI', 'slug_value' => 'stock-split-right-issue-bei'],
                ['kilo_text_a' => 'Emiten Emas & Komoditas Tambang', 'slug_value' => 'emiten-emas-komoditas-tambang'],
                ['kilo_text_a' => 'Valuasi PE Ratio Saham Murah', 'slug_value' => 'valuasi-pe-ratio-saham-murah'],
                ['kilo_text_a' => 'Obligasi Ritel Negara SBR ORI', 'slug_value' => 'obligasi-ritel-negara-sbr-ori'],
                ['kilo_text_a' => 'IPO Saham Baru IDX 2026', 'slug_value' => 'ipo-saham-baru-idx-2026'],
            ];
            $this->db->table('entity_kilo')->insertBatch($keywords);

            $kiloRows = $this->db->table('entity_kilo')->get()->getResultArray();
            $articles = $this->db->table('entity_nano')->limit(20)->get()->getResultArray();
            $links = [];
            foreach ($articles as $aIdx => $art) {
                $kItem = $kiloRows[$aIdx % count($kiloRows)];
                $links[] = [
                    'ref_entity_nano_id' => (int)$art['ref_entity_nano_id'],
                    'ref_entity_kilo_id' => (int)$kItem['record_id'],
                ];
            }
            if (!empty($links)) {
                $this->db->table('entity_nano_keyword_link')->insertBatch($links);
            }
        }

        // 15. trend_record & trend_exclude_link (Trending Topics)
        if ($this->db->table('trend_record')->countAllResults() == 0) {
            $trends = [
                ['label_name' => 'Dividen Jumbo BBCA & BBRI', 'slug_value' => 'dividen-jumbo-bbca-bbri', 'tag_text' => 'BBCA, BBRI, Dividen, Perbankan', 'publish_date' => date('Y-m-d'), 'min_entity_a_publish_date' => date('Y-m-d', strtotime('-30 days')), 'create_date' => date('Y-m-d H:i:s'), 'modified_at' => date('Y-m-d H:i:s'), 'status_code' => '1'],
                ['label_name' => 'Rekor All-Time High IHSG 7.300', 'slug_value' => 'rekor-all-time-high-ihsg-7300', 'tag_text' => 'IHSG, Rekor, BEI, Pasar Modal', 'publish_date' => date('Y-m-d'), 'min_entity_a_publish_date' => date('Y-m-d', strtotime('-30 days')), 'create_date' => date('Y-m-d H:i:s'), 'modified_at' => date('Y-m-d H:i:s'), 'status_code' => '1'],
                ['label_name' => 'Laba Bersih Emiten Komoditas Tambang', 'slug_value' => 'laba-bersih-emiten-tambang', 'tag_text' => 'AMMN, ADRO, ANTM, MDKA', 'publish_date' => date('Y-m-d'), 'min_entity_a_publish_date' => date('Y-m-d', strtotime('-30 days')), 'create_date' => date('Y-m-d H:i:s'), 'modified_at' => date('Y-m-d H:i:s'), 'status_code' => '1'],
                ['label_name' => 'Transisi Energi & Saham Hijau BREN', 'slug_value' => 'transisi-energi-saham-hijau-bren', 'tag_text' => 'BREN, PGEO, EBT, Energi Hijau', 'publish_date' => date('Y-m-d'), 'min_entity_a_publish_date' => date('Y-m-d', strtotime('-30 days')), 'create_date' => date('Y-m-d H:i:s'), 'modified_at' => date('Y-m-d H:i:s'), 'status_code' => '1'],
                ['label_name' => 'Kebijakan Suku Bunga BI & The Fed', 'slug_value' => 'kebijakan-suku-bunga-bi-the-fed', 'tag_text' => 'Bank Indonesia, BI-Rate, The Fed, Rupiah', 'publish_date' => date('Y-m-d'), 'min_entity_a_publish_date' => date('Y-m-d', strtotime('-30 days')), 'create_date' => date('Y-m-d H:i:s'), 'modified_at' => date('Y-m-d H:i:s'), 'status_code' => '1'],
            ];
            $this->db->table('trend_record')->insertBatch($trends);

        }

        if ($this->db->table('trend_exclude_link')->countAllResults() == 0) {
            $trendRow = $this->db->table('trend_record')->get()->getRowArray();
            $firstNano = $this->db->table('entity_nano')->get()->getRowArray();
            if ($trendRow && $firstNano) {
                $this->db->table('trend_exclude_link')->insert([
                    'ref_entity_nano_id'  => (int)$firstNano['ref_entity_nano_id'],
                    'ref_entity_trend_id' => (int)$trendRow['record_id'],
                ]);
            }
        }

        // 16. comment_record (Reader Comments from Members)
        if ($this->db->table('comment_record')->countAllResults() == 0) {
            $clients = $this->db->table('client_account_entity')->limit(12)->get()->getResultArray();
            $articles = $this->db->table('entity_nano')->limit(10)->get()->getResultArray();
            
            $commentsList = [
                'Ulasan yang sangat mendalam dan berbobot. Prospek laba bersih kuartal ini terbukti solid.',
                'Menarik sekali melihat pertumbuhan kredit perbankan yang tetap ekspansif di tengah volatilitas global.',
                'Target yield dividen tahun ini sangat menjanjikan untuk portofolio jangka panjang.',
                'Bagaimana pengaruh pergerakan nilai tukar rupiah terhadap beban operasional emiten ini?',
                'Analisis teknikal dan fundamentalnya sinkron. Patut masuk watchlist pekan ini.',
                'Terima kasih atas riset eksklusif ini Tim Investor.id, sangat mencerahkan bagi investor institusi.',
                'Apakah ada proyeksi capex untuk ekspansi fasilitas energi terbarukan di semester dua nanti?',
                'Valuasi PE saat ini masih di bawah rata-rata historis 5 tahun, momen akumulasi yang bagus.',
                'Kinerja ekspor komoditas tampaknya masih menjadi penopang utama pendapatan konsolidasi.',
                'Sangat setuju dengan paparan analis di artikel ini. Risk-to-reward ratio terlihat menarik.',
                'Pemberitaan yang lugas dan berimbang. Investor ritel sangat terbantu dengan ringkasan poin utamanya.',
                'Menantikan kelanjutan liputan khusus untuk emiten sektor infrastruktur dan menara telekomunikasi.'
            ];

            foreach ($clients as $cIdx => $cl) {
                $art = $articles[$cIdx % count($articles)];
                $this->db->table('comment_record')->insert([
                    'ref_entity_nano_id'   => (int)$art['ref_entity_nano_id'],
                    'ref_entity_client_id' => (int)$cl['ref_entity_client_id'],
                    'reply_to_id'          => 0,
                    'posted_at'            => date('Y-m-d H:i:s', strtotime('-' . ($cIdx * 3) . ' hours')),
                    'status_code'          => '1',
                    'comment_text'         => $commentsList[$cIdx % count($commentsList)],
                    'reply_count'          => ($cIdx % 3 == 0) ? 2 : 0,
                    'like_count'           => 12 + ($cIdx * 5),
                    'dislike_count'        => $cIdx % 2,
                ]);
            }
        }

        // 17. client_favorite_entity_nano (Bookmarks / Favorites)
        if ($this->db->table('client_favorite_entity_nano')->countAllResults() == 0) {
            $clients = $this->db->table('client_account_entity')->limit(8)->get()->getResultArray();
            $articles = $this->db->table('entity_nano')->limit(15)->get()->getResultArray();

            $favs = [];
            foreach ($clients as $cIdx => $cl) {
                $art1 = $articles[($cIdx * 2) % count($articles)];
                $art2 = $articles[(($cIdx * 2) + 1) % count($articles)];

                $favs[] = [
                    'ref_entity_client_id' => (int)$cl['ref_entity_client_id'],
                    'ref_entity_nano_id'   => (int)$art1['ref_entity_nano_id'],
                    'event_timestamp'      => date('Y-m-d H:i:s', strtotime('-' . ($cIdx + 1) . ' days')),
                ];
                $favs[] = [
                    'ref_entity_client_id' => (int)$cl['ref_entity_client_id'],
                    'ref_entity_nano_id'   => (int)$art2['ref_entity_nano_id'],
                    'event_timestamp'      => date('Y-m-d H:i:s', strtotime('-' . ($cIdx + 2) . ' days')),
                ];
            }
            if (!empty($favs)) {
                $this->db->table('client_favorite_entity_nano')->insertBatch($favs);
            }
        }

        // 18. config_store (Apps & System Configs)
        if ($this->db->table('config_store')->countAllResults() == 0) {
            $configs = [
                ['config_key' => 'portal_site_name', 'config_value' => 'INVESTOR.ID — Portal Berita Pasar Modal & Investasi Terpercaya'],
                ['config_key' => 'portal_site_tagline', 'config_value' => 'Cerdas, Akurat, dan Terpercaya Mengawal Portofolio Investasi Anda'],
                ['config_key' => 'portal_contact_email', 'config_value' => 'redaksi@investor.id'],
                ['config_key' => 'portal_hotline', 'config_value' => '+62-21-2995-7500'],
                ['config_key' => 'portal_meta_keywords', 'config_value' => 'pasar modal, saham ihsg, bursa efek indonesia, reksadana, dividen, obligasi, investasi'],
                ['config_key' => 'ai_gemini_default_model', 'config_value' => 'gemini-3.5-flash-lite'],
                ['config_key' => 'app_version_ios', 'config_value' => '2.4.1'],
                ['config_key' => 'app_version_android', 'config_value' => '2.4.5'],
                ['config_key' => 'maintenance_mode', 'config_value' => '0'],
                ['config_key' => 'editor_max_title_chars', 'config_value' => '70'],
                ['config_key' => 'editor_min_summary_chars', 'config_value' => '110'],
                ['config_key' => 'editor_max_summary_chars', 'config_value' => '160'],
            ];
            $this->db->table('config_store')->insertBatch($configs);
        }

        // 19. entity_aubert_current
        if ($this->db->table('entity_aubert_current')->countAllResults() == 0) {
            $auberts = $this->db->table('entity_aubert')->get()->getResultArray();
            $currAuberts = [];
            foreach ($auberts as $ab) {
                $currAuberts[] = [
                    'ref_entity_aubert_id' => (int)$ab['ref_entity_aubert_id'],
                    'aubert_name_a'        => $ab['aubert_name_a'],
                    'slug_value'           => $ab['slug_value'] ?? url_title($ab['aubert_name_a'], '-', true),
                ];
            }
            if (!empty($currAuberts)) {
                $this->db->table('entity_aubert_current')->insertBatch($currAuberts);
            }
        }

        // 20. entity_charlie_meta
        if ($this->db->table('entity_charlie_meta')->countAllResults() == 0) {
            $charlies = $this->db->table('entity_charlie')->limit(10)->get()->getResultArray();
            $chMeta = [];
            foreach ($charlies as $idx => $ch) {
                $chMeta[] = [
                    'ref_entity_charlie_id' => (int)$ch['ref_entity_charlie_id'],
                    'numeric_val'           => $idx + 1,
                    'asset_path_a'          => '/img/icons/' . ($ch['field_label_a'] ?? 'market') . '.svg',
                    'asset_path_b'          => '/img/banners/' . ($ch['field_label_a'] ?? 'market') . '.jpg',
                    'asset_path_c'          => '/img/og/' . ($ch['field_label_a'] ?? 'market') . '-og.jpg',
                ];
            }
            if (!empty($chMeta)) {
                $this->db->table('entity_charlie_meta')->insertBatch($chMeta);
            }
        }

        // 21. ext_resource_record
        if ($this->db->table('ext_resource_record')->countAllResults() == 0) {
            $resources = [
                ['attr_header_title' => 'IDX Financial Feed Realtime API', 'attr_link_locator' => 'https://api.idx.co.id/feed/v1', 'ref_entity_principal_id' => 1, 'attr_recorded_at' => date('Y-m-d H:i:s'), 'attr_origin_source' => 'SOURCE_1'],
                ['attr_header_title' => 'TradingView Interactive Chart Widget CDN', 'attr_link_locator' => 'https://s.tradingview.com/widgetembed/', 'ref_entity_principal_id' => 1, 'attr_recorded_at' => date('Y-m-d H:i:s'), 'attr_origin_source' => 'SOURCE_1'],
                ['attr_header_title' => 'Bank Indonesia BI-Rate Official Data', 'attr_link_locator' => 'https://www.bi.go.id/bi-rate', 'ref_entity_principal_id' => 1, 'attr_recorded_at' => date('Y-m-d H:i:s'), 'attr_origin_source' => 'SOURCE_2'],
            ];
            $this->db->table('ext_resource_record')->insertBatch($resources);
        }

        // 22. media_entity_nano_link
        if ($this->db->table('media_entity_nano_link')->countAllResults() == 0) {
            $articles = $this->db->table('entity_nano')->limit(10)->get()->getResultArray();
            $mediaLinks = [];
            foreach ($articles as $mIdx => $art) {
                $mediaLinks[] = [
                    'ref_entity_media_id' => ($mIdx % 24) + 1,
                    'ref_entity_nano_id'  => (int)$art['ref_entity_nano_id'],
                    'display_position'    => 1,
                    'caption_text'        => 'Dokumentasi riset redaksi Investor.id terkait ' . $art['headline_text'],
                ];
            }
            if (!empty($mediaLinks)) {
                $this->db->table('media_entity_nano_link')->insertBatch($mediaLinks);
            }
        }

        // 23. notif_device_registry
        if ($this->db->table('notif_device_registry')->countAllResults() == 0) {
            $devices = [
                ['device_uid' => 'dev-ios-a1b2c3d4', 'push_token' => 'fcm_token_ios_vip_001', 'os_type' => 'type_a', 'os_version_val' => '17.5', 'app_version_val' => '2.4.1', 'notif_flag' => 'true', 'registered_at' => date('Y-m-d H:i:s', strtotime('-1 month')), 'last_active_at' => date('Y-m-d H:i:s'), 'owner_tag' => 'VIP'],
                ['device_uid' => 'dev-android-e5f6g7h8', 'push_token' => 'fcm_token_android_vip_002', 'os_type' => 'type_b', 'os_version_val' => '14.0', 'app_version_val' => '2.4.5', 'notif_flag' => 'true', 'registered_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')), 'last_active_at' => date('Y-m-d H:i:s'), 'owner_tag' => 'PRIORITY'],
                ['device_uid' => 'dev-android-i9j0k1l2', 'push_token' => 'fcm_token_android_ret_003', 'os_type' => 'type_b', 'os_version_val' => '13.0', 'app_version_val' => '2.4.5', 'notif_flag' => 'true', 'registered_at' => date('Y-m-d H:i:s', strtotime('-5 days')), 'last_active_at' => date('Y-m-d H:i:s'), 'owner_tag' => 'RETAIL'],
            ];
            $this->db->table('notif_device_registry')->insertBatch($devices);
        }

        // 24. notif_device_dispatch
        if ($this->db->table('notif_device_dispatch')->countAllResults() == 0) {
            $dispatches = [
                [
                    'ref_push_id' => 1, 'ref_entity_device_id' => 1, 'device_token' => 'fcm_token_ios_vip_001',
                    'push_type' => 'breaking', 'push_url' => '/artikel/ihsg-menguat-tajam-menembus-7300',
                    'target_direction' => 'outbound', 'push_title' => 'BREAKING NEWS: IHSG Menembus Level 7.300',
                    'push_body' => 'IHSG melesat ditopang akumulasi beli asing pada saham perbankan dan tambang.',
                    'push_image' => '/img/investor_placeholder.jpg', 'status_code' => 'success',
                    'log_message_text' => 'Delivered to APNs successfully in 45ms', 'ref_entity_nano_id' => 1,
                    'category_name' => 'Market', 'push_time' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'push_instance_num' => 1
                ],
                [
                    'ref_push_id' => 2, 'ref_entity_device_id' => 2, 'device_token' => 'fcm_token_android_vip_002',
                    'push_type' => 'market_flash', 'push_url' => '/artikel/bbca-umumkan-jadwal-dividen-jumbo',
                    'target_direction' => 'outbound', 'push_title' => 'MARKET FLASH: BBCA Jadwalkan Cum Dividen',
                    'push_body' => 'Bank Central Asia menetapkan cum dividen tunai per saham dengan total Rp 33,2 triliun.',
                    'push_image' => '/img/article2.jpg', 'status_code' => 'success',
                    'log_message_text' => 'Delivered to FCM gateway successfully in 38ms', 'ref_entity_nano_id' => 2,
                    'category_name' => 'Market', 'push_time' => date('Y-m-d H:i:s', strtotime('-3 hours')), 'push_instance_num' => 1
                ],
            ];
            $this->db->table('notif_device_dispatch')->insertBatch($dispatches);
        }

        // 25. notif_dispatch_cron
        if ($this->db->table('notif_dispatch_cron')->countAllResults() == 0) {
            $crons = [
                ['status_code' => 1, 'push_start_at' => date('Y-m-d 08:30:00'), 'push_end_at' => date('Y-m-d 08:30:05'), 'range_start_id' => 1, 'range_end_id' => 150],
                ['status_code' => 1, 'push_start_at' => date('Y-m-d 16:30:00'), 'push_end_at' => date('Y-m-d 16:30:08'), 'range_start_id' => 151, 'range_end_id' => 300],
            ];
            $this->db->table('notif_dispatch_cron')->insertBatch($crons);
        }

        // 26. sub_content_record & sys_auth_token_entity
        if ($this->db->table('sub_content_record')->countAllResults() == 0) {
            $subContents = [
                [
                    'uniq_id'              => md5('sub_ch1_vip_report'),
                    'ref_entity_client_id' => 1,
                    'attr_header_title'    => 'Bagian 1: Analisis Rasio Permodalan Bank Mandiri & BRI',
                    'attr_preview_asset'   => 'chart_preview_1',
                    'attr_slug_token'      => 'bagian-1-analisis-rasio-permodalan',
                    'attr_brief_summary'   => 'Tinjauan mendalam permodalan CAR dan likuiditas LDR bank BUMN.',
                    'attr_content_body'    => '<p>Pertumbuhan aset produktif perbankan nasional mencatat pertumbuhan dua digit sepanjang semester ini didukung stabilitas marjin bunga bersih (NIM)...</p>',
                    'attr_created_at'      => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'attr_released_at'     => date('Y-m-d H:i:s', strtotime('-9 days')),
                    'attr_released_by_id'  => 1,
                    'attr_status_code'     => 'STATE_1',
                    'attr_metric_counter'  => 450,
                    'attr_compact_locator' => '/reports/banking-q2-p1',
                ],
                [
                    'uniq_id'              => md5('sub_ch2_vip_report'),
                    'ref_entity_client_id' => 2,
                    'attr_header_title'    => 'Bagian 2: Stress-Test Penyaluran Kredit Sektor Riil',
                    'attr_preview_asset'   => 'chart_preview_2',
                    'attr_slug_token'      => 'bagian-2-stress-test-kredit-riil',
                    'attr_brief_summary'   => 'Dampak tren suku bunga terhadap kualitas aset NPL dan LAR perbankan.',
                    'attr_content_body'    => '<p>Sektor manufaktur dan konsumsi domestik menunjukkan ketahanan yang kuat menghadapi penyesuaian biaya dana (cost of funds)...</p>',
                    'attr_created_at'      => date('Y-m-d H:i:s', strtotime('-8 days')),
                    'attr_released_at'     => date('Y-m-d H:i:s', strtotime('-7 days')),
                    'attr_released_by_id'  => 1,
                    'attr_status_code'     => 'STATE_1',
                    'attr_metric_counter'  => 380,
                    'attr_compact_locator' => '/reports/banking-q2-p2',
                ]
            ];
            $this->db->table('sub_content_record')->insertBatch($subContents);
        }

        if ($this->db->table('sys_auth_token_entity')->countAllResults() == 0) {
            $tokens = [
                ['ref_entity_auth_token_id' => 1, 'attr_verification_code' => 849201, 'attr_expiry_at' => date('Y-m-d H:i:s', strtotime('+30 days')), 'attr_usage_flag' => 'VAL_1'],
                ['ref_entity_auth_token_id' => 2, 'attr_verification_code' => 610394, 'attr_expiry_at' => date('Y-m-d H:i:s', strtotime('+15 days')), 'attr_usage_flag' => 'VAL_0'],
                ['ref_entity_auth_token_id' => 3, 'attr_verification_code' => 459128, 'attr_expiry_at' => date('Y-m-d H:i:s', strtotime('+7 days')),  'attr_usage_flag' => 'VAL_1'],
            ];
            $this->db->table('sys_auth_token_entity')->insertBatch($tokens);
        }

        echo "CmsDataSeeder finished successfully!\n";
    }
}
