<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header with Summary KPIs -->
<div class="mb-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Direktori Member &amp; Klien</h1>
            <p class="mt-1 text-xs text-muted-foreground">Kelola basis data subscriber dan pembaca terdaftar Investor.id (Tabel Client Account).</p>
        </div>
        <button type="button" onclick="openMemberModal()" class="inline-flex items-center gap-2 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
            <i data-lucide="user-plus" class="size-4"></i>
            <span>Tambah Member Baru</span>
        </button>
    </div>

    <!-- Stat Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="surface-card p-4 rounded-xl border border-border shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground font-medium">Total Member</span>
                <i data-lucide="users" class="size-4 text-primary"></i>
            </div>
            <p class="text-2xl font-bold text-foreground mt-2 font-mono"><?= number_format($stats['total'] ?? 0) ?></p>
            <span class="text-[10px] text-primary font-medium flex items-center gap-1 mt-1">
                <i data-lucide="check" class="size-3"></i> client_account_entity
            </span>
        </div>

        <div class="surface-card p-4 rounded-xl border border-border shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground font-medium">Member Aktif</span>
                <i data-lucide="check-circle" class="size-4 text-emerald-500"></i>
            </div>
            <p class="text-2xl font-bold text-foreground mt-2 font-mono"><?= number_format($stats['active'] ?? 0) ?></p>
            <span class="text-[10px] text-emerald-600 font-medium mt-1">Status 1 (Active)</span>
        </div>

        <div class="surface-card p-4 rounded-xl border border-border shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground font-medium">Pending Review</span>
                <i data-lucide="clock" class="size-4 text-amber-500"></i>
            </div>
            <p class="text-2xl font-bold text-foreground mt-2 font-mono"><?= number_format($stats['pending'] ?? 0) ?></p>
            <span class="text-[10px] text-amber-600 font-medium mt-1">Status 2 (Pending)</span>
        </div>

        <div class="surface-card p-4 rounded-xl border border-border shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground font-medium">Suspended / Nonaktif</span>
                <i data-lucide="shield-alert" class="size-4 text-rose-500"></i>
            </div>
            <p class="text-2xl font-bold text-foreground mt-2 font-mono"><?= number_format($stats['suspended'] ?? 0) ?></p>
            <span class="text-[10px] text-rose-600 font-medium mt-1">Status 3 (Suspended)</span>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border shadow-2xs">
    <form method="get" action="<?= base_url('member') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
            <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari nama lengkap, email, atau institusi/perusahaan..." class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select name="status" class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                <option value="all">Semua Status</option>
                <option value="Active" <?= ($filters['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active (Aktif)</option>
                <option value="Pending" <?= ($filters['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending (Menunggu)</option>
                <option value="Suspended" <?= ($filters['status'] ?? '') === 'Suspended' ? 'selected' : '' ?>>Suspended (Nonaktif)</option>
            </select>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($filters['q']) || ($filters['status'] ?? 'all') !== 'all'): ?>
                <a href="<?= base_url('member') ?>" class="rounded-md border border-input bg-background px-3 py-2 text-xs text-muted-foreground hover:text-foreground">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Members Table -->
<div class="surface-card rounded-xl border border-border overflow-hidden shadow-2xs">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-border bg-muted/50 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                    <th class="py-3 px-4">Nama Lengkap &amp; Email Kontak</th>
                    <th class="py-3 px-4">Institusi / Perusahaan</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Terdaftar Sejak</th>
                    <th class="py-3 px-4">Login Terakhir</th>
                    <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-muted-foreground">
                            Tidak ada data member yang sesuai filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr class="hover:bg-muted/30 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="grid size-8 shrink-0 place-items-center rounded-full bg-primary/10 text-primary font-bold text-xs uppercase">
                                    <?= substr(esc($m['full_name']), 0, 2) ?>
                                </div>
                                <div class="min-w-0">
                                    <span class="font-bold text-foreground block"><?= esc($m['full_name']) ?></span>
                                    <span class="text-[11px] text-muted-foreground block truncate font-mono"><?= esc($m['email']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-foreground font-medium">
                            <?= esc($m['company'] ?? '-') ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($m['status'] === 'Active'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            <?php elseif ($m['status'] === 'Pending'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                    <span class="size-1.5 rounded-full bg-amber-500"></span> Pending
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-500/30">
                                    <span class="size-1.5 rounded-full bg-rose-500"></span> Suspended
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 font-mono text-[11px] text-muted-foreground">
                            <?= !empty($m['created_at']) ? date('d M Y', strtotime($m['created_at'])) : '-' ?>
                        </td>
                        <td class="py-3 px-4 font-mono text-[11px] text-muted-foreground">
                            <?= !empty($m['last_login_at']) ? date('d M Y H:i', strtotime($m['last_login_at'])) : '<span class="text-muted-foreground/60 italic">Belum pernah</span>' ?>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" onclick="editMember(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)" class="p-1.5 rounded hover:bg-muted text-muted-foreground hover:text-foreground" title="Edit Member">
                                    <i data-lucide="edit-3" class="size-3.5"></i>
                                </button>
                                <a href="<?= base_url('member/delete/' . $m['id']) ?>" onclick="return confirm('Hapus member <?= esc(addslashes($m['full_name'])) ?> dari tabel client_account_entity & backup?')" class="p-1.5 rounded hover:bg-destructive/10 text-destructive" title="Hapus Member">
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('member')]) ?>
</div>

<!-- Modal Create / Edit Member (Standardized Sizing: max-w-lg) -->
<div id="memberModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="surface-card w-full max-w-lg p-6 shadow-2xl space-y-4 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground flex items-center gap-2" id="memberModalTitle">
                <i data-lucide="user-plus" class="size-4 text-primary"></i>
                <span>Tambah Member Baru</span>
            </h3>
            <button type="button" onclick="closeMemberModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <form method="post" action="<?= base_url('member/save') ?>" class="space-y-3.5">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="memberIdInput">

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Nama Lengkap Member <span class="text-destructive">*</span></label>
                <input type="text" name="full_name" id="modalFullName" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="e.g. Budi Hartono Wijaya">
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Email Kontak <span class="text-destructive">*</span></label>
                <input type="email" name="email" id="modalEmail" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="email@perusahaan.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Perusahaan / Institusi / Ringkasan</label>
                <input type="text" name="company" id="modalCompany" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary" placeholder="e.g. PT Djarum Capital Investama / Retail Trader">
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1">Status Akun</label>
                <select name="status" id="modalStatus" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                    <option value="Active">Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Suspended">Suspended</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-border">
                <button type="button" onclick="closeMemberModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-[#2B6CB0] px-5 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function openMemberModal() {
    document.getElementById('memberModalTitle').innerHTML = '<i data-lucide="user-plus" class="size-4 text-primary"></i><span>Tambah Member Baru</span>';
    document.getElementById('memberIdInput').value = '';
    document.getElementById('modalFullName').value = '';
    document.getElementById('modalEmail').value = '';
    document.getElementById('modalCompany').value = '';
    document.getElementById('modalStatus').value = 'Active';
    document.getElementById('memberModal').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function editMember(m) {
    document.getElementById('memberModalTitle').innerHTML = '<i data-lucide="edit-3" class="size-4 text-primary"></i><span>Edit Data Member: ' + escapeHtml(m.full_name) + '</span>';
    document.getElementById('memberIdInput').value = m.id;
    document.getElementById('modalFullName').value = m.full_name;
    document.getElementById('modalEmail').value = m.email;
    document.getElementById('modalCompany').value = m.company || '';
    document.getElementById('modalStatus').value = m.status;
    document.getElementById('memberModal').style.display = 'flex';
    if (window.lucide) lucide.createIcons();
}

function closeMemberModal() {
    document.getElementById('memberModal').style.display = 'none';
}

function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.ModalUtils) {
        window.ModalUtils.register('memberModal', { onClose: closeMemberModal });
    }
});
</script>
<?= $this->endSection() ?>
