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
                                'Admin' => 'bg-primary/10 text-primary border-primary/20',
                                'Editor' => 'bg-accent/12 text-accent border-accent/25',
                                'Author' => 'bg-success/12 text-success border-success/25',
                                default => 'bg-muted text-muted-foreground border-border'
                            };
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
                            <button class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground hover:text-foreground hover:bg-muted" title="Permissions">
                                <i data-lucide="shield" class="size-3.5"></i>
                            </button>
                            <a href="<?= base_url('user/delete/' . $user['id']) ?>" class="inline-flex size-8 items-center justify-center rounded-md border border-input bg-background text-destructive hover:bg-destructive/10" title="Delete User" onclick="return confirm('Remove user account?')">
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="newUserModal" style="display: none; position: fixed; inset: 0; background: rgba(11,31,51,0.6); z-index: 99; align-items: center; justify-content: center; padding: 1rem;">
    <div class="surface-card w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-border pb-3">
            <h3 class="text-sm font-bold text-foreground">Register Editorial Staff</h3>
            <button type="button" onclick="document.getElementById('newUserModal').style.display='none'" class="text-muted-foreground hover:text-foreground">
                <i data-lucide="x" class="size-4"></i>
            </button>
        </div>
        <form method="post" action="<?= base_url('user/save') ?>" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Full Name</label>
                <input type="text" name="name" required placeholder="e.g. Raditya Pramana" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="raditya@investor.id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Assigned Role</label>
                <select name="role" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="Author">Author / Journalist</option>
                    <option value="Editor">Editor</option>
                    <option value="Contributor">Contributor</option>
                    <option value="Admin">Administrator</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-border">
                <button type="button" onclick="document.getElementById('newUserModal').style.display='none'" class="rounded-md border border-input bg-background px-4 py-2 text-xs font-medium text-foreground hover:bg-muted">Batal</button>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground hover:bg-primary/90">Create User</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
