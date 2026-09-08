<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Investor.ID Newsroom CMS</title>

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

    <!-- Page Specific CSS -->
    <link rel="stylesheet" href="/css/pages/login.css">
</head>
<body class="min-h-screen bg-[#07223D] text-white font-sans antialiased relative flex flex-col justify-between overflow-x-hidden selection:bg-[#0077CC] selection:text-white">

    <!-- Decorative Soft Background Ambient Circles (Matching Gambar) -->
    <div class="login-bg-circles">
        <div class="login-circle-1"></div>
        <div class="login-circle-2"></div>
        <div class="login-circle-3"></div>
    </div>

    <!-- Main Responsive Grid Container -->
    <div class="relative z-10 w-full max-w-[1360px] mx-auto px-6 sm:px-10 lg:px-16 py-8 sm:py-12 flex-1 flex flex-col justify-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
            
            <!-- Left Side: Hero Branding & Slogan -->
            <div class="lg:col-span-6 xl:col-span-7 space-y-8 lg:pr-8">
                <!-- Brand Wordmark / Header Logo -->
                <div class="space-y-1">
                    <div class="flex items-center gap-1">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-white font-serif">INVESTOR<span class="text-[#38BDF8]">.ID</span></span>
                    </div>
                    <p class="text-[11px] font-bold tracking-[0.2em] text-blue-200/80 uppercase">CMS INVESTOR.ID</p>
                </div>

                <!-- Hero Heading & Subtitle -->
                <div class="space-y-4 max-w-xl">
                    <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-extrabold tracking-tight text-white leading-[1.18]">
                        Kelola informasi,<br>bangun kepercayaan.
                    </h1>
                    <p class="text-sm sm:text-base text-blue-100/75 leading-relaxed max-w-md">
                        Satu ruang kerja untuk menerbitkan konten Investor.id dengan cepat dan terkontrol.
                    </p>
                </div>

                <!-- Minimalist Line Accent Grid (Matching Referensi Garis Putih Tipis) -->
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

            <!-- Right Side: White Floating Login Card (Matching Gambar) -->
            <div class="lg:col-span-6 xl:col-span-5 w-full max-w-[460px] mx-auto lg:ml-auto">
                <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-9 text-[#1E293B]">
                    <!-- Form Header -->
                    <div class="space-y-1.5 mb-7">
                        <h2 class="text-2xl sm:text-[26px] font-bold tracking-tight text-[#0F172A]">Selamat datang kembali</h2>
                        <p class="text-xs sm:text-sm text-[#64748B]">Masukkan kredensial Anda untuk melanjutkan.</p>
                    </div>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="size-4 shrink-0"></i>
                            <span><?= esc(session()->getFlashdata('error')) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-xs text-green-700 flex items-center gap-2">
                            <i data-lucide="check-circle" class="size-4 shrink-0"></i>
                            <span><?= esc(session()->getFlashdata('success')) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="post" action="<?= base_url('login') ?>" class="space-y-5">
                        <?= csrf_field() ?>

                        <!-- Username Input -->
                        <div>
                            <label class="block text-xs font-semibold text-[#334155] mb-1.5">Username</label>
                            <div class="relative">
                                <input type="text" name="username" id="loginUsernameInput" required placeholder="e.g. raka_h" value="" autocomplete="username" class="flex h-11 w-full rounded-lg border border-[#CBD5E1] bg-white px-3.5 pr-10 text-sm text-[#0F172A] shadow-xs placeholder:text-[#94A3B8] focus:border-[#0077CC] focus:outline-none focus:ring-2 focus:ring-[#0077CC]/20 transition-all">
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#94A3B8] pointer-events-none flex items-center">
                                    <i data-lucide="user" class="size-4"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label class="block text-xs font-semibold text-[#334155] mb-1.5">Kata sandi</label>
                            <div class="relative">
                                <input type="password" name="password" id="passwordInput" required value="" placeholder="••••••••••••" class="flex h-11 w-full rounded-lg border border-[#CBD5E1] bg-white px-3.5 pr-10 text-sm text-[#0F172A] shadow-xs placeholder:text-[#94A3B8] focus:border-[#0077CC] focus:outline-none focus:ring-2 focus:ring-[#0077CC]/20 transition-all font-mono">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8] hover:text-[#475569] p-1 transition-colors" title="Toggle password visibility">
                                    <i data-lucide="eye" id="passwordEyeIcon" class="size-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full flex items-center justify-center rounded-lg bg-[#0077CC] py-3 px-4 text-sm font-semibold text-white shadow-md hover:bg-[#0066B3] active:scale-[0.99] transition-all duration-150">
                            Masuk
                        </button>
                    </form>

                    <!-- Demo Accounts Quick Fill -->
                    <div class="mt-6 pt-5 border-t border-[#F1F5F9] space-y-2">
                        <p class="text-[11px] font-bold text-[#64748B] uppercase tracking-wider">
                            Akun Demo Cepat (Klik untuk Isi):
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="fillDemo('raka_h', 'password123')" class="flex flex-col text-left p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 transition-colors">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-[#0F172A]">Raka</span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Admin</span>
                                </div>
                                <span class="text-[11px] text-blue-600 font-medium mt-0.5">@raka_h</span>
                                <span class="text-[10px] text-slate-500 font-mono mt-0.5">password123</span>
                            </button>
                            <button type="button" onclick="fillDemo('natasha_k', 'editor123')" class="flex flex-col text-left p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 transition-colors">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-[#0F172A]">Natasha</span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Editor</span>
                                </div>
                                <span class="text-[11px] text-blue-600 font-medium mt-0.5">@natasha_k</span>
                                <span class="text-[10px] text-slate-500 font-mono mt-0.5">editor123</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Page Footer Security Label -->
    <div class="relative z-10 w-full max-w-[1360px] mx-auto px-6 sm:px-10 lg:px-16 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-blue-200/60">
        <div class="flex items-center gap-2">
            <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Akses internal terproteksi</span>
        </div>
        <div>
            <span>&copy; Investor.id</span>
        </div>
    </div>

    <!-- Login Page Script -->
    <script src="/js/pages/login.js"></script>
</body>
</html>
