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
                            $roleClass = match($role) {
                                'Master' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30 font-semibold',
                                'Admin' => 'bg-primary/10 text-primary border-primary/20',
                                'Editor' => 'bg-accent/12 text-accent border-accent/25',
                                'Author' => 'bg-success/12 text-success border-success/25',
                                default => 'bg-muted text-muted-foreground border-border'
                            };

                            $sessionUserId = (int)(session()->get('user_id') ?: 9);
                            $sessionUserRole = session()->get('user_role') ?? 'Admin';
                            $canDelete = true;
                            if ((int)$user['id'] === $sessionUserId) {
                                $canDelete = false; // Cannot delete self
                            } elseif (strcasecmp($sessionUserRole, 'Master') !== 0 && in_array(strtolower($user['role']), ['master', 'admin'])) {
                                $canDelete = false; // Only Master can delete Admin or Master
                            }
                        ?>
                        <span class="inline-flex rounded-md border px-2 py-0.5 text-xs font-medium <?= $roleClass ?>"><?= esc($role) ?></span>
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
                            <?php if (in_array(strtolower($sessionUserRole), ['master', 'admin'])): ?>
                            <button type="button" onclick="openPermissionModal(<?= $user['id'] ?>, '<?= esc(addslashes($user['name'])) ?>', '<?= esc($user['role']) ?>')" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-primary hover:border-primary transition-colors" title="Manage Permissions">
                                <i data-lucide="shield" class="size-3.5"></i>
                            </button>
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
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add User -->
<div id="newUserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg p-5 shadow-2xl space-y-3.5 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Register Editorial Staff</h3>
            <button type="button" onclick="closeNewUserModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form id="newUserForm" method="post" action="<?= base_url('user/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Full Name</label>
                <input type="text" name="name" required placeholder="e.g. Raditya Pramana" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground">Username</label>
                    <span class="text-[10px] text-muted-foreground/70 font-normal italic">Opsional — Otomatis jika kosong</span>
                </div>
                <input type="text" name="username" placeholder="e.g. raditya_p" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="raditya@investor.id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Assigned Role</label>
                <select name="role" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <?php if (strcasecmp(session()->get('user_role') ?? '', 'Master') === 0): ?>
                    <option value="Master">Master / Super Administrator</option>
                    <?php endif; ?>
                    <option value="Admin">Administrator</option>
                    <option value="Editor">Editor</option>
                    <option value="Author">Author / Journalist</option>
                    <option value="Contributor">Contributor</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="closeNewUserModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Create User</button>
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
        <div class="flex justify-end gap-2 pt-2 border-t border-border">
            <button type="button" onclick="closeDeleteUserModal()" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
            <a id="confirmDeleteUserBtn" href="#" class="rounded-md bg-destructive px-4 py-2 text-xs font-bold text-destructive-foreground hover:bg-destructive/90">Hapus Akun</a>
        </div>
    </div>
</div>

<!-- Modal Page Permissions -->
<div id="permissionModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.7); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1.25rem;">
    <div class="surface-card w-full max-w-lg p-5 shadow-2xl space-y-4 rounded-xl border border-border animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="size-5 text-primary"></i>
                <div>
                    <h3 class="text-sm font-bold text-foreground">Hak Akses Halaman User</h3>
                    <p class="text-[11px] text-muted-foreground" id="permissionUserDesc">Atur halaman yang boleh diakses oleh personil ini.</p>
                </div>
            </div>
            <button type="button" onclick="closePermissionModal()" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>

        <form id="permissionForm" class="space-y-4">
            <input type="hidden" id="permUserId" name="user_id" value="">
            
            <div class="space-y-3 max-h-[340px] overflow-y-auto custom-sidebar-scroll pr-1">
                <!-- Content Module -->
                <div class="rounded-lg border border-border p-3 bg-muted/20">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground block mb-2">Modul Konten</span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="dashboard" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Dashboard</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="post" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Post (Artikel)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="headline" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Headline</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="media" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Media Library</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="files" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Files Manager</span>
                        </label>
                    </div>
                </div>

                <!-- Insights Module -->
                <div class="rounded-lg border border-border p-3 bg-muted/20">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground block mb-2">Modul Insights</span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="embed-chart" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Embed Chart</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="statistic" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Trafik & Audiens</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="statistic/report" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Performa Tim Editorial</span>
                        </label>
                    </div>
                </div>

                <!-- Management Module -->
                <div class="rounded-lg border border-border p-3 bg-muted/20">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground block mb-2">Modul Manajemen & Apps</span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="kanal/master" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Master Kanal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="kanal/sub" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Sub Kanal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="kanal/stats" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Performa Kanal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="apps-version" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Apps Version</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="apps-push-notif" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Apps Push Notif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="apps-feature-content" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Feature Content</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="member" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>Member Portal</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="pages[]" value="user" class="rounded border-input text-primary focus:ring-primary perm-checkbox">
                            <span>User Management</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-border">
                <button type="button" onclick="selectAllPerms(true)" class="text-[11px] text-primary hover:underline font-medium">Pilih Semua</button>
                <div class="flex gap-2">
                    <button type="button" onclick="closePermissionModal()" class="rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                    <button type="button" onclick="saveUserPermissions()" id="savePermBtn" class="rounded-md bg-primary px-4 py-1.5 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Simpan Izin</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function closeNewUserModal() {
    const modal = document.getElementById('newUserModal');
    if (modal) modal.style.display = 'none';
}

function openDeleteUserModal(userId, userName) {
    const modal = document.getElementById('deleteUserModal');
    const nameEl = document.getElementById('deleteUserName');
    const btn = document.getElementById('confirmDeleteUserBtn');
    if (modal && nameEl && btn) {
        nameEl.textContent = userName;
        btn.href = '<?= base_url('user/delete/') ?>/' + userId;
        modal.style.display = 'flex';
    }
}

function closeDeleteUserModal() {
    const modal = document.getElementById('deleteUserModal');
    if (modal) modal.style.display = 'none';
}

function openPermissionModal(userId, userName, userRole) {
    const modal = document.getElementById('permissionModal');
    const desc = document.getElementById('permissionUserDesc');
    const userIdInput = document.getElementById('permUserId');
    if (!modal) return;

    userIdInput.value = userId;
    desc.textContent = `Hak akses untuk: ${userName} (${userRole})`;

    // Reset checkboxes
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);

    // Fetch user permissions
    fetch('<?= base_url('user/permissions/') ?>/' + userId)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.permissions) {
                const p = res.permissions;
                document.querySelectorAll('.perm-checkbox').forEach(cb => {
                    if (p.all === true || p[cb.value] === true) {
                        cb.checked = true;
                    }
                });
            }
            modal.style.display = 'flex';
            if (window.lucide) window.lucide.createIcons();
        })
        .catch(err => {
            console.error(err);
            modal.style.display = 'flex';
        });
}

function closePermissionModal() {
    const modal = document.getElementById('permissionModal');
    if (modal) modal.style.display = 'none';
}

function selectAllPerms(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
}

function saveUserPermissions() {
    const form = document.getElementById('permissionForm');
    const btn = document.getElementById('savePermBtn');
    const formData = new FormData(form);

    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    fetch('<?= base_url('user/permissions/save') ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.textContent = 'Simpan Izin';
        if (res.status === 'success') {
            closePermissionModal();
            alert('Izin halaman berhasil diperbarui.');
        } else {
            alert('Gagal: ' + (res.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Simpan Izin';
        alert('Terjadi kesalahan jaringan.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.ModalUtils) {
        window.ModalUtils.register('newUserModal', { onClose: closeNewUserModal });
        window.ModalUtils.register('deleteUserModal', { onClose: closeDeleteUserModal });
        window.ModalUtils.register('permissionModal', { onClose: closePermissionModal });
    }
});
</script>
<?= $this->endSection() ?>
