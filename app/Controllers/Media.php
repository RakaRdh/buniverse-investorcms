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
                'id'      => $item['ref_entity_media_id'],
                'title'   => $item['attr_resource_title'],
                'image'   => $item['attr_resource_name'],
                'caption' => $item['attr_short_description'],
                'source'  => $item['attr_origin_source'],
            ];
        }

        return view('media/upload', [
            'title'      => 'Edit Media Information',
            'breadcrumb' => 'Media / Edit',
            'media'      => $item,
            'isEdit'     => true
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
            $imagePath = '/img/article1.jpg';
        }

        $this->mediaModel->insert([
            'attr_resource_title'    => $this->request->getPost('title') ?? 'Media Item',
            'attr_short_description' => $this->request->getPost('caption') ?? '',
            'attr_full_description'  => $this->request->getPost('caption') ?? '',
            'attr_resource_name'     => $imagePath,
            'attr_origin_source'     => $this->request->getPost('source') ?: 'Investor.id / Riset',
            'attr_aubert_name'       => $username,
            'ref_entity_principal_id'=> $sessionUser,
        ]);

        return redirect()->to('/media')->with('success', 'Media asset berhasil disimpan ke database!');
    }

    public function update($id)
    {
        $updateData = [
            'attr_resource_title'    => $this->request->getPost('title'),
            'attr_short_description' => $this->request->getPost('caption'),
            'attr_full_description'  => $this->request->getPost('caption'),
            'attr_origin_source'     => $this->request->getPost('source') ?: 'Investor.id / Riset',
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
            $updateData['attr_resource_name'] = '/uploads/media/' . $newName;
        } elseif ($this->request->getPost('image')) {
            $updateData['attr_resource_name'] = $this->request->getPost('image');
        }

        $this->mediaModel->update((int)$id, $updateData);

        return redirect()->to('/media')->with('success', 'Media information updated!');
    }

    public function delete($id)
    {
        $this->mediaModel->delete((int)$id);
        return redirect()->to('/media')->with('success', 'Media asset removed.');
    }
}
