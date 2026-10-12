<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- PageHeader Responsive -->
<div class="mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground sm:text-2xl leading-tight">User &amp; Editorial Staff Management</h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">Manage newsroom journalists, managing editors, authors, contributors, and role permissions.</p>
        </div>
        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground shadow hover:bg-primary/90 transition-colors self-start sm:self-auto" onclick="document.getElementById('newUserModal').style.display='flex'">
            <i data-lucide="user-plus" class="size-4"></i>
            <span>Add New Staff</span>
        </button>
    </div>
</div>

<!-- Simple Filters Bar (Search, Status, Role) -->
<div class="surface-card p-4 mb-6 rounded-xl border border-border shadow-2xs">
    <form method="get" action="<?= base_url('user') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground"></i>
            <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari nama personil, username, atau email..." class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Status -->
            <select name="status" class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary">
                <option value="all">Semua Status</option>
                <option value="Active" <?= ($filters['status'] ?? '') === 'Active' ? 'selected' : '' ?>>Active (Aktif)</option>
                <option value="Inactive" <?= ($filters['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive (Nonaktif)</option>
            </select>

            <!-- Filter Role -->
            <select name="role" class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary capitalize">
                <option value="all">Semua Role</option>
                <option value="administrator" <?= strtolower($filters['role'] ?? '') === 'administrator' ? 'selected' : '' ?>>Administrator</option>
                <option value="redpel" <?= strtolower($filters['role'] ?? '') === 'redpel' ? 'selected' : '' ?>>Redpel</option>
                <option value="editor" <?= strtolower($filters['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                <option value="riset" <?= strtolower($filters['role'] ?? '') === 'riset' ? 'selected' : '' ?>>Riset</option>
                <option value="sekred" <?= strtolower($filters['role'] ?? '') === 'sekred' ? 'selected' : '' ?>>Sekred</option>
                <option value="support" <?= strtolower($filters['role'] ?? '') === 'support' ? 'selected' : '' ?>>Support</option>
            </select>

            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-[#2B6CB0] px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-[#2B6CB0]/90 transition-colors">
                <i data-lucide="filter" class="size-3.5"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($filters['q']) || ($filters['status'] ?? 'all') !== 'all' || ($filters['role'] ?? 'all') !== 'all'): ?>
                <a href="<?= base_url('user') ?>" class="rounded-md border border-input bg-background px-3 py-2 text-xs text-muted-foreground hover:text-foreground">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="surface-card overflow-hidden">
    <div class="relative w-full overflow-auto">
        <table class="w-full caption-bottom text-sm">
            <thead class="[&_tr]:border-b border-border bg-muted/40">
                <tr class="text-left font-bold text-foreground text-xs">
                    <th class="h-10 px-4">User / Journalist</th>
                    <th class="h-10 px-4">Role</th>
                    <th class="h-10 px-4">Status</th>
                    <th class="h-10 px-4">Last Active</th>
                    <th class="h-10 px-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="p-8 text-center text-xs text-muted-foreground">
                        Tidak ada personil yang sesuai kriteria pencarian filter.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                <?php
                    $uParts = explode(' ', $user['name']);
                    $uInitials = strtoupper(substr($uParts[0], 0, 1) . (isset($uParts[1]) ? substr($uParts[1], 0, 1) : ''));
                ?>
                <tr class="hover:bg-muted/40 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary/10 text-primary font-bold text-xs border border-primary/20">
                                <?= esc($uInitials) ?>
                            </span>
                            <div>
                                <span class="font-bold text-foreground text-xs block"><?= esc($user['name']) ?></span>
                                <span class="text-[11px] text-muted-foreground"><?= esc($user['email']) ?> &bull; @<?= esc($user['username']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <?php
                            $role = $user['role'];
                            $roleLower = strtolower($role);
                            $roleClass = match($roleLower) {
                                'administrator', 'master', 'admin' => 'bg-primary/10 text-primary border-primary/20 font-bold',
                                'redpel' => 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/25 font-semibold',
                                'editor' => 'bg-accent/12 text-accent border-accent/25',
                                'riset' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/25',
                                'sekred' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/25',
                                'support' => 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border-cyan-500/25',
                                default => 'bg-muted text-muted-foreground border-border'
                            };

                            $sessionUserId = (int)(session()->get('user_id') ?: 9);
                            $sessionUserRole = strtolower(session()->get('user_role') ?? 'administrator');
                            $canManagePerms = in_array($sessionUserRole, ['master', 'administrator', 'admin']);
                            $canDelete = true;
                            if ((int)$user['id'] === $sessionUserId) {
                                $canDelete = false; // Cannot delete self
                            } elseif (!in_array($sessionUserRole, ['master', 'administrator']) && in_array(strtolower($user['role']), ['master', 'administrator', 'admin'])) {
                                $canDelete = false; // Only Administrator can delete Admin
                            }
                        ?>
                        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium capitalize <?= $roleClass ?>"><?= esc($role) ?></span>
                    </td>
                    <td class="p-4">
                        <span class="inline-flex items-center gap-1.5 rounded-md border border-success/25 bg-success/12 px-2 py-0.5 text-xs font-medium text-success capitalize">
                            <span class="size-1.5 rounded-full bg-current"></span>
                            <?= esc($user['status']) ?>
                        </span>
                    </td>
                    <td class="p-4 text-xs text-muted-foreground"><?= esc($user['last_active']) ?></td>
                    <td class="p-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <?php if ($canManagePerms): ?>
                            <button type="button" onclick="openPermissionModal(<?= $user['id'] ?>, '<?= esc(addslashes($user['name'])) ?>', '<?= esc($user['role']) ?>')" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-primary hover:border-primary transition-colors" title="Manage Permissions">
                                <i data-lucide="shield" class="size-3.5"></i>
                            </button>
                            <?php endif; ?>

                            <?php if ($user['id'] !== $sessionUserId && $canManagePerms): ?>
                            <form method="post" action="<?= base_url('user/toggle-status/' . $user['id']) ?>" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin <?= $user['is_active'] ? 'menonaktifkan (block)' : 'mengaktifkan kembali (unblock)' ?> akun <?= esc(addslashes($user['name'])) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background <?= $user['is_active'] ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/20' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/20' ?> transition-colors" title="<?= $user['is_active'] ? 'Block User' : 'Unblock User' ?>">
                                    <i data-lucide="<?= $user['is_active'] ? 'user-x' : 'user-check' ?>" class="size-3.5"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($canDelete): ?>
                            <button type="button" onclick="openDeleteUserModal(<?= $user['id'] ?>, '<?= esc(addslashes($user['name'])) ?>')" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Delete User">
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </button>
                            <?php else: ?>
                            <span class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-muted/40 text-muted-foreground/40 cursor-not-allowed" title="Protected Account">
                                <i data-lucide="lock" class="size-3.5"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (isset($pagination) && $pagination['totalItems'] > 0): ?>
        <?= view('layouts/partials/pagination', ['pagination' => $pagination, 'baseUrl' => base_url('user')]) ?>
    <?php endif; ?>
</div>

<!-- Modal Add User -->
<!-- Modal Add User -->
<div id="newUserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-xl max-h-[90vh] flex flex-col p-4 sm:p-5 shadow-2xl rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-border pb-2.5 shrink-0">
            <div>
                <h3 class="text-sm font-bold text-foreground">Register Editorial Staff</h3>
                <p class="text-[11px] text-muted-foreground">Tambah akun jurnalis atau staf editorial baru</p>
            </div>
            <button type="button" onclick="closeNewUserModal()" class="rounded-md p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable if height exceeds screen) -->
        <form id="newUserForm" method="post" action="<?= base_url('user/save') ?>" class="flex-1 overflow-y-auto custom-sidebar-scroll py-3 space-y-3.5 pr-1">
            <?= csrf_field() ?>
            <!-- Row 1: Full Name & Username -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Full Name <span class="text-destructive">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Raditya Pramana" class="flex h-8.5 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">Username</label>
                        <span class="text-[10px] text-muted-foreground/70 font-normal italic">Otomatis jika kosong</span>
                    </div>
                    <input type="text" name="username" placeholder="e.g. raditya_p" class="flex h-8.5 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
            </div>

            <!-- Row 2: Email Address & Inisial Alias -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Email Address <span class="text-destructive">*</span></label>
                    <input type="email" name="email" required placeholder="raditya@investor.id" class="flex h-8.5 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">Inisial Penulis</label>
                        <span class="text-[10px] text-muted-foreground/70 font-normal italic">e.g. RDP, AGG</span>
                    </div>
                    <input type="text" name="alias" maxlength="20" placeholder="e.g. RDP" class="flex h-8.5 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono uppercase">
                </div>
            </div>

            <!-- Row 3: Assigned Role -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Assigned Role <span class="text-destructive">*</span></label>
                <select name="role" class="flex h-8.5 w-full rounded-md border border-input bg-background px-3 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring capitalize">
                    <option value="administrator">Administrator</option>
                    <option value="redpel">Redpel (Redaktur Pelaksana)</option>
                    <option value="editor" selected>Editor</option>
                    <option value="riset">Riset</option>
                    <option value="sekred">Sekred</option>
                    <option value="support">Support</option>
                </select>
            </div>

            <!-- Row 4: Password & Confirm Password -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Kata Sandi (Password) <span class="text-destructive">*</span></label>
                    <div class="relative flex items-center">
                        <input type="password" name="password" id="newUserPassword" required minlength="8" placeholder="Password baru..." class="flex h-8.5 w-full rounded-md border border-input bg-background pl-3 pr-10 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono" oninput="validateUserPasswordRules(this.value)">
                        <button type="button" onclick="togglePasswordVisibility('newUserPassword', this)" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 text-muted-foreground hover:text-foreground p-1 rounded transition-colors" title="Lihat/Sembunyikan Password">
                            <i data-lucide="eye" class="size-3.5"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Konfirmasi Password <span class="text-destructive">*</span></label>
                    <div class="relative flex items-center">
                        <input type="password" name="password_confirmation" id="newUserPasswordConfirm" required minlength="8" placeholder="Ketik ulang password..." class="flex h-8.5 w-full rounded-md border border-input bg-background pl-3 pr-10 py-1 text-xs sm:text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono" oninput="validateUserPasswordMatch()">
                        <button type="button" onclick="togglePasswordVisibility('newUserPasswordConfirm', this)" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 text-muted-foreground hover:text-foreground p-1 rounded transition-colors" title="Lihat/Sembunyikan Password">
                            <i data-lucide="eye" class="size-3.5"></i>
                        </button>
                    </div>
                    <div id="passwordMatchMessage" class="text-[10px] mt-0.5 hidden"></div>
                </div>
            </div>

            <!-- Password Rules Checklist Box (Compact) -->
            <div class="p-2.5 rounded-lg border border-border/70 bg-muted/25 text-[11px] space-y-1">
                <span class="font-bold text-foreground block text-[10px] uppercase tracking-wider">Kriteria Keamanan Kata Sandi:</span>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-2 gap-y-1 text-muted-foreground text-[10.5px]">
                    <div id="rule-len" class="flex items-center gap-1.5 transition-colors">
                        <span class="size-1.5 rounded-full bg-muted-foreground/50 shrink-0"></span>
                        <span>Min. 8 karakter</span>
                    </div>
                    <div id="rule-upper" class="flex items-center gap-1.5 transition-colors">
                        <span class="size-1.5 rounded-full bg-muted-foreground/50 shrink-0"></span>
                        <span>Huruf besar (A-Z)</span>
                    </div>
                    <div id="rule-lower" class="flex items-center gap-1.5 transition-colors">
                        <span class="size-1.5 rounded-full bg-muted-foreground/50 shrink-0"></span>
                        <span>Huruf kecil (a-z)</span>
                    </div>
                    <div id="rule-digit" class="flex items-center gap-1.5 transition-colors">
                        <span class="size-1.5 rounded-full bg-muted-foreground/50 shrink-0"></span>
                        <span>Angka (0-9)</span>
                    </div>
                    <div id="rule-special" class="flex items-center gap-1.5 transition-colors sm:col-span-2">
                        <span class="size-1.5 rounded-full bg-muted-foreground/50 shrink-0"></span>
                        <span>Simbol khusus (@, #, $, !, dll)</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer (Pinned Actions) -->
            <div class="flex justify-end gap-2 pt-2.5 border-t border-border shrink-0">
                <button type="button" onclick="closeNewUserModal()" class="rounded-md border border-input bg-background px-3.5 py-1.5 text-xs font-medium text-foreground hover:bg-muted transition-colors">Batal</button>
                <button type="submit" id="btnSubmitNewUser" class="rounded-md bg-primary px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-primary-foreground hover:bg-primary/90 transition-colors shadow-xs">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Delete User Confirmation -->
<div id="deleteUserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-md p-5 shadow-2xl space-y-4 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3 border-b border-border pb-3 text-destructive">
            <i data-lucide="alert-triangle" class="size-5"></i>
            <h3 class="text-sm font-bold text-foreground">Hapus Akun Pengguna</h3>
        </div>
        <p class="text-xs text-muted-foreground leading-relaxed">
            Apakah Anda yakin ingin menghapus akun pengguna <strong id="deleteUserName" class="text-foreground"></strong>? Tindakan ini tidak dapat dibatalkan.
        </p>
        <form id="deleteUserForm" method="post" action="">
            <?= csrf_field() ?>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="closeDeleteUserModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" id="confirmDeleteUserBtn" class="rounded-md bg-destructive px-4 py-2 text-xs font-bold text-destructive-foreground hover:bg-destructive/90">Hapus Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Page Permissions (Informative Read-Only Role Access Overview) -->
<div id="permissionModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg p-5 shadow-2xl space-y-4 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2.5">
                <div class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="shield-check" class="size-4.5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-foreground">Daftar Hak Akses Halaman</h3>
                    <p class="text-[11px] text-muted-foreground" id="permissionUserDesc">Memuat ringkasan wewenang...</p>
                </div>
            </div>
            <button type="button" onclick="closePermissionModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <div class="space-y-3 max-h-[360px] overflow-y-auto custom-sidebar-scroll pr-1">
            <!-- Content Module -->
            <div class="rounded-lg border border-border p-3 bg-muted/20">
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block mb-2.5">Modul Konten</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-dashboard">
                        <span class="font-medium text-foreground">Dashboard</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-post">
                        <span class="font-medium text-foreground">Post (Artikel)</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-headline">
                        <span class="font-medium text-foreground">Headline</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-media">
                        <span class="font-medium text-foreground">Media Library</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-files">
                        <span class="font-medium text-foreground">Files Manager</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                </div>
            </div>

            <!-- Insights Module -->
            <div class="rounded-lg border border-border p-3 bg-muted/20">
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block mb-2.5">Modul Insights</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-embed-chart">
                        <span class="font-medium text-foreground">Embed Chart</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-statistic">
                        <span class="font-medium text-foreground">Trafik &amp; Audiens</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-statistic-report">
                        <span class="font-medium text-foreground">Performa Tim Editorial</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                </div>
            </div>

            <!-- Management Module -->
            <div class="rounded-lg border border-border p-3 bg-muted/20">
                <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground block mb-2.5">Modul Manajemen &amp; Apps</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-kanal-master">
                        <span class="font-medium text-foreground">Master Kanal</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-kanal-sub">
                        <span class="font-medium text-foreground">Sub Kanal</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-kanal-stats">
                        <span class="font-medium text-foreground">Performa Kanal</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-apps-version">
                        <span class="font-medium text-foreground">Apps Version</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-apps-push-notif">
                        <span class="font-medium text-foreground">Apps Push Notif</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-apps-feature-content">
                        <span class="font-medium text-foreground">Feature Content</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-member">
                        <span class="font-medium text-foreground">Member Portal</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-border/60 bg-background/80 transition-all" id="perm-user">
                        <span class="font-medium text-foreground">User Management</span>
                        <span class="perm-icon-wrapper flex items-center justify-center"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-border text-xs">
            <div class="flex items-center gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>
                    <span>Diizinkan</span>
                </span>
                <span class="inline-flex items-center gap-1.5 font-medium text-rose-600 dark:text-rose-400">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m15 9-6 6"></path>
                        <path d="m9 9 6 6"></path>
                    </svg>
                    <span>Dibatasi</span>
                </span>
            </div>
            <button type="button" onclick="closePermissionModal()" class="rounded-md bg-[#2B6CB0] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#2B6CB0]/90 transition-colors shadow-xs">
                Tutup
            </button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Embedded preloaded permissions map for zero-lag instant rendering
const globalPermissionsMap = <?= json_encode($permissionsMap ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

const CHECK_ICON_SVG = `<svg class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>`;

const CROSS_ICON_SVG = `<svg class="size-4 text-rose-600 dark:text-rose-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path></svg>`;

function closeNewUserModal() {
    const modal = document.getElementById('newUserModal');
    if (modal) modal.style.display = 'none';
}

function openDeleteUserModal(userId, userName) {
    const modal = document.getElementById('deleteUserModal');
    const nameSpan = document.getElementById('deleteUserName');
    const deleteForm = document.getElementById('deleteUserForm');
    if (modal && nameSpan && deleteForm) {
        nameSpan.textContent = userName;
        deleteForm.action = '<?= base_url('user/delete/') ?>/' + userId;
        modal.style.display = 'flex';
    }
}

function closeDeleteUserModal() {
    const modal = document.getElementById('deleteUserModal');
    if (modal) modal.style.display = 'none';
}

function renderPermissionItems(permissions) {
    const allKeys = [
        'dashboard', 'post', 'headline', 'media', 'files',
        'embed-chart', 'statistic', 'statistic-report',
        'kanal-master', 'kanal-sub', 'kanal-stats',
        'apps-version', 'apps-push-notif', 'apps-feature-content',
        'member', 'user'
    ];

    allKeys.forEach(k => {
        const el = document.getElementById('perm-' + k);
        if (!el) return;
        const iconWrapper = el.querySelector('.perm-icon-wrapper');
        const lookupKey = k.replace('-', '/');
        const hasAccess = Boolean(permissions.all === true || permissions[k] === true || permissions[lookupKey] === true);

        if (hasAccess) {
            el.className = 'flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-emerald-500/25 bg-emerald-500/10 text-emerald-950 dark:text-emerald-200 font-medium transition-all shadow-2xs';
            if (iconWrapper) iconWrapper.innerHTML = CHECK_ICON_SVG;
        } else {
            el.className = 'flex items-center justify-between py-1.5 px-2.5 rounded-lg border border-rose-500/20 bg-rose-500/10 text-rose-950 dark:text-rose-200 transition-all opacity-85';
            if (iconWrapper) iconWrapper.innerHTML = CROSS_ICON_SVG;
        }
    });
}

function openPermissionModal(userId, userName, userRole) {
    const modal = document.getElementById('permissionModal');
    const desc = document.getElementById('permissionUserDesc');
    if (!modal) return;

    desc.textContent = `Ringkasan wewenang halaman untuk ${userName} (Peran: ${userRole.toUpperCase()})`;

    // 1. INSTANT ZERO-LAG RENDERING from local data
    const roleKey = (userRole || '').toLowerCase();
    const userKey = String(userId);
    const defaultRolePerms = (globalPermissionsMap.roles && globalPermissionsMap.roles[roleKey]) ? globalPermissionsMap.roles[roleKey] : {};
    const userCustomPerms = (globalPermissionsMap.users && globalPermissionsMap.users[userKey]) ? globalPermissionsMap.users[userKey] : defaultRolePerms;

    renderPermissionItems(userCustomPerms);
    modal.style.display = 'flex';

    // 2. Background sync (optional fallback to update in real-time if modified concurrently)
    fetch('<?= base_url('user/permissions/') ?>/' + userId)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.permissions) {
                renderPermissionItems(res.permissions);
            }
        })
        .catch(err => {
            console.error('Permissions fetch error:', err);
        });
}

function closePermissionModal() {
    const modal = document.getElementById('permissionModal');
    if (modal) modal.style.display = 'none';
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i data-lucide="eye-off" class="size-4"></i>' : '<i data-lucide="eye" class="size-4"></i>';
    if (window.lucide) window.lucide.createIcons();
}

function updateRuleIndicator(elementId, isValid) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const dot = el.querySelector('span:first-child');
    if (isValid) {
        el.className = 'flex items-center gap-1.5 text-emerald-600 font-semibold transition-colors';
        if (dot) dot.className = 'size-1.5 rounded-full bg-emerald-500';
    } else {
        el.className = 'flex items-center gap-1.5 text-muted-foreground transition-colors';
        if (dot) dot.className = 'size-1.5 rounded-full bg-muted-foreground/50';
    }
}

function validateUserPasswordRules(pwd) {
    const hasLen     = pwd.length >= 8;
    const hasUpper   = /[A-Z]/.test(pwd);
    const hasLower   = /[a-z]/.test(pwd);
    const hasDigit   = /[0-9]/.test(pwd);
    const hasSpecial = /[^a-zA-Z0-9]/.test(pwd);

    updateRuleIndicator('rule-len', hasLen);
    updateRuleIndicator('rule-upper', hasUpper);
    updateRuleIndicator('rule-lower', hasLower);
    updateRuleIndicator('rule-digit', hasDigit);
    updateRuleIndicator('rule-special', hasSpecial);

    validateUserPasswordMatch();
}

function validateUserPasswordMatch() {
    const pwd = document.getElementById('newUserPassword') ? document.getElementById('newUserPassword').value : '';
    const confirmInput = document.getElementById('newUserPasswordConfirm');
    const msgEl = document.getElementById('passwordMatchMessage');
    if (!confirmInput || !msgEl) return;

    const confirmVal = confirmInput.value;
    if (!confirmVal) {
        msgEl.className = 'text-[11px] mt-1 hidden';
        msgEl.textContent = '';
        return;
    }

    msgEl.classList.remove('hidden');
    if (pwd === confirmVal) {
        msgEl.className = 'text-[11px] mt-1 text-emerald-600 font-medium flex items-center gap-1';
        msgEl.innerHTML = '<i data-lucide="check" class="size-3"></i> <span>Password cocok</span>';
    } else {
        msgEl.className = 'text-[11px] mt-1 text-rose-600 font-medium flex items-center gap-1';
        msgEl.innerHTML = '<i data-lucide="alert-circle" class="size-3"></i> <span>Konfirmasi password belum cocok</span>';
    }
    if (window.lucide) window.lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.ModalUtils) {
        window.ModalUtils.register('newUserModal', { onClose: closeNewUserModal });
        window.ModalUtils.register('deleteUserModal', { onClose: closeDeleteUserModal });
        window.ModalUtils.register('permissionModal', { onClose: closePermissionModal });
    }

    const newUserForm = document.getElementById('newUserForm');
    if (newUserForm) {
        newUserForm.addEventListener('submit', (e) => {
            const pwd = document.getElementById('newUserPassword') ? document.getElementById('newUserPassword').value : '';
            const confirmVal = document.getElementById('newUserPasswordConfirm') ? document.getElementById('newUserPasswordConfirm').value : '';

            const hasLen     = pwd.length >= 8;
            const hasUpper   = /[A-Z]/.test(pwd);
            const hasLower   = /[a-z]/.test(pwd);
            const hasDigit   = /[0-9]/.test(pwd);
            const hasSpecial = /[^a-zA-Z0-9]/.test(pwd);

            if (!hasLen || !hasUpper || !hasLower || !hasDigit || !hasSpecial) {
                e.preventDefault();
                alert('Password belum memenuhi kriteria keamanan: wajib minimal 8 karakter dengan huruf besar (A-Z), huruf kecil (a-z), angka (0-9), dan simbol khusus.');
                return false;
            }

            if (pwd !== confirmVal) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok dengan password yang dimasukkan.');
                return false;
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
