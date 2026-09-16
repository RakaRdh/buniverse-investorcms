<?php

namespace App\Controllers;

/**
 * Class Trending
 * 
 * @deprecated FITUR DEPRECATED / DEAD CODE
 * Controller ini adalah stub dari fitur manajemen trending & trending exclude legacy
 * (tabel `tbltrending` dan `tbltrendingexclude`). Fitur ini kemungkinan besar sudah
 * tidak digunakan lagi pada workflow portal modern Investor.id, sehingga seluruh logic
 * dibiarkan ter-comment out agar tidak membebani sistem namun tetap tersedia jika
 * dibutuhkan referensi di masa depan.
 */
class Trending extends BaseController
{
    /*
    public function index()
    {
        $db = \Config\Database::connect();
        
        $trending = $db->table('tbltrending')
            ->orderBy('num', 'ASC')
            ->get()->getResultArray();
            
        $excludes = $db->table('tbltrendingexclude')
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return view('trending/index', [
            'title'    => 'Trending Topics (Deprecated)',
            'trending' => $trending,
            'excludes' => $excludes,
        ]);
    }

    public function save()
    {
        // Deprecated: logic penambahan trending topic
        return redirect()->to('/dashboard')->with('info', 'Fitur Trending Topics sudah tidak aktif (deprecated).');
    }

    public function addExclude()
    {
        // Deprecated: logic exclude keyword trending
        return redirect()->to('/dashboard')->with('info', 'Fitur Trending Exclude sudah tidak aktif (deprecated).');
    }
    */
}
