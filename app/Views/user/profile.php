<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">My Profile</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Kelola informasi profil akun editorial dan alias penulisan Anda.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card Summary -->
    <div class="surface-card p-6 flex flex-col items-center text-center">
        <?php
            $nameParts = explode(' ', $user['name'] ?? 'Staff Editorial');
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
        ?>
        <!-- Avatar Circle with generous breathing room (D21 fix) -->
        <div class="size-32 sm:size-36 rounded-full bg-primary/10 border-2 border-primary/25 flex items-center justify-center text-primary font-bold text-2xl sm:text-3xl tracking-wider shadow-inner mb-4 select-none p-6">
            <?= esc($initials) ?>
        </div>
        <h2 class="text-base font-bold text-foreground"><?= esc($user['name'] ?? '-') ?></h2>
        <span class="text-xs text-muted-foreground mt-0.5 truncate max-w-[200px] block" title="<?= esc($user['email'] ?? '-') ?>"><?= esc($user['email'] ?? '-') ?></span>
        
        <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20 capitalize">
            <i data-lucide="shield" class="size-3.5"></i>
            <span><?= esc($user['roleName'] ?? 'Administrator') ?></span>
        </div>

        <div class="w-full mt-6 pt-4 border-t border-border/60 text-left space-y-2 text-xs">
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
        </div>
    </div>

    <!-- Profile Details & Alias Edit Form -->
    <div class="lg:col-span-2 surface-card p-6">
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

        <!-- Change Password Section (D14 & D19) -->
        <div class="mt-8 pt-6 border-t border-border/60">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-foreground flex items-center gap-2">
                    <i data-lucide="lock" class="size-4 text-primary"></i>
                    <span>Ubah Kata Sandi Mandiri</span>
                </h3>
                <p class="text-xs text-muted-foreground mt-0.5">Tingkatkan keamanan akun Anda dengan mengganti kata sandi secara berkala.</p>
            </div>

            <form action="<?= base_url('user/profile/change-password') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Kata Sandi Saat Ini</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" required placeholder="••••••••••••" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                            <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat kata sandi">
                                <i data-lucide="eye" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                            <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat kata sandi">
                                <i data-lucide="eye" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-muted-foreground mb-1.5">Konfirmasi Sandi Baru</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Ulangi sandi baru" class="flex h-9 w-full rounded-md border border-input bg-background pl-3 pr-9 py-1 text-xs text-foreground shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary font-mono">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1" title="Lihat kata sandi">
                                <i data-lucide="eye" class="size-3.5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-secondary text-secondary-foreground hover:bg-secondary/80 border border-border px-4 py-2 text-xs font-bold uppercase tracking-wider shadow-xs transition-colors">
                        <i data-lucide="key" class="size-3.5"></i>
                        <span>Perbarui Kata Sandi</span>
                    </button>
                </div>
            </form>

        </div>
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
</script>
<?= $this->endSection() ?>
