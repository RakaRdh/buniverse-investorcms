<?php

namespace App\Controllers;

use App\Models\MediaModel;

class Media extends BaseController
{
    protected MediaModel $mediaModel;

    public function __construct()
    {
        $this->mediaModel = new MediaModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('q');
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 6;

        $result = $this->mediaModel->getMediaWithRelations($page, $perPage, $keyword);
        $totalItems = $result['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;

        return view('media/index', [
            'title'      => 'Media Management',
            'breadcrumb' => 'Media / Library',
            'media'      => $result['data'],
            'keyword'    => $keyword ?? '',
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'from'       => $totalItems > 0 ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $totalItems),
            ]
        ]);
    }

    public function new()
    {
        return view('media/upload', [
            'title'      => 'Upload Media',
            'breadcrumb' => 'Media / Upload New',
            'media'      => null,
            'isEdit'     => false
        ]);
    }

    public function edit($id)
    {
        $item = $this->mediaModel->find((int)$id);

        if ($item) {
            $item = [
                'id'      => $item['mediaID'],
                'title'   => $item['mediaTitle'],
                'image'   => $item['filename'],
                'caption' => $item['caption'],
                'source'  => $item['source'],
            ];
        }

        $usedInArticles = $this->mediaModel->getArticlesUsingMedia((int)$id);

        return view('media/upload', [
            'title'          => 'Edit Media Information',
            'breadcrumb'     => 'Media / Edit',
            'media'          => $item,
            'isEdit'         => true,
            'usedInArticles' => $usedInArticles,
        ]);
    }

    public function save()
    {
        $sessionUser = session()->get('user_id') ?: 1;
        $username = session()->get('user_username') ?: 'raka_h';

        $imagePath = $this->request->getPost('image');

        // Handle local file upload if provided
        $file = $this->request->getFile('media_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadDir = FCPATH . 'uploads/media';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $newName = $file->getRandomName();
            $file->move($uploadDir, $newName);
            $imagePath = '/uploads/media/' . $newName;
        }

        if (empty($imagePath)) {
            $imagePath = '/img/investor_placeholder.jpg';
        }

        $mediaTitle = $this->request->getPost('title') ?? 'Media Item';
        $caption = $this->request->getPost('caption') ?? '';

        $this->mediaModel->insert([
            'mediaTitle'  => $mediaTitle,
            'caption'     => $caption,
            'description' => $caption,
            'filename'    => $imagePath,
            'source'      => $this->request->getPost('source') ?: 'Investor.id / Riset',
            'author'      => $username,
            'userID'      => $sessionUser,
            'timestamp'   => date('Y-m-d H:i:s'),
        ]);

        // Production convention: log insert foto with JSON details
        $activityLog = new \App\Models\ActivityLogModel();
        $activityLog->logAction((int)$sessionUser, 'insert', 'foto', [
            'mediaTitle' => $mediaTitle,
            'caption'    => $caption,
        ]);

        return redirect()->to('/media')->with('success', 'Media asset berhasil disimpan ke database!');
    }

    public function update($id)
    {
        $mediaTitle = $this->request->getPost('title');
        $caption = $this->request->getPost('caption');

        $updateData = [
            'mediaTitle'  => $mediaTitle,
            'caption'     => $caption,
            'description' => $caption,
            'source'      => $this->request->getPost('source') ?: 'Investor.id / Riset',
        ];

        // Check for local file upload first
        $file = $this->request->getFile('media_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadDir = FCPATH . 'uploads/media';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $newName = $file->getRandomName();
            $file->move($uploadDir, $newName);
            $updateData['filename'] = '/uploads/media/' . $newName;
        } elseif ($this->request->getPost('image')) {
            $updateData['filename'] = $this->request->getPost('image');
        }

        $this->mediaModel->update((int)$id, $updateData);

        // Production convention: log edit foto with JSON details
        $sessionUser = session()->get('user_id') ?: 1;
        $activityLog = new \App\Models\ActivityLogModel();
        $activityLog->logAction((int)$sessionUser, 'edit', 'foto', [
            'mediaTitle' => $mediaTitle,
            'caption'    => $caption,
        ]);

        return redirect()->to('/media')->with('success', 'Media information updated!');
    }

    public function delete($id)
    {
        $this->mediaModel->delete((int)$id);
        return redirect()->to('/media')->with('success', 'Media asset removed.');
    }
}
