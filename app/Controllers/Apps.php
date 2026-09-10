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
        // Version settings (kept lightweight)
        $versions = [
            [
                'id'           => 1,
                'app'          => 'Investor.id Mobile',
                'platform'     => 'iOS (App Store)',
                'version'      => 'v4.2.1',
                'build'        => '4210',
                'release_date' => '28 Aug 2026',
                'status'       => 'Published',
                'mandatory'    => false,
                'changelog'    => 'New live market widget, performance optimization on push alerts.'
            ],
            [
                'id'           => 2,
                'app'          => 'Investor.id Mobile',
                'platform'     => 'Android (Play Store)',
                'version'      => 'v4.2.0',
                'build'        => '4205',
                'release_date' => '25 Aug 2026',
                'status'       => 'Published',
                'mandatory'    => false,
                'changelog'    => 'Added dark mode support, stock watchlist sync improvements.'
            ]
        ];

        return view('apps/version', [
            'title'      => 'Apps — Version Management',
            'breadcrumb' => 'Apps / Version',
            'versions'   => $versions
        ]);
    }

    public function saveVersion()
    {
        return redirect()->to('/apps-version')->with('success', 'Mobile app version release created.');
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
        if ($pushType === 'NewsLanding') {
            $articleId = (int)($this->request->getPost('article_id') ?? 0);
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
        ]);

        return redirect()->to('/apps-push-notif')->with('success', 'Push notification recorded to database successfully!');
    }

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

        $data = [
            'type'      => $type,
            'title'     => $title,
            'tag'       => ($type === 'tag') ? $badge : '',
            'setby'     => $username,
            'setdate'   => date('Y-m-d H:i:s'),
            'imageUrl'  => $image ?: '/img/investor_placeholder.jpg',
            'linkUrl'   => ($type === 'banner') ? ($this->request->getPost('link_url') ?: '/post/1') : '',
            'position'  => 'top',
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
