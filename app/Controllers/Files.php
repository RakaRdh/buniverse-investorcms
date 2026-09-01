<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Files extends BaseController
{
    public function index()
    {
        $files = DataRepository::get('files_extended') ?? [];
        if (empty($files)) {
            $files = [
                [
                    'id' => 1,
                    'name' => 'Prospektus RSGK',
                    'date' => '22/02/22 11:29',
                    'upload_by' => 'radyan',
                    'url' => 'https://img.beritasatu.com/investor/1645504179.pdf'
                ],
                [
                    'id' => 2,
                    'name' => 'Test Share Google [2][x]',
                    'date' => '18/04/22 13:08',
                    'upload_by' => 'radyan',
                    'url' => 'https://img.beritasatu.com/investor/1645512631.pdf'
                ],
                [
                    'id' => 3,
                    'name' => 'Laporan Keuangan Semester I 2026 PT Telkom Indonesia',
                    'date' => '31/08/26 15:40',
                    'upload_by' => 'andika_m',
                    'url' => 'https://investor.id/docs/financial_tlkm_2026_q2.pdf'
                ],
                [
                    'id' => 4,
                    'name' => 'Buku Pedoman Redaksi Investor Daily 2026',
                    'date' => '25/08/26 09:15',
                    'upload_by' => 'natasha_k',
                    'url' => 'https://investor.id/docs/editorial_handbook_2026.pdf'
                ]
            ];
            DataRepository::save('files_extended', $files);
        }

        $keyword = $this->request->getGet('q');
        if ($keyword) {
            $files = array_filter($files, fn($f) => 
                stripos($f['name'], $keyword) !== false || 
                stripos($f['upload_by'], $keyword) !== false
            );
        }

        return view('files/index', [
            'title' => 'Files Management',
            'breadcrumb' => 'Files / All Files',
            'files' => array_values($files),
            'keyword' => $keyword ?? ''
        ]);
    }

    public function upload()
    {
        $files = DataRepository::get('files_extended') ?? [];
        $name = $this->request->getPost('name');
        $url = $this->request->getPost('url') ?: 'https://img.beritasatu.com/investor/' . time() . '.pdf';
        
        $newId = count($files) > 0 ? max(array_column($files, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'name' => $name,
            'date' => date('d/m/y H:i'),
            'upload_by' => 'andika_m',
            'url' => $url
        ];

        array_unshift($files, $newItem);
        DataRepository::save('files_extended', $files);

        return redirect()->to('/files')->with('success', 'File registered successfully.');
    }

    public function delete($id)
    {
        $files = DataRepository::get('files_extended') ?? [];
        $files = array_values(array_filter($files, fn($f) => $f['id'] != $id));
        DataRepository::save('files_extended', $files);

        return redirect()->to('/files')->with('success', 'File deleted.');
    }
}
