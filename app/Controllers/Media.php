<?php

namespace App\Controllers;

use App\Models\MediaModel;
use App\Libraries\ImageProcessor;

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

        // Handle local file upload if provided (MIME validated & memory re-encoded via ImageProcessor)
        $file = $this->request->getFile('media_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return redirect()->back()->withInput()->with('error', 'Format file tidak didukung. Harap gunakan format JPG, PNG, atau WebP.');
            }

            $imageProcessor = new ImageProcessor();
            $processedUrl = $imageProcessor->processUploadedFile($file, 'media');
            if ($processedUrl) {
                $imagePath = $processedUrl;
            } else {
                return redirect()->back()->withInput()->with('error', 'Gagal memproses file gambar. Pastikan file gambar valid.');
            }
        }

        if (empty($imagePath)) {
            $imagePath = '/img/investor_placeholder.jpg';
        }

        $mediaTitle = $this->request->getPost('title') ?? 'Media Item';
        $caption = $this->request->getPost('caption') ?? '';
        $description = $this->request->getPost('description') ?: $caption;

        $this->mediaModel->insert([
            'mediaTitle'  => $mediaTitle,
            'caption'     => $caption,
            'description' => $description,
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
        $description = $this->request->getPost('description') ?: $caption;

        $updateData = [
            'mediaTitle'  => $mediaTitle,
            'caption'     => $caption,
            'description' => $description,
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
        $id = (int)$id;
        $media = $this->mediaModel->find($id);
        if (!$media) {
            return redirect()->to('/media')->with('error', 'Media asset tidak ditemukan.');
        }

        // 1. Reverse lookup: cek apakah foto ini sedang dipakai oleh artikel via mediaarticle
        $usedArticles = $this->mediaModel->getArticlesUsingMedia($id);

        // 2. Cek apakah filename foto ini dipakai sebagai thumbnail artikel
        if (!empty($media['filename'])) {
            $thumbArticles = $this->mediaModel->db->table('articlemeta am')
                ->select('a.articleID as id, a.headline as title, a.status')
                ->join('article a', 'a.articleID = am.articleID')
                ->where('am.metakey', 'thumbnail')
                ->where('am.metaval', $media['filename'])
                ->get()->getResultArray();
            foreach ($thumbArticles as $ta) {
                if (!in_array($ta['id'], array_column($usedArticles, 'id'))) {
                    $usedArticles[] = $ta;
                }
            }
        }

        // Jika media masih dipakai oleh artikel aktif, tolak penghapusan untuk melindungi integritas konten
        if (!empty($usedArticles)) {
            $articleTitles = array_map(fn($a) => '"' . mb_substr($a['title'], 0, 40) . '..."', array_slice($usedArticles, 0, 3));
            $countTotal = count($usedArticles);
            $msg = "Gagal menghapus: Foto ini sedang digunakan oleh {$countTotal} artikel redaksi (" . implode(', ', $articleTitles) . "). Lepaskan tautan foto dari artikel terkait sebelum menghapus media ini.";
            return redirect()->to('/media')->with('error', $msg);
        }

        // Audit log penghapusan foto
        $sessionUser = session()->get('user_id') ?: 1;
        try {
            $activityLog = new \App\Models\ActivityLogModel();
            $activityLog->logAction((int)$sessionUser, 'delete', 'foto', [
                'mediaID'    => $id,
                'mediaTitle' => $media['mediaTitle'],
                'filename'   => $media['filename'],
            ]);
        } catch (\Throwable $e) {}

        $this->mediaModel->delete($id);
        return redirect()->to('/media')->with('success', 'Media asset berhasil dihapus dari perpustakaan.');
    }
}
