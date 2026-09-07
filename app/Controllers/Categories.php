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
        if (!empty($name)) {
            $this->categoryModel->insert([
                'charlie_name_a' => $name,
                'field_label_a'  => url_title($name, '-', true),
                'status_code'    => 1,
                'numeric_val'    => 1,
            ]);
            $newId = $this->categoryModel->insertID();
            $this->categoryModel->syncBackup($newId);
        }

        return redirect()->to('/kanal/master')->with('success', 'Category created successfully!');
    }

    public function delete($id)
    {
        $this->categoryModel->delete((int)$id);
        return redirect()->to('/kanal/master')->with('success', 'Category removed.');
    }
}
