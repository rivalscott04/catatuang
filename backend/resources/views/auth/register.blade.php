<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - CatatBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap');
        body { font-family: 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 mb-4">
                <svg viewBox="0 0 24 24" fill="none" class="w-8 h-8 text-[#10B981]">
                    <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z" fill="currentColor"/>
                </svg>
                <span class="text-2xl font-bold text-[#0F172A]">CatatBot</span>
            </div>
            <h1 class="text-2xl font-bold text-[#0F172A] mb-2">Daftar Sekarang</h1>
            <p class="text-[#64748B] text-sm">Mulai atur keuanganmu dengan mudah via WhatsApp</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-[#E2E8F0] p-6 md:p-8">
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <!-- Phone Number Input -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-[#0F172A] mb-2">
                        Nomor WhatsApp <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-[#64748B] text-sm">+62</span>
                        </div>
                        <input
                            type="tel"
                            id="phone_number"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            placeholder="81234567890"
                            class="block w-full pl-12 pr-4 py-3 border border-[#E2E8F0] rounded-lg focus:ring-2 focus:ring-[#10B981] focus:border-[#10B981] outline-none transition-all text-[#0F172A] placeholder-[#94A3B8]"
                            required
                            autofocus
                        />
                    </div>
                    <p class="mt-1.5 text-xs text-[#64748B]">
                        Masukkan nomor WhatsApp tanpa +62 (contoh: 81234567890)
                    </p>
                    @error('phone_number')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name Input -->
                <div>
                    <label for="name" class="block text-sm font-medium text-[#0F172A] mb-2">
                        Nama (Opsional)
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Nama lengkap"
                        class="block w-full px-4 py-3 border border-[#E2E8F0] rounded-lg focus:ring-2 focus:ring-[#10B981] focus:border-[#10B981] outline-none transition-all text-[#0F172A] placeholder-[#94A3B8]"
                    />
                    <p class="mt-1.5 text-xs text-[#64748B]">
                        Nama akan digunakan untuk personalisasi pesan bot
                    </p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-[#10B981] hover:bg-[#059669] text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                >
                    Daftar Sekarang
                </button>
            </form>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="text-sm text-[#64748B] hover:text-[#10B981] transition-colors">
                    ← Kembali ke halaman utama
                </a>
            </div>
        </div>

        <!-- Footer Info -->
        <p class="mt-6 text-center text-xs text-[#94A3B8]">
            Dengan mendaftar, Anda menyetujui syarat & ketentuan kami
        </p>
    </div>
</body>
</html>

