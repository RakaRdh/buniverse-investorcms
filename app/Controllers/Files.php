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
        $name = trim((string)($this->request->getPost('name') ?? ''));
        if (empty($name)) {
            return redirect()->to('/files')->with('error', 'Nama berkas (title) wajib diisi.');
        }

        $url = trim((string)($this->request->getPost('url') ?? '')) ?: ('/files/download/' . time());
        $sessionUser = (int)(session()->get('user_id') ?: 1);

        $this->fileModel->insert([
            'title'     => $name,
            'filename'  => $url,
            'userID'    => $sessionUser,
        ]);

        try {
            $activityLog = new \App\Models\ActivityLogModel();
            $activityLog->logAction($sessionUser, 'FILE', 'UPLOAD', "Registered file: {$name}");
        } catch (\Throwable $e) {}

        return redirect()->to('/files')->with('success', 'File registered successfully in database.');
    }

    public function delete($id)
    {
        $sessionUser = (int)(session()->get('user_id') ?: 1);
        $targetId = (int)$id;
        $file = $this->fileModel->find($targetId);
        if ($file) {
            try {
                $activityLog = new \App\Models\ActivityLogModel();
                $activityLog->logAction($sessionUser, 'FILE', 'DELETE', "Deleted file: " . ($file['title'] ?? ('ID #' . $targetId)));
            } catch (\Throwable $e) {}
            $this->fileModel->delete($targetId);
        }
        return redirect()->to('/files')->with('success', 'File deleted.');
    }
}
