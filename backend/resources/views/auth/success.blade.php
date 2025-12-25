<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil - CatatBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap');
        body { font-family: 'Outfit', system-ui, sans-serif; }
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .checkmark-animation {
            animation: checkmark 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Success Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-[#10B981] rounded-full mb-6 checkmark-animation">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-[#0F172A] mb-2">Registrasi Berhasil!</h1>
            <p class="text-[#64748B]">
                Akun dengan nomor <span class="font-semibold text-[#0F172A]">{{ $phoneNumber ?? '' }}</span> telah dibuat
            </p>
        </div>

        <!-- Instructions Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-[#E2E8F0] p-6 md:p-8 mb-6">
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-[#0F172A] mb-3">Mulai Chat dengan Bot</h2>
                    <p class="text-[#64748B] text-sm mb-4">
                        Kirim pesan "Halo" ke nomor WhatsApp bot kami untuk mulai menggunakan CatatBot.
                    </p>
                </div>

                <!-- Bot Number Display -->
                <div class="bg-[#F0FDF4] border border-[#D1FAE5] rounded-lg p-4 mb-4">
                    <p class="text-xs text-[#64748B] mb-1">Nomor WhatsApp Bot:</p>
                    <p class="text-lg font-semibold text-[#0F172A]">{{ $botNumber ?? '6281234567890' }}</p>
                </div>

                <!-- WhatsApp Button -->
                <a
                    href="https://wa.me/{{ $botNumber ?? '6281234567890' }}?text=Halo"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block w-full bg-[#25D366] hover:bg-[#20BA5A] text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-center flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Buka WhatsApp
                </a>

                <!-- Instructions List -->
                <div class="mt-6 space-y-2">
                    <p class="text-sm font-medium text-[#0F172A] mb-2">Cara menggunakan:</p>
                    <ol class="space-y-2 text-sm text-[#64748B]">
                        <li class="flex items-start gap-2">
                            <span class="text-[#10B981] font-bold">1.</span>
                            <span>Klik tombol "Buka WhatsApp" di atas</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#10B981] font-bold">2.</span>
                            <span>Kirim pesan "Halo" ke bot</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-[#10B981] font-bold">3.</span>
                            <span>Mulai catat transaksi dengan format: "Makan 25rb" atau "Gaji 5jt"</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="text-center">
            <a href="{{ url('/') }}" class="text-sm text-[#64748B] hover:text-[#10B981] transition-colors">
                ← Kembali ke halaman utama
            </a>
        </div>
    </div>
</body>
</html>


