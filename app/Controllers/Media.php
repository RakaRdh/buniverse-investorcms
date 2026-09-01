<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Media extends BaseController
{
    public function index()
    {
        $media = DataRepository::get('media_extended') ?? [];

        $keyword = $this->request->getGet('q');
        if ($keyword) {
            $media = array_filter($media, fn($m) => 
                stripos($m['title'], $keyword) !== false || 
                stripos($m['caption'], $keyword) !== false ||
                stripos($m['user'], $keyword) !== false
            );
        }

        $filteredMedia = array_values($media);
        $totalItems = count($filteredMedia);
        $perPage = 6;
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $perPage;
        $paginatedMedia = array_slice($filteredMedia, $offset, $perPage);

        return view('media/index', [
            'title' => 'Media Management',
            'breadcrumb' => 'Media / Library',
            'media' => $paginatedMedia,
            'keyword' => $keyword ?? '',
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'from' => $totalItems > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalItems),
            ]
        ]);
    }

    public function new()
    {
        return view('media/upload', [
            'title' => 'Upload Media',
            'breadcrumb' => 'Media / Upload New',
            'media' => null,
            'isEdit' => false
        ]);
    }

    public function edit($id)
    {
        $mediaList = DataRepository::get('media_extended') ?? [];
        $item = null;
        foreach ($mediaList as $m) {
            if ($m['id'] == $id) {
                $item = $m;
                break;
            }
        }

        return view('media/upload', [
            'title' => 'Edit Media Information',
            'breadcrumb' => 'Media / Edit',
            'media' => $item,
            'isEdit' => true
        ]);
    }

    public function save()
    {
        $mediaList = DataRepository::get('media_extended') ?? [];
        $newId = count($mediaList) > 0 ? max(array_column($mediaList, 'id')) + 1 : 1;

        $newItem = [
            'id' => $newId,
            'title' => $this->request->getPost('title'),
            'caption' => $this->request->getPost('caption'),
            'image' => $this->request->getPost('image') ?: 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?w=600&auto=format&fit=crop&q=80',
            'source' => $this->request->getPost('source') ?: '/',
            'date' => date('d/m/y H:i'),
            'user' => 'andika_m'
        ];

        array_unshift($mediaList, $newItem);
        DataRepository::save('media_extended', $mediaList);

        return redirect()->to('/media')->with('success', 'Media asset uploaded successfully!');
    }

    public function update($id)
    {
        $mediaList = DataRepository::get('media_extended') ?? [];
        foreach ($mediaList as &$m) {
            if ($m['id'] == $id) {
                $m['title'] = $this->request->getPost('title');
                $m['caption'] = $this->request->getPost('caption');
                $m['source'] = $this->request->getPost('source') ?: '/';
                if ($this->request->getPost('image')) {
                    $m['image'] = $this->request->getPost('image');
                }
                break;
            }
        }
        DataRepository::save('media_extended', $mediaList);

        return redirect()->to('/media')->with('success', 'Media information updated!');
    }

    public function delete($id)
    {
        $mediaList = DataRepository::get('media_extended') ?? [];
        $mediaList = array_values(array_filter($mediaList, fn($m) => $m['id'] != $id));
        DataRepository::save('media_extended', $mediaList);

        return redirect()->to('/media')->with('success', 'Media asset removed.');
    }
}
