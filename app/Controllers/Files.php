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
        $files = $this->fileModel->getFilesWithUploader($keyword);

        return view('files/index', [
            'title'      => 'Files Management',
            'breadcrumb' => 'Files / All Files',
            'files'      => $files,
            'keyword'    => $keyword ?? ''
        ]);
    }

    public function upload()
    {
        $name = $this->request->getPost('name');
        $url = $this->request->getPost('url') ?: '/files/download/' . time();
        $sessionUser = session()->get('user_id') ?: 1;

        $this->fileModel->insert([
            'attr_header_title'      => $name,
            'attr_resource_name'     => $url,
            'ref_entity_principal_id'=> $sessionUser,
        ]);

        return redirect()->to('/files')->with('success', 'File registered successfully in database.');
    }

    public function delete($id)
    {
        $this->fileModel->delete((int)$id);
        return redirect()->to('/files')->with('success', 'File deleted.');
    }
}
