<?php

namespace App\Controllers;

use App\Models\DataRepository;

class User extends BaseController
{
    public function index()
    {
        $users = DataRepository::get('users');
        return view('user/index', [
            'title' => 'User Management & Newsroom Staff',
            'breadcrumb' => 'Management / User',
            'users' => $users
        ]);
    }

    public function save()
    {
        $users = DataRepository::get('users');
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $role = $this->request->getPost('role');
        
        $newId = count($users) > 0 ? max(array_column($users, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'name' => $name,
            'username' => strtolower(explode(' ', $name)[0]) . '_' . rand(10, 99),
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
            'status' => 'Active',
            'last_active' => 'Just registered',
        ];

        $users[] = $newItem;
        DataRepository::save('users', $users);

        return redirect()->to('/user')->with('success', 'User staff account created.');
    }

    public function delete($id)
    {
        $users = DataRepository::get('users');
        $users = array_filter($users, fn($u) => $u['id'] != $id);
        DataRepository::save('users', array_values($users));

        return redirect()->to('/user')->with('success', 'User removed.');
    }
}
