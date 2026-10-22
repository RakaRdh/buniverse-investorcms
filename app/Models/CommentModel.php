<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class CommentModel
 *
 * @category Discontinued / Archival Track Record
 * @description Model representasi tabel `tblCommentar`.
 * Di portal Investor.id modern dan sistem editorial CMS, fitur komentar pembaca
 * telah DISCONTINUED (tidak ada formulir interaksi komentar di front-end dan tidak
 * ada antarmuka moderasi di CMS editorial).
 * Disediakan sebagai track record skema dan preservasi 52 data historis era 2019-2020.
 */
class CommentModel extends Model
{
    protected $table = 'Commentar';
    protected $primaryKey = 'commentID';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'articleID',
        'memberID',
        'replyto',
        'posted',
        'status',
        'comment',
        'replies',
        'likes',
        'dislikes',
    ];
    protected $useTimestamps = false;

    /**
     * Ambil daftar komentar untuk suatu artikel dengan join profil member jika ada
     */
    public function getCommentsByArticle(int $articleId, int $limit = 50): array
    {
        return $this->db->table('Commentar c')
            ->select('c.*, m.fullname, m.email, m.avatar')
            ->join('Member m', 'm.memberID = c.memberID', 'left')
            ->where('c.articleID', $articleId)
            ->where('c.status', '1')
            ->orderBy('c.posted', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Simpan komentar baru
     */
    public function addComment(array $data): int
    {
        $record = [
            'articleID' => (int)($data['articleID'] ?? 0),
            'memberID'  => (int)($data['memberID'] ?? 0),
            'replyto'   => (int)($data['replyto'] ?? 0),
            'posted'    => $data['posted'] ?? date('Y-m-d H:i:s'),
            'status'    => isset($data['status']) ? (string)$data['status'] : '1',
            'comment'   => strip_tags(trim($data['comment'] ?? '')),
            'replies'   => (int)($data['replies'] ?? 0),
            'likes'     => (int)($data['likes'] ?? 0),
            'dislikes'  => (int)($data['dislikes'] ?? 0),
        ];

        return (int)$this->insert($record);
    }

    /**
     * Moderasi status komentar (1 = aktif, 0 = disembunyikan)
     */
    public function setStatus(int $commentId, string $status): bool
    {
        return (bool)$this->update($commentId, ['status' => $status]);
    }
}
