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

    public function createPushNotif()
    {
        $title = $this->request->getPost('title') ?? 'Breaking Market Update';
        $sessionUser = session()->get('user_id') ?: 1;

        $this->notifModel->insert([
            'posted_by'        => $sessionUser,
            'push_date'        => date('Y-m-d H:i:s'),
            'push_type'        => 'broadcast',
            'push_url'         => '/news',
            'target_direction' => 'outbound',
            'push_slug'        => url_title($title, '-', true),
            'push_title'       => $title,
            'push_body'        => $title,
            'push_image'       => '/img/investor_placeholder.jpg',
            'push_message_ref' => 'REF-' . rand(1000, 9999),
            'status_code'      => 'state_1',
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
        $title = $this->request->getPost('title');
        $badge = $this->request->getPost('badge');
        $image = $this->request->getPost('image');
        $username = session()->get('user_username') ?: 'raka_h';

        $this->featureModel->insert([
            'content_type'  => 'Carousel',
            'content_title' => $title,
            'content_tag'   => $badge,
            'set_by'        => $username,
            'set_date'      => date('Y-m-d H:i:s'),
            'image_url'     => $image ?: '/img/investor_placeholder.jpg',
            'link_url'      => '/post/1',
            'position_val'  => 'top',
            'numeric_val'   => $this->featureModel->countAllResults() + 1,
        ]);

        return redirect()->to('/apps-feature-content')->with('success', 'App feature content card saved to database.');
    }
}
