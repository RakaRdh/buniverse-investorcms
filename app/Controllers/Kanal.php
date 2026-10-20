<?php

namespace App\Controllers;

use App\Models\CategoryModel;

class Kanal extends BaseController
{
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function master()
    {
        $allKanals = $this->categoryModel->getKanalTree();
        
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 10;
        $totalItems = count($allKanals);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $pagedKanals = array_slice($allKanals, $offset, $perPage);

        return view('kanal/master', [
            'title'      => 'Master Kanal Management',
            'breadcrumb' => 'Kanal / Master Kanal',
            'kanals'     => $pagedKanals,
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

    public function subKanal()
    {
        $kanals = $this->categoryModel->getKanalTree();
        $allSubs = $this->categoryModel->getSubKanalsList();

        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 10;
        $totalItems = count($allSubs);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $pagedSubs = array_slice($allSubs, $offset, $perPage);

        return view('kanal/sub_kanal', [
            'title'      => 'Sub Kanal Management',
            'breadcrumb' => 'Kanal / Sub Kanal',
            'kanals'     => $kanals,
            'subKanals'  => $pagedSubs,
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

    public function stats()
    {
        $kanalPerformance = $this->categoryModel->getKanalPerformance();

        $totalArticles = array_sum(array_column($kanalPerformance, 'total_articles'));
        $totalViews = array_sum(array_column($kanalPerformance, 'total_views'));

        return view('kanal/stats', [
            'title'            => 'Performa Kanal & Distribusi Audiens',
            'breadcrumb'       => 'Kanal / Performa Kanal',
            'kanalPerformance' => $kanalPerformance,
            'totalArticles'    => $totalArticles,
            'totalViews'       => $totalViews,
        ]);
    }

    public function saveMaster()
    {
        $name = trim($this->request->getPost('name') ?? '');
        if (!empty($name)) {
            $this->categoryModel->insert([
                'categoryName' => url_title($name, '-', true),
                'label'        => $name,
                'parent'       => null,
                'status'       => 1,
                'num'          => $this->categoryModel->countAllResults() + 1,
            ]);
            $newId = $this->categoryModel->insertID();
        }

        return redirect()->to('/kanal/master')->with('success', 'Master Kanal berhasil ditambahkan ke database!');
    }

    public function saveSub()
    {
        $parentId = (int)$this->request->getPost('parent_id');
        $name = trim($this->request->getPost('name') ?? '');

        if (!empty($name) && $parentId > 0) {
            $this->categoryModel->insert([
                'categoryName' => url_title($name, '-', true),
                'label'        => $name,
                'parent'       => $parentId,
                'status'       => 1,
                'num'          => 1,
            ]);
            $newId = $this->categoryModel->insertID();
        }

        return redirect()->to('/kanal/sub')->with('success', 'Sub Kanal berhasil ditambahkan ke database!');
    }
}
