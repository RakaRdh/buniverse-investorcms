<?php

namespace App\Controllers;

use App\Models\DataRepository;

class PostPreview extends BaseController
{
    public function show($id)
    {
        $posts = DataRepository::get('posts') ?? [];
        $article = null;
        foreach ($posts as $p) {
            if ($p['id'] == $id) {
                $article = $p;
                break;
            }
        }

        if (!$article) {
            return redirect()->to('/post')->with('error', 'Artikel tidak ditemukan.');
        }

        return view('post/preview', [
            'title' => 'Live Preview: ' . $article['title'],
            'article' => $article,
            'isFromForm' => false
        ]);
    }

    public function fromForm()
    {
        $points = [
            $this->request->getPost('point_1') ?: '',
            $this->request->getPost('point_2') ?: '',
            $this->request->getPost('point_3') ?: ''
        ];
        $points = array_values(array_filter($points, fn($pt) => trim($pt) !== ''));

        $article = [
            'id' => 0,
            'title' => $this->request->getPost('title') ?: 'Judul Artikel Masih Kosong',
            'upper_deck' => $this->request->getPost('upper_deck') ?: '',
            'author' => $this->request->getPost('author') ?: 'Redaksi Investor.id',
            'category' => $this->request->getPost('sub_kanal') ? ($this->request->getPost('kanal') . ' ' . $this->request->getPost('sub_kanal')) : ($this->request->getPost('kanal') ?: 'Market'),
            'kanal' => $this->request->getPost('kanal') ?: 'Market',
            'sub_kanal' => $this->request->getPost('sub_kanal') ?: '',
            'sumber_berita' => $this->request->getPost('source') ?: 'Investor.id / Riset',
            'publish_date' => $this->request->getPost('publish_date') ?: date('d M Y H:i'),
            'status' => $this->request->getPost('status') ?: 'Draft',
            'is_premium' => (int)($this->request->getPost('is_premium') ?: 0),
            'thumbnail' => $this->request->getPost('thumbnail') ?: '/img/article1.jpg',
            'summary' => $this->request->getPost('summary') ?: 'Ringkasan artikel pengantar belum diisi.',
            'content' => $this->request->getPost('content') ?: '<p>Konten naskah berita masih kosong.</p>',
            'tags' => $this->request->getPost('tags') ?: '',
            'points' => $points,
        ];

        return view('post/preview', [
            'title' => 'Live Preview: ' . $article['title'],
            'article' => $article,
            'isFromForm' => true
        ]);
    }
}
