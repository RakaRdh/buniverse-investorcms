<?php

namespace App\Controllers;

use App\Models\FileModel;

class Files extends BaseController
{
    protected FileModel $fileModel;

    public function __construct()
    {
        $this->fileModel = new FileModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('q');
        $page = (int)($this->request->getGet('page') ?? 1);
        $result = $this->fileModel->getFilesWithUploader($keyword, $page, 25);

        return view('files/index', [
            'title'      => 'Files Management',
            'breadcrumb' => 'Files / All Files',
            'files'      => $result['data'],
            'pagination' => $result,
            'keyword'    => $keyword ?? ''
        ]);
    }

    public function upload()
    {
        $name = $this->request->getPost('name');
        $url = $this->request->getPost('url') ?: '/files/download/' . time();
        $sessionUser = session()->get('user_id') ?: 1;

        $this->fileModel->insert([
            'title'     => $name,
            'filename'  => $url,
            'userID'    => $sessionUser,
        ]);

        return redirect()->to('/files')->with('success', 'File registered successfully in database.');
    }

    public function delete($id)
    {
        $this->fileModel->delete((int)$id);
        return redirect()->to('/files')->with('success', 'File deleted.');
    }
}
