<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keamanan (2FA) — Investor.ID Newsroom CMS</title>

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

    <!-- Decorative Soft Background Ambient Circles -->
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
                        Verifikasi Dua Langkah<br>Keamanan Akun.
                    </h1>
                    <p class="text-sm sm:text-base text-blue-100/75 leading-relaxed max-w-md">
                        Sebagai prosedur keamanan 5-harian, kode autentikasi unik telah dikirim ke email terdaftar Anda.
                    </p>
                </div>

                <!-- Minimalist Line Accent Grid -->
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

            <!-- Right Side: Floating Card -->
            <div class="lg:col-span-6 xl:col-span-5 w-full max-w-[460px] mx-auto lg:ml-auto">
                <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-9 text-[#1E293B]">
                    <!-- Form Header -->
                    <div class="space-y-2 mb-6">
                        <div class="inline-flex p-3 bg-blue-50 text-[#0077CC] rounded-xl mb-1">
                            <i data-lucide="shield-check" class="size-6"></i>
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight text-[#0F172A]">Masukkan Kode Autentikasi</h2>
                        <p class="text-xs sm:text-sm text-[#64748B] leading-relaxed">
                            Kami telah mengirimkan 6-digit kode verifikasi ke email:
                            <strong class="text-[#0F172A] block font-mono mt-0.5"><?= esc($maskedEmail ?? 'email terdaftar') ?></strong>
                        </p>
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

                    <!-- OTP Form (6 Separate Input Boxes matching UI Spec) -->
                    <form method="post" action="<?= base_url('auth/verify-code') ?>" id="otpForm" class="space-y-5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="auth_code" id="authCodeHidden" required>

                        <div>
                            <label class="block text-xs font-semibold text-[#334155] mb-3 text-center">
                                Masukkan 6 Digit Kode Autentikasi
                            </label>
                            <div class="flex justify-center items-center gap-2 sm:gap-2.5" id="otpBoxContainer">
                                <?php for ($i = 0; $i < 6; $i++): ?>
                                    <input type="text" 
                                           class="otp-digit size-11 sm:size-13 text-center text-xl sm:text-2xl font-black rounded-xl border-2 border-[#CBD5E1] bg-slate-50 text-[#0F172A] shadow-xs focus:border-[#0077CC] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#0077CC]/15 transition-all font-mono select-all"
                                           maxlength="1" 
                                           inputmode="numeric" 
                                           pattern="[0-9]" 
                                           data-index="<?= $i ?>"
                                           autocomplete="off">
                                <?php endfor; ?>
                            </div>
                            <p class="text-[11px] text-[#94A3B8] text-center mt-2.5">Kode berlaku selama 15 menit</p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitOtpBtn" class="w-full flex items-center justify-center gap-2 rounded-lg bg-[#0077CC] py-3 px-4 text-sm font-semibold text-white shadow-md hover:bg-[#0066B3] active:scale-[0.99] transition-all duration-150">
                            <i data-lucide="key-round" class="size-4"></i>
                            <span>Verifikasi & Masuk</span>
                        </button>
                    </form>

                    <!-- Resend OTP Section -->
                    <div class="mt-6 pt-5 border-t border-[#F1F5F9] text-center space-y-3">
                        <p class="text-xs text-[#64748B]">
                            Tidak menerima email atau kode kadaluarsa?
                        </p>
                        <form method="post" action="<?= base_url('auth/resend-code') ?>" id="resendForm">
                            <?= csrf_field() ?>
                            <button type="submit" id="resendBtn" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0077CC] hover:text-[#005599] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-lucide="refresh-cw" class="size-3.5"></i>
                                <span id="resendText">Kirim Ulang Kode</span>
                            </button>
                        </form>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="<?= base_url('logout') ?>" class="text-xs text-[#94A3B8] hover:text-[#475569] transition-colors">
                            ← Kembali ke halaman masuk
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Page Footer Security Label -->
    <div class="relative z-10 w-full max-w-[1360px] mx-auto px-6 sm:px-10 lg:px-16 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-blue-200/60">
        <div class="flex items-center gap-2">
            <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Autentikasi Dua Langkah (2FA) Aktif</span>
        </div>
        <div>
            <span>&copy; Investor.id</span>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // 6-Digit OTP Interactive Flow
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = Array.from(document.querySelectorAll('.otp-digit'));
            const hiddenInput = document.getElementById('authCodeHidden');
            const form = document.getElementById('otpForm');

            if (!inputs.length) return;

            // Auto-focus first input
            inputs[0].focus();

            const syncHiddenInput = () => {
                const fullCode = inputs.map(input => input.value.trim()).join('');
                hiddenInput.value = fullCode;
                return fullCode;
            };

            inputs.forEach((input, index) => {
                // Input handling & auto-advance
                input.addEventListener('input', (e) => {
                    const val = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = val ? val.slice(-1) : '';

                    if (e.target.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                        inputs[index + 1].select();
                    }

                    const code = syncHiddenInput();
                    // Auto submit if all 6 digits are filled
                    if (code.length === 6) {
                        form.submit();
                    }
                });

                // Backspace & Left/Right navigation
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace') {
                        if (!input.value && index > 0) {
                            inputs[index - 1].focus();
                            inputs[index - 1].value = '';
                            syncHiddenInput();
                        } else {
                            input.value = '';
                            syncHiddenInput();
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        inputs[index - 1].focus();
                        inputs[index - 1].select();
                    } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                        inputs[index + 1].select();
                    }
                });

                // Paste handling
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text').trim();
                    const cleanNumbers = pastedData.replace(/[^0-9]/g, '').slice(0, 6);

                    if (cleanNumbers.length > 0) {
                        cleanNumbers.split('').forEach((char, i) => {
                            if (inputs[i]) {
                                inputs[i].value = char;
                            }
                        });

                        const focusIdx = Math.min(cleanNumbers.length, inputs.length - 1);
                        inputs[focusIdx].focus();
                        const code = syncHiddenInput();
                        if (code.length === 6) {
                            form.submit();
                        }
                    }
                });
            });

            form.addEventListener('submit', (e) => {
                const code = syncHiddenInput();
                if (code.length < 6) {
                    e.preventDefault();
                    alert('Silakan lengkapi 6 digit kode verifikasi terlebih dahulu.');
                    const firstEmpty = inputs.find(i => !i.value);
                    if (firstEmpty) firstEmpty.focus();
                }
            });
        });
    </script>
</body>
</html>
