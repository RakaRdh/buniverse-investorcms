<?php

namespace App\Controllers;

use App\Models\CategoryModel;

class Categories extends BaseController
{
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->getKanalTree();
        return redirect()->to('/kanal/master');
    }

    public function save()
    {
        $name = trim($this->request->getPost('name') ?? '');
        if (empty($name)) {
            return redirect()->to('/kanal/master')->withInput()->with('error', 'Nama kategori tidak boleh kosong.');
        }

        $this->categoryModel->insert([
            'categoryName' => url_title($name, '-', true),
            'label'        => $name,
            'parent'       => ($this->request->getPost('parent') > 0) ? (int)$this->request->getPost('parent') : null,
            'status'       => 1,
            'num'          => 1,
        ]);

        return redirect()->to('/kanal/master')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function delete($id)
    {
        $this->categoryModel->delete((int)$id);
        return redirect()->to('/kanal/master')->with('success', 'Category removed.');
    }
}
