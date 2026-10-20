<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use App\Models\FeatureContentModel;
use App\Models\DataRepository;

class Apps extends BaseController
{
    protected NotificationModel $notifModel;
    protected FeatureContentModel $featureModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
        $this->featureModel = new FeatureContentModel();
    }

    public function version()
    {
        // Fetch version records from live `apps` table
        $db = \Config\Database::connect();
        $dbVersions = $db->table('Apps')->get()->getResultArray();

        $versions = [];
        foreach ($dbVersions as $row) {
            $versions[] = [
                'id'           => $row['id'],
                'key'          => $row['key'] ?? 'version',
                'app'          => 'Investor.id Mobile',
                'platform'     => 'iOS & Android (Unified API)',
                'version'      => $row['value'],
                'build'        => 'v' . str_replace('.', '', $row['value']),
                'release_date' => 'Live Database Config',
                'status'       => 'Active',
                'mandatory'    => false,
                'changelog'    => 'Mobile API version config record'
            ];
        }

        return view('apps/version', [
            'title'      => 'Apps — Version Management',
            'breadcrumb' => 'Apps / Version',
            'versions'   => $versions
        ]);
    }

    public function saveVersion()
    {
        $id = (int)$this->request->getPost('id');
        $version = trim($this->request->getPost('version') ?? '');
        $key = trim($this->request->getPost('key') ?: 'version');

        if (empty($version)) {
            return redirect()->to('/apps-version')->with('error', 'Nomor versi tidak boleh kosong.');
        }

        $db = \Config\Database::connect();
        if ($id > 0) {
            $db->table('Apps')->where('id', $id)->update([
                'key'   => $key,
                'value' => $version
            ]);
            $msg = 'Versi mobile app berhasil diperbarui di database.';
        } else {
            $db->table('Apps')->insert([
                'key'   => $key,
                'value' => $version
            ]);
            $msg = 'Versi mobile app baru berhasil ditambahkan ke database.';
        }

        return redirect()->to('/apps-version')->with('success', $msg);
    }

    public function deleteVersion(int $id)
    {
        $db = \Config\Database::connect();
        $db->table('Apps')->where('id', $id)->delete();
        return redirect()->to('/apps-version')->with('success', 'Data konfigurasi versi berhasil dihapus dari database.');
    }

    public function pushNotif()
    {
        $history = $this->notifModel->getNotifHistory();

        return view('apps/push_notif', [
            'title'      => 'Push Notifications Broadcast',
            'breadcrumb' => 'Apps / Push Notif',
            'history'    => $history
        ]);
    }

    /**
     * @deprecated LEGACY DEAD CODE — External FCM Push Notification
     * Legacy memanggil Firebase HTTP API / Cron untuk broadcast notifikasi mobile app.
     * Pada CI4, data hanya disimpan ke DB lokal `pushnotif` dan panggilan eksternal FCM di-stub
     * agar terisolasi murni dan tidak ada payload yang keluar ke production / real device.
     */
    public function createPushNotif()
    {
        $title = $this->request->getPost('title') ?? 'Breaking Market Update';
        $pushType = $this->request->getPost('push_type') ?: 'NewsLanding';
        $sessionUser = session()->get('user_id') ?: 1;

        // Push type routing sesuai variasi target legacy
        $pushUrl = '/news';
        $urlTarget = 'inbound';
        $articleId = (int)($this->request->getPost('article_id') ?? 0);
        $categoryName = trim($this->request->getPost('category_name') ?? $this->request->getPost('category') ?? '');

        if ($pushType === 'NewsLanding') {
            $pushUrl = $articleId > 0 ? "/article/detail/{$articleId}" : '/news';
        } elseif ($pushType === 'TVShow') {
            $pushUrl = '/tv/live';
        } elseif ($pushType === 'WebPage') {
            $pushUrl = $this->request->getPost('url') ?: 'https://investor.id';
            $urlTarget = 'outbound';
        }

        $this->notifModel->insert([
            'postBy'         => $sessionUser,
            'pushDate'       => date('Y-m-d H:i:s'),
            'pushType'       => $pushType,
            'pushUrl'        => $pushUrl,
            'urlTarget'      => $urlTarget,
            'pushSlug'       => url_title($title, '-', true),
            'pushTitle'      => $title,
            'pushBody'       => $title,
            'pushImage'      => $this->request->getPost('image') ?: '/img/investor_placeholder.jpg',
            'fcm_message_id' => null,
            'status'         => 'ready',
            'articleID'      => $articleId > 0 ? $articleId : null,
            'categoryName'   => !empty($categoryName) ? $categoryName : null,
        ]);

        return redirect()->to('/apps-push-notif')->with('success', 'Push notification recorded to database successfully!');
    }

    /**
     * [Kategori D - Sengaja Dilewatkan / Placeholder Only]
     * Method stub pengiriman FCM langsung ke server Google Firebase Cloud Messaging.
     * Disediakan sesuai permintaan arsitektur namun di-comment out agar tidak membebani lingkungan lokal/staging.
     *
     * protected function sendFcmDirect(array $payload, string $targetToken): array
     * {
     *     // $fcmServerKey = getenv('FCM_SERVER_KEY');
     *     // $client = \Config\Services::curlrequest();
     *     // $response = $client->post('https://fcm.googleapis.com/fcm/send', [
     *     //     'headers' => [
     *     //         'Authorization' => 'key=' . $fcmServerKey,
     *     //         'Content-Type'  => 'application/json',
     *     //     ],
     *     //     'json' => [
     *     //         'to'           => $targetToken,
     *     //         'notification' => [
     *     //             'title' => $payload['title'],
     *     //             'body'  => $payload['body'],
     *     //             'image' => $payload['image'] ?? null,
     *     //         ],
     *     //         'data' => $payload,
     *     //     ]
     *     // ]);
     *     // return json_decode($response->getBody(), true) ?: ['success' => 1];
     *     return ['success' => 1, 'mock' => true];
     * }
     */

    public function featureContent()
    {
        $features = $this->featureModel->getFeaturesList();

        return view('apps/feature_content', [
            'title'      => 'Apps — Feature Content',
            'breadcrumb' => 'Apps / Feature Content',
            'features'   => $features
        ]);
    }

    public function saveFeatureContent()
    {
        $id = (int)$this->request->getPost('id');
        $title = $this->request->getPost('title');
        $badge = $this->request->getPost('badge');
        $image = $this->request->getPost('image');
        $type = $this->request->getPost('type') ?: (!empty($badge) ? 'tag' : 'banner');
        if (!in_array($type, ['tag', 'banner'])) {
            $type = 'banner';
        }
        $username = session()->get('user_username') ?: 'raka_h';
        $position = trim($this->request->getPost('position') ?? 'top');
        if (empty($position)) {
            $position = 'top';
        }

        $data = [
            'type'      => $type,
            'title'     => $title,
            'tag'       => ($type === 'tag') ? $badge : '',
            'setby'     => $username,
            'setdate'   => date('Y-m-d H:i:s'),
            'imageUrl'  => $image ?: '/img/investor_placeholder.jpg',
            'linkUrl'   => ($type === 'banner') ? ($this->request->getPost('link_url') ?: '/post/1') : '',
            'position'  => $position,
        ];

        if ($id > 0) {
            $this->featureModel->update($id, $data);
            $msg = 'App feature content card updated successfully.';
        } else {
            $data['num'] = $this->featureModel->countAllResults() + 1;
            $this->featureModel->insert($data);
            $msg = 'App feature content card saved to database.';
        }

        return redirect()->to('/apps-feature-content')->with('success', $msg);
    }

    public function deleteFeature(int $id)
    {
        $this->featureModel->delete($id);
        return redirect()->to('/apps-feature-content')->with('success', 'Feature content removed from database.');
    }
}
