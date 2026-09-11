<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Lupa Kata Sandi — Investor.ID Newsroom CMS') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/img/investor-logo.ico">
    <link rel="shortcut icon" href="/img/investor-logo.ico">
    
    <!-- Modern Typography: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ["class"],
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: "#0A2540",
                            deep: "#0B1F33",
                            blue: "#0066CC",
                            accent: "#0077CC",
                        }
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/css/pages/login.css">
</head>
<body class="min-h-screen bg-[#07223D] text-white font-sans antialiased relative flex flex-col justify-between overflow-x-hidden selection:bg-[#0077CC] selection:text-white">

    <!-- Decorative Soft Background Ambient Circles -->
    <div class="login-bg-circles">
        <div class="login-circle-1"></div>
        <div class="login-circle-2"></div>
        <div class="login-circle-3"></div>
    </div>

    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-[1360px] mx-auto px-6 sm:px-10 lg:px-16 py-8 sm:py-12 flex-1 flex flex-col justify-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
            
            <!-- Left Side: Hero Info -->
            <div class="lg:col-span-6 xl:col-span-7 space-y-8 lg:pr-8">
                <div class="space-y-1">
                    <div class="flex items-center gap-1">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-white font-serif">INVESTOR<span class="text-[#38BDF8]">.ID</span></span>
                    </div>
                    <p class="text-[11px] font-bold tracking-[0.2em] text-blue-200/80 uppercase">CMS INVESTOR.ID</p>
                </div>

                <div class="space-y-4 max-w-xl">
                    <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-extrabold tracking-tight text-white leading-[1.18]">
                        Pemulihan Akses<br>Akun Editorial.
                    </h1>
                    <p class="text-sm sm:text-base text-blue-100/75 leading-relaxed max-w-md">
                        Sistem akan mengirimkan 6-digit kode verifikasi ke alamat email internal redaksi Anda untuk mereset kata sandi.
                    </p>
                </div>

                <div class="pt-6 sm:pt-10 space-y-3 max-w-md opacity-40">
                    <div class="flex items-center gap-4">
                        <div class="h-0.5 w-12 bg-white rounded-full"></div>
                        <div class="h-0.5 w-8 bg-white/60 rounded-full"></div>
                        <div class="h-0.5 w-6 bg-white/40 rounded-full"></div>
                    </div>
                    <div class="flex items-center gap-6 pl-8">
                        <div class="h-0.5 w-10 bg-white/70 rounded-full"></div>
                        <div class="h-0.5 w-14 bg-white rounded-full"></div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Forgot Password Card -->
            <div class="lg:col-span-6 xl:col-span-5 w-full max-w-[460px] mx-auto lg:ml-auto">
                <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-9 text-[#1E293B]">
                    <!-- Form Header -->
                    <div class="space-y-1.5 mb-7">
                        <div class="flex items-center gap-2 mb-2 text-[#0077CC]">
                            <i data-lucide="key-round" class="size-6"></i>
                            <span class="text-xs font-bold uppercase tracking-wider">Pemulihan Akun</span>
                        </div>
                        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#0F172A]">Lupa kata sandi?</h2>
                        <p class="text-xs sm:text-sm text-[#64748B]">Masukkan username atau email internal Anda untuk menerima kode pemulihan.</p>
                    </div>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="size-4 shrink-0"></i>
                            <span><?= esc(session()->getFlashdata('error')) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('info')): ?>
                        <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-700 flex items-center gap-2">
                            <i data-lucide="info" class="size-4 shrink-0"></i>
                            <span><?= esc(session()->getFlashdata('info')) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="post" action="<?= base_url('auth/forgot-password') ?>" class="space-y-5">
                        <?= csrf_field() ?>

                        <div>
                            <label class="block text-xs font-semibold text-[#334155] mb-1.5">Username atau Email</label>
                            <div class="relative">
                                <input type="text" name="identifier" required placeholder="e.g. raka_h atau raka@investor.id" class="flex h-11 w-full rounded-lg border border-[#CBD5E1] bg-white px-3.5 pr-10 text-sm text-[#0F172A] shadow-xs placeholder:text-[#94A3B8] focus:border-[#0077CC] focus:outline-none focus:ring-2 focus:ring-[#0077CC]/20 transition-all">
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#94A3B8] pointer-events-none flex items-center">
                                    <i data-lucide="mail" class="size-4"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-lg bg-[#0077CC] py-3 px-4 text-sm font-semibold text-white shadow-md hover:bg-[#0066B3] active:scale-[0.99] transition-all duration-150">
                            <i data-lucide="send" class="size-4"></i>
                            <span>Kirim Tautan Pemulihan</span>
                        </button>

                        <!-- Back to Login -->
                        <div class="pt-2 text-center">
                            <a href="<?= base_url('login') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#64748B] hover:text-[#0077CC] transition-colors">
                                <i data-lucide="arrow-left" class="size-3.5"></i>
                                <span>Kembali ke halaman Masuk</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Copyright -->
    <div class="relative z-10 w-full py-4 text-center border-t border-white/10 text-[11px] text-blue-200/50">
        &copy; <?= date('Y') ?> PT Investor Media Global (B-Universe). Dilindungi hak cipta.
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
