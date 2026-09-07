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
        $kanals = $this->categoryModel->getKanalTree();

        return view('kanal/master', [
            'title'      => 'Master Kanal Management',
            'breadcrumb' => 'Kanal / Master Kanal',
            'kanals'     => $kanals
        ]);
    }

    public function subKanal()
    {
        $kanals = $this->categoryModel->getKanalTree();
        $subList = $this->categoryModel->getSubKanalsList();

        return view('kanal/sub_kanal', [
            'title'      => 'Sub Kanal Management',
            'breadcrumb' => 'Kanal / Sub Kanal',
            'kanals'     => $kanals,
            'subKanals'  => $subList
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
                'charlie_name_a' => $name,
                'field_label_a'  => url_title($name, '-', true),
                'parent_id'      => null,
                'status_code'    => 1,
                'numeric_val'    => $this->categoryModel->countAllResults() + 1,
            ]);
            $newId = $this->categoryModel->insertID();
            $this->categoryModel->syncBackup($newId);
        }

        return redirect()->to('/kanal/master')->with('success', 'Master Kanal berhasil ditambahkan ke database!');
    }

    public function saveSub()
    {
        $parentId = (int)$this->request->getPost('parent_id');
        $name = trim($this->request->getPost('name') ?? '');

        if (!empty($name) && $parentId > 0) {
            $this->categoryModel->insert([
                'charlie_name_a' => $name,
                'field_label_a'  => url_title($name, '-', true),
                'parent_id'      => $parentId,
                'status_code'    => 1,
                'numeric_val'    => 1,
            ]);
            $newId = $this->categoryModel->insertID();
            $this->categoryModel->syncBackup($newId);
        }

        return redirect()->to('/kanal/sub')->with('success', 'Sub Kanal berhasil ditambahkan ke database!');
    }
}
