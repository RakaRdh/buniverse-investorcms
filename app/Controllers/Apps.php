<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Apps extends BaseController
{
    public function version()
    {
        $versions = DataRepository::get('app_versions');
        return view('apps/version', [
            'title' => 'Apps — Version Management',
            'breadcrumb' => 'Apps / Version',
            'versions' => $versions
        ]);
    }

    public function saveVersion()
    {
        $versions = DataRepository::get('app_versions');
        $platform = $this->request->getPost('platform');
        $version = $this->request->getPost('version');
        $changelog = $this->request->getPost('changelog');
        
        $newId = count($versions) > 0 ? max(array_column($versions, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'app' => 'Investor.id Mobile',
            'platform' => $platform,
            'version' => $version,
            'build' => rand(4300, 4999),
            'release_date' => date('d M Y'),
            'status' => 'Published',
            'mandatory' => false,
            'changelog' => $changelog
        ];

        array_unshift($versions, $newItem);
        DataRepository::save('app_versions', $versions);

        return redirect()->to('/apps-version')->with('success', 'Mobile app version release created.');
    }

    public function pushNotif()
    {
        $history = DataRepository::get('push_history') ?? [];
        if (empty($history)) {
            $history = [
                [
                    'id' => 1,
                    'content' => 'Harga Melandai, Emas Perhiasan Diburu',
                    'push_date' => '2026-08-21 14:33:50',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 2,
                    'content' => 'Rupiah Lagi-lagi Dilanda Pelemahan Beruntun',
                    'push_date' => '2026-07-02 16:43:08',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 3,
                    'content' => 'IHSG Melesat, 5 Saham Melonjak hingga 34%',
                    'push_date' => '2026-07-02 12:51:07',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 4,
                    'content' => 'Celios Nilai Kenaikan BBM Nonsubsidi Sudah Tepat',
                    'push_date' => '2026-04-28 15:30:45',
                    'post_by' => 'denny',
                    'status' => 'complete'
                ],
                [
                    'id' => 5,
                    'content' => 'Harga Emas Antam (ANTM) Hari Ini, Kamis 11 Desember 2025: Melonjak Lagi',
                    'push_date' => '2025-12-11 08:59:14',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 6,
                    'content' => 'Purbaya Siapkan Instrumen untuk Pungut Bea Keluar Emas',
                    'push_date' => '2025-12-08 15:48:08',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 7,
                    'content' => 'Saham BUMI dan GOTO Banyak Dibuang Asing!',
                    'push_date' => '2025-12-05 15:34:32',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ],
                [
                    'id' => 8,
                    'content' => 'Harga Emas Perhiasan Sabtu 29 November 2025, Ini Daftar Lengkapnya',
                    'push_date' => '2025-11-29 12:18:24',
                    'post_by' => 'andika',
                    'status' => 'complete'
                ]
            ];
            DataRepository::save('push_history', $history);
        }

        return view('apps/push_notif', [
            'title' => 'Push Notifications Broadcast',
            'breadcrumb' => 'Apps / Push Notif',
            'history' => $history
        ]);
    }

    public function createPushNotif()
    {
        $history = DataRepository::get('push_history') ?? [];
        $title = $this->request->getPost('title');
        
        $newId = count($history) > 0 ? max(array_column($history, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'content' => $title,
            'push_date' => date('Y-m-d H:i:s'),
            'post_by' => 'andika',
            'status' => 'complete'
        ];

        array_unshift($history, $newItem);
        DataRepository::save('push_history', $history);

        return redirect()->to('/apps-push-notif')->with('success', 'Push notification broadcasted successfully!');
    }

    public function featureContent()
    {
        $features = DataRepository::get('feature_content');
        return view('apps/feature_content', [
            'title' => 'Apps — Feature Content',
            'breadcrumb' => 'Apps / Feature Content',
            'features' => $features
        ]);
    }

    public function saveFeatureContent()
    {
        $features = DataRepository::get('feature_content');
        $title = $this->request->getPost('title');
        $badge = $this->request->getPost('badge');
        $image = $this->request->getPost('image');
        
        $newId = count($features) > 0 ? max(array_column($features, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'title' => $title,
            'type' => 'App Carousel Feature',
            'badge' => $badge,
            'order' => count($features) + 1,
            'status' => 'Active',
            'image' => $image ?: 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?w=600&auto=format&fit=crop&q=80',
            'target_url' => '/post/1'
        ];

        array_unshift($features, $newItem);
        DataRepository::save('feature_content', $features);

        return redirect()->to('/apps-feature-content')->with('success', 'App feature content card saved.');
    }
}
