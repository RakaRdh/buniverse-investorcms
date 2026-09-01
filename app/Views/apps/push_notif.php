<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="/css/pages/push_notif.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Top 2-Column Section Matching Gambar 5 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 items-start">
    <!-- Left Panel: Push Notif Form -->
    <div class="surface-card p-6 space-y-4">
        <form method="post" action="<?= base_url('apps-push-notif/create') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-foreground mb-1.5">Tipe Push:</label>
                <select name="type" class="flex h-9 w-44 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="Breaking News">Breaking News</option>
                    <option value="Daily Market Wrap">Daily Market Wrap</option>
                    <option value="Premium Insight">Premium Insight</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1.5">Title</label>
                <input type="text" name="title" id="notifTitleInput" required class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Masukkan judul notifikasi...">
            </div>

            <div>
                <label class="block text-xs font-bold text-foreground mb-1.5">Body Message</label>
                <textarea name="message" id="notifBodyInput" rows="2" required class="flex w-full rounded-md border border-input bg-background p-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Masukkan ringkasan pesan notifikasi..."></textarea>
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#48BB78] px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm hover:bg-[#48BB78]/90 transition-colors">
                SUBMIT PUSH
            </button>
        </form>
    </div>

    <!-- Right Panel: Push Notifikasi Mobile Apps Live Preview Matching Gambar 5 -->
    <div class="surface-card p-6 space-y-3">
        <h2 class="text-base font-bold text-foreground">Push Notifikasi Mobile Apps</h2>
        <p class="text-xs text-muted-foreground">Preview:</p>

        <div class="rounded-lg border border-border bg-background p-4 flex items-center justify-between gap-4 shadow-xs">
            <div class="space-y-1 min-w-0 flex-1">
                <p id="previewTitle" class="font-bold text-xs text-foreground truncate">Judul pesan notifikasi....</p>
                <p id="previewBody" class="text-[11px] text-muted-foreground leading-relaxed">Isi pesan notifikasi yang muncul di device user..</p>
            </div>
            <div class="w-36 h-16 rounded-md bg-[#BEE3F8] text-[#2B6CB0] font-black text-xs uppercase flex items-center justify-center tracking-tight text-center px-2 shrink-0 border border-blue-200">
                INVESTOR DAILY<br><span class="text-[9px] font-semibold text-blue-800">INDONESIA</span>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Push Notif History Table Matching Gambar 5 -->
<div class="surface-card p-6 space-y-4">
    <h2 class="text-lg font-bold text-foreground">Push Notif History</h2>

    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">Content</th>
                    <th class="h-10 px-4 w-48">Push Date</th>
                    <th class="h-10 px-4 w-32">Post By</th>
                    <th class="h-10 px-4 w-28">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="4" class="p-8 text-center text-muted-foreground text-xs">No push notification history available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $item): ?>
                    <tr class="push-history-row transition-colors">
                        <td class="p-4 font-medium text-foreground text-xs">
                            <?= esc($item['content']) ?>
                        </td>
                        <td class="p-4 text-xs font-mono text-muted-foreground whitespace-nowrap">
                            <?= esc($item['push_date']) ?>
                        </td>
                        <td class="p-4 text-xs text-muted-foreground">
                            <?= esc($item['post_by']) ?>
                        </td>
                        <td class="p-4 text-xs text-muted-foreground">
                            <span class="inline-flex items-center text-xs text-muted-foreground lowercase">
                                <?= esc($item['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/pages/push_notif.js"></script>
<?= $this->endSection() ?>
