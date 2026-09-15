<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">Pengaturan Akun &amp; Profil</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Kelola informasi profil akun editorial, kredensial keamanan, dan alias penulisan berita Anda.</p>
        </div>
    </div>
</div>

<?php if (!empty($isPasswordExpired)): ?>
    <!-- 90-Day Password Expired Warning Banner (D19 & AuthGuard Parity) -->
    <div class="mb-6 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-destructive flex items-start gap-3.5 shadow-sm">
        <i data-lucide="alert-octagon" class="size-5 shrink-0 mt-0.5"></i>
        <div class="flex-1 text-xs sm:text-sm">
            <h4 class="font-bold text-sm">Masa Berlaku Kata Sandi Telah Berakhir!</h4>
            <p class="mt-1 text-destructive/90 leading-relaxed">
                Sesuai kebijakan kepatuhan keamanan redaksi Investor.ID, masa aktif kata sandi (90 hari) telah berakhir pada <strong><?= esc(date('d M Y', strtotime($expiredDate))) ?></strong>. Silakan perbarui kata sandi Anda sekarang pada tab <strong>Keamanan &amp; Kata Sandi</strong> di bawah ini.
            </p>
        </div>
    </div>
<?php elseif (isset($daysRemaining) && $daysRemaining <= 14): ?>
    <!-- Password Expiring Soon Notice (< 14 days) -->
    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 dark:border-amber-600/30 dark:bg-amber-950/30 p-4 text-amber-800 dark:text-amber-300 flex items-start gap-3.5 shadow-sm">
        <i data-lucide="clock" class="size-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400"></i>
        <div class="flex-1 text-xs sm:text-sm">
            <h4 class="font-bold text-sm">Pemberitahuan Rotasi Kata Sandi</h4>
            <p class="mt-1 leading-relaxed">
                Masa berlaku kata sandi akun Anda akan berakhir dalam <strong><?= $daysRemaining ?> hari lagi</strong> (<?= esc(date('d M Y', strtotime($expiredDate))) ?>). Disarankan untuk memperbarui kata sandi Anda lebih awal demi kelancaran tugas editorial.
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card Summary (Left Column) -->
    <div class="surface-card p-6 flex flex-col items-center text-center">
        <?php
            $nameParts = explode(' ', $user['name'] ?? 'Staff Editorial');
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
        ?>
        <!-- Avatar Circle -->
        <div class="size-28 sm:size-32 rounded-full bg-primary/10 border-2 border-primary/25 flex items-center justify-center text-primary font-bold text-2xl sm:text-3xl tracking-wider shadow-inner mb-4 select-none p-6">
            <?= esc($initials) ?>
        </div>
        <h2 class="text-base font-bold text-foreground"><?= esc($user['name'] ?? '-') ?></h2>
        <span class="text-xs text-muted-foreground mt-0.5 truncate max-w-[220px] block" title="<?= esc($user['email'] ?? '-') ?>"><?= esc($user['email'] ?? '-') ?></span>
        
        <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 capitalize">
            <i data-lucide="shield" class="size-3.5"></i>
            <span><?= esc($user['roleName'] ?? 'Administrator') ?></span>
        </div>

        <div class="w-full mt-6 pt-4 border-t border-border/60 text-left space-y-2.5 text-xs">
            <div class="flex justify-between py-1">
                <span class="text-muted-foreground">Username</span>
                <span class="font-medium text-foreground">@<?= esc($user['username'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-muted-foreground">Current Alias</span>
                <span class="font-mono font-bold text-primary"><?= esc($user['alias'] ?: '(belum diatur)') ?></span>
            </div>
            <div class="flex justify-between py-1">
                <span class="text-muted-foreground">Terakhir Login</span>
                <span class="text-muted-foreground"><?= esc($user['lastLogin'] ?? 'Baru saja') ?></span>
            </div>
            <div class="flex justify-between py-1 border-t border-dashed border-border/50 pt-2">
                <span class="text-muted-foreground">Rotasi Sandi</span>
                <span class="font-medium <?= (!empty($isPasswordExpired) ? 'text-destructive font-bold' : ($daysRemaining <= 14 ? 'text-amber-600 font-bold' : 'text-foreground')) ?>">
                    <?= !empty($isPasswordExpired) ? 'Kedaluwarsa' : "Sisa {$daysRemaining} hari" ?>
                </span>
            </div>
        </div>

        <!-- Quick Sub-Navigation Pills -->
        <div class="w-full mt-6 pt-4 border-t border-border/60 flex flex-col gap-1.5">
            <a href="<?= base_url('user/profile?tab=profile') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors <?= ($activeTab === 'profile') ? 'bg-primary text-primary-foreground font-semibold shadow-xs' : 'text-muted-foreground hover:bg-muted hover:text-foreground' ?>">
                <i data-lucide="user" class="size-4"></i>
                <span>Informasi Profil &amp; Alias</span>
            </a>
            <a href="<?= base_url('user/profile?tab=security') ?>" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-colors <?= ($activeTab === 'security') ? 'bg-primary text-primary-foreground font-semibold shadow-xs' : 'text-muted-foreground hover:bg-muted hover:text-foreground' ?>">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="lock" class="size-4"></i>
                    <span>Keamanan &amp; Kata Sandi</span>
                </div>
                <?php if (!empty($isPasswordExpired)): ?>
                    <span class="size-2 rounded-full bg-red-400 animate-pulse"></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Main Content Area with Tabs (Right 2 Columns) -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Tab Header Bar -->
        <div class="flex border-b border-border/80 gap-6">
            <a href="<?= base_url('user/profile?tab=profile') ?>" class="pb-3 text-xs sm:text-sm font-semibold transition-colors relative flex items-center gap-2 <?= ($activeTab === 'profile') ? 'text-primary border-b-2 border-primary font-bold' : 'text-muted-foreground hover:text-foreground' ?>">
                <i data-lucide="user" class="size-4"></i>
                <span>Profil Akun</span>
            </a>
            <a href="<?= base_url('user/profile?tab=security') ?>" class="pb-3 text-xs sm:text-sm font-semibold transition-colors relative flex items-center gap-2 <?= ($activeTab === 'security') ? 'text-primary border-b-2 border-primary font-bold' : 'text-muted-foreground hover:text-foreground' ?>">
                <i data-lucide="shield-check" class="size-4"></i>
                <span>Keamanan &amp; Kata Sandi</span>
                <?php if (!empty($isPasswordExpired)): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-bold uppercase">Kadaluarsa</span>
                <?php endif; ?>
            </a>
        </div>

        <?php if ($activeTab === 'profile'): ?>
            <!-- TAB 1: Profil & Alias -->
            <div class="surface-card p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-border/60">
                    <div>
                        <h3 class="text-sm font-bold text-foreground">Detail Akun &amp; Kredensial</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Sesuai kebijakan keamanan redaksi, hanya inisial Alias penulisan yang dapat disesuaikan.</p>
                    </div>
                </div>

                <form action="<?= base_url('user/profile/update') ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Full Name (Disabled) -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Nama Lengkap</label>
                            <input type="text" value="<?= esc($user['name'] ?? '') ?>" disabled class="flex h-9 w-full rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-none">
                            <p class="mt-1 text-[10px] text-muted-foreground/80">Nama resmi jurnalis sesuai SK Redaksi.</p>
                        </div>

                        <!-- Username (Disabled) -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Username Login</label>
                            <input type="text" value="<?= esc($user['username'] ?? '') ?>" disabled class="flex h-9 w-full rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-none">
                            <p class="mt-1 text-[10px] text-muted-foreground/80">Pengidentifikasi unik untuk autentikasi sistem.</p>
                        </div>

                        <!-- Email (Disabled) -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Email Internal</label>
                            <input type="email" value="<?= esc($user['email'] ?? '') ?>" disabled class="flex h-9 w-full rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-none">
                            <p class="mt-1 text-[10px] text-muted-foreground/80">Alamat korespondensi editorial resmi.</p>
                        </div>

                        <!-- Slug (Disabled) -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Slug URL Penulis</label>
                            <input type="text" value="<?= esc($user['slug'] ?? '') ?>" disabled class="flex h-9 w-full rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-none">
                            <p class="mt-1 text-[10px] text-muted-foreground/80">Struktur permalink arsip artikel di frontend.</p>
                        </div>

                        <!-- Role (Disabled) -->
                        <div>
                            <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Peran Redaksi (Role)</label>
                            <input type="text" value="<?= esc($user['roleName'] ?? '') ?>" disabled class="flex h-9 w-full rounded-md border border-input bg-muted/60 px-3 py-1 text-xs text-muted-foreground cursor-not-allowed shadow-none capitalize">
                            <p class="mt-1 text-[10px] text-muted-foreground/80">Tingkat hak akses CMS ditentukan oleh Master Admin.</p>
                        </div>

                        <!-- Alias (EDITABLE) -->
                        <div>
                            <label for="alias" class="block text-xs font-bold text-foreground mb-1.5 flex items-center justify-between">
                                <span>Inisial Alias Penulis</span>
                                <span class="text-[10px] text-primary font-normal">Dapat Diubah</span>
                            </label>
                            <input type="text" id="alias" name="alias" value="<?= esc($user['alias'] ?? '') ?>" placeholder="Contoh: RHR, AGG, dupp" maxlength="20" class="flex h-9 w-full rounded-md border border-primary/40 bg-background px-3 py-1 text-xs text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono font-semibold" required>
                            <p class="mt-1 text-[10px] text-muted-foreground">Inisial singkatan nama yang disematkan di akhir naskah berita.</p>
                        </div>
                    </div>

                    <div class="pt-4 mt-6 border-t border-border/60 flex items-center justify-end gap-3">
                        <a href="<?= base_url('dashboard') ?>" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                            <i data-lucide="save" class="size-4"></i>
                            <span>Simpan Perubahan Alias</span>
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- TAB 2: Keamanan & Reset Password -->
            <div class="space-y-6">
                <!-- Card 1: Ubah Kata Sandi Mandiri (Direct Form) -->
                <div class="surface-card p-6">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-border/60">
                        <div>
                            <h3 class="text-sm font-bold text-foreground flex items-center gap-2">
                                <i data-lucide="key" class="size-4 text-primary"></i>
                                <span>Ubah Kata Sandi Mandiri</span>
                            </h3>
                            <p class="text-xs text-muted-foreground mt-0.5">Masukkan kata sandi saat ini dan tentukan kombinasi kata sandi baru yang kuat.</p>
                        </div>
                    </div>

                    <form action="<?= base_url('user/profile/change-password') ?>" method="POST" class="space-y-4">
                        <?= csrf_field() ?>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Current Password -->
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Kata Sandi Saat Ini</label>
                                <div class="relative flex items-center">
                                    <input type="password" id="current_password" name="current_password" required placeholder="••••••••••••" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                                    <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat/Sembunyikan kata sandi">
                                        <i data-lucide="eye" class="size-3.5"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Kata Sandi Baru</label>
                                <div class="relative flex items-center">
                                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                                    <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat/Sembunyikan kata sandi">
                                        <i data-lucide="eye" class="size-3.5"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Konfirmasi Sandi Baru</label>
                                <div class="relative flex items-center">
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Ulangi sandi baru" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                                    <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat/Sembunyikan kata sandi">
                                        <i data-lucide="eye" class="size-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password Strength Policy Checklist -->
                        <div class="rounded-lg bg-muted/40 border border-border/50 p-3 text-[11px] text-muted-foreground space-y-1">
                            <p class="font-bold text-foreground">Ketentuan Kebijakan Kata Sandi Redaksi:</p>
                            <ul class="list-disc pl-4 space-y-0.5">
                                <li>Minimal terdiri dari <strong>8 karakter</strong></li>
                                <li>Mengandung kombinasi <strong>huruf besar (A-Z)</strong> dan <strong>huruf kecil (a-z)</strong></li>
                                <li>Mengandung minimal <strong>1 digit angka (0-9)</strong></li>
                                <li>Mengandung minimal <strong>1 karakter khusus/simbol</strong> (!, @, #, $, %, dll)</li>
                                <li>Tidak boleh sama dengan kata sandi saat ini</li>
                            </ul>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <p class="text-[11px] text-muted-foreground">
                                <i data-lucide="info" class="inline size-3 mr-1 text-primary"></i>
                                Setelah kata sandi diperbarui, sesi Anda akan ditutup dan diminta masuk kembali.
                            </p>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 px-4 py-2 text-xs font-bold uppercase tracking-wider shadow-sm transition-colors">
                                <i data-lucide="key" class="size-3.5"></i>
                                <span>Simpan Sandi Baru</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card 2: Lupa Kata Sandi Lama / Kirim Tautan Konfirmasi Email (Alur 1-Click Fallback) -->
                <div class="surface-card p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-foreground flex items-center gap-2">
                                <i data-lucide="mail-check" class="size-4 text-primary"></i>
                                <span>Lupa Kata Sandi Saat Ini?</span>
                            </h4>
                            <p class="text-xs text-muted-foreground leading-relaxed max-w-lg">
                                Anda dapat meminta tautan pemulihan satu kali klik yang akan dikirimkan langsung ke email resmi internal Anda (<strong><?= esc($user['email']) ?></strong>).
                            </p>
                        </div>
                        <form action="<?= base_url('user/profile/request-reset-email') ?>" method="POST" onsubmit="return confirm('Kirimkan tautan pemulihan kata sandi ke email Anda?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background hover:bg-muted text-foreground px-4 py-2 text-xs font-bold uppercase tracking-wider shadow-xs transition-colors whitespace-nowrap">
                                <i data-lucide="send" class="size-3.5"></i>
                                <span>Kirim Tautan ke Email</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i data-lucide="eye-off" class="size-3.5 text-primary"></i>' : '<i data-lucide="eye" class="size-3.5"></i>';
    if (window.lucide) lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>
<?= $this->endSection() ?>
