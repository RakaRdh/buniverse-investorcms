<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Categories extends BaseController
{
    public function index()
    {
        $categories = DataRepository::get('categories');
        return view('categories/index', [
            'title' => 'Category Management',
            'breadcrumb' => 'Management / Categories',
            'categories' => $categories
        ]);
    }

    public function save()
    {
        $categories = DataRepository::get('categories');
        $name = $this->request->getPost('name');
        
        $newId = count($categories) > 0 ? max(array_column($categories, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'name' => $name,
            'slug' => url_title($name, '-', true),
            'count' => 0,
            'status' => 'Active',
            'updated' => date('d M Y H:i')
        ];

        $categories[] = $newItem;
        DataRepository::save('categories', $categories);

        return redirect()->to('/categories')->with('success', 'Category created successfully!');
    }

    public function delete($id)
    {
        $categories = DataRepository::get('categories');
        $categories = array_filter($categories, fn($c) => $c['id'] != $id);
        DataRepository::save('categories', array_values($categories));

        return redirect()->to('/categories')->with('success', 'Category removed.');
    }
}
