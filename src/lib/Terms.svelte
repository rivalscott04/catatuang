<script>
  import { onMount } from "svelte";

  onMount(() => {
    window.scrollTo(0, 0);
  });
</script>

<div class="legal-page">
  <div class="legal-container">
    <div class="legal-header">
      <h1>Syarat & Ketentuan</h1>
      <p class="last-updated">Terakhir diperbarui: {new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
    </div>

    <div class="legal-content">
      <section>
        <h2>1. Penerimaan Syarat</h2>
        <p>
          Dengan mengakses dan menggunakan layanan CatatBot ("Layanan"), Anda menyetujui untuk terikat oleh Syarat & Ketentuan ini. Jika Anda tidak setuju dengan syarat-syarat ini, mohon untuk tidak menggunakan Layanan kami.
        </p>
      </section>

      <section>
        <h2>2. Deskripsi Layanan</h2>
        <p>
          CatatBot adalah layanan asisten keuangan pribadi yang beroperasi melalui WhatsApp. Layanan ini memungkinkan pengguna untuk:
        </p>
        <ul>
          <li>Mencatat transaksi keuangan melalui chat WhatsApp</li>
          <li>Mengunggah dan memproses struk pembayaran secara otomatis</li>
          <li>Menerima ringkasan dan laporan keuangan</li>
          <li>Mengatur pengingat transaksi harian</li>
        </ul>
      </section>

      <section>
        <h2>3. Pendaftaran dan Akun</h2>
        <h3>3.1. Persyaratan Pendaftaran</h3>
        <p>
          Untuk menggunakan Layanan, Anda harus:
        </p>
        <ul>
          <li>Berusia minimal 18 tahun atau memiliki persetujuan dari wali hukum</li>
          <li>Memiliki nomor WhatsApp yang aktif dan valid</li>
          <li>Memberikan informasi yang akurat dan lengkap saat pendaftaran</li>
          <li>Memiliki koneksi internet yang stabil</li>
        </ul>

        <h3>3.2. Keamanan Akun</h3>
        <p>
          Anda bertanggung jawab untuk:
        </p>
        <ul>
          <li>Menjaga kerahasiaan nomor WhatsApp dan akses ke perangkat Anda</li>
          <li>Segera melaporkan jika terjadi penggunaan akun yang tidak sah</li>
          <li>Memastikan keamanan perangkat yang digunakan untuk mengakses Layanan</li>
        </ul>
      </section>

      <section>
        <h2>4. Paket dan Pembayaran</h2>
        <h3>4.1. Paket Layanan</h3>
        <p>
          Kami menyediakan beberapa paket layanan:
        </p>
        <ul>
          <li><strong>Free:</strong> Trial 3 hari dengan 10 chat text dan 1 struk per bulan</li>
          <li><strong>Pro:</strong> Rp 29.000/bulan dengan 200 chat text dan 50 struk per bulan</li>
          <li><strong>VIP:</strong> Rp 79.000/bulan dengan unlimited chat text dan 200 struk per bulan</li>
        </ul>

        <h3>4.2. Pembayaran</h3>
        <p>
          Pembayaran dilakukan melalui payment gateway yang terintegrasi. Dengan melakukan pembayaran, Anda menyetujui:
        </p>
        <ul>
          <li>Pembayaran dilakukan secara otomatis setiap bulan (auto-renewal) kecuali dibatalkan</li>
          <li>Harga dapat berubah dengan pemberitahuan minimal 30 hari sebelumnya</li>
          <li>Pembayaran yang sudah dilakukan tidak dapat dikembalikan (non-refundable) kecuali ada kesalahan teknis dari pihak kami</li>
        </ul>

        <h3>4.3. Pembatalan</h3>
        <p>
          Anda dapat membatalkan langganan kapan saja melalui WhatsApp atau dashboard. Pembatalan akan berlaku pada akhir periode pembayaran yang sedang berjalan.
        </p>
      </section>

      <section>
        <h2>5. Limit dan Penggunaan</h2>
        <p>
          Setiap paket memiliki limit penggunaan per bulan:
        </p>
        <ul>
          <li>Limit dihitung berdasarkan kalender bulan (1-30/31 setiap bulan)</li>
          <li>Limit akan direset otomatis setiap awal bulan</li>
          <li>Penggunaan yang melebihi limit akan ditolak oleh sistem</li>
          <li>Upgrade paket dapat dilakukan kapan saja untuk meningkatkan limit</li>
        </ul>
      </section>

      <section>
        <h2>6. Data dan Privasi</h2>
        <p>
          Penggunaan data pribadi Anda diatur dalam <a href="#privasi">Kebijakan Privasi</a>. Dengan menggunakan Layanan, Anda menyetujui pengumpulan, penggunaan, dan penyimpanan data sesuai dengan Kebijakan Privasi kami.
        </p>
      </section>

      <section>
        <h2>7. Keterbatasan Layanan</h2>
        <p>
          Kami berusaha memberikan Layanan yang terbaik, namun:
        </p>
        <ul>
          <li>Layanan dapat mengalami gangguan teknis, maintenance, atau downtime</li>
          <li>Kami tidak bertanggung jawab atas kehilangan data akibat force majeure</li>
          <li>Akurasi OCR (Optical Character Recognition) untuk struk tidak dijamin 100%</li>
          <li>Layanan bergantung pada koneksi WhatsApp dan internet yang stabil</li>
        </ul>
      </section>

      <section>
        <h2>8. Larangan Penggunaan</h2>
        <p>
          Anda dilarang untuk:
        </p>
        <ul>
          <li>Menggunakan Layanan untuk tujuan ilegal atau melanggar hukum</li>
          <li>Mengirimkan konten yang mengandung virus, malware, atau kode berbahaya</li>
          <li>Mencoba mengakses sistem atau data pengguna lain tanpa izin</li>
          <li>Menggunakan Layanan untuk spam atau aktivitas yang mengganggu</li>
          <li>Menggandakan, menjual, atau menyewakan akses ke akun Anda</li>
        </ul>
      </section>

      <section>
        <h2>9. Hak Kekayaan Intelektual</h2>
        <p>
          Semua konten, fitur, dan teknologi yang ada dalam Layanan adalah milik CatatBot atau pemberi lisensinya. Anda tidak diperbolehkan untuk:
        </p>
        <ul>
          <li>Menyalin, memodifikasi, atau mendistribusikan konten tanpa izin</li>
          <li>Menggunakan merek dagang, logo, atau identitas visual kami tanpa izin</li>
          <li>Membuat produk atau layanan yang meniru atau bersaing dengan Layanan kami</li>
        </ul>
      </section>

      <section>
        <h2>10. Penghentian Layanan</h2>
        <p>
          Kami berhak untuk menghentikan atau menangguhkan akses Anda ke Layanan jika:
        </p>
        <ul>
          <li>Anda melanggar Syarat & Ketentuan ini</li>
          <li>Anda tidak melakukan pembayaran sesuai dengan ketentuan</li>
          <li>Terjadi aktivitas yang mencurigakan atau penipuan</li>
          <li>Atas permintaan otoritas hukum yang berwenang</li>
        </ul>
      </section>

      <section>
        <h2>11. Perubahan Syarat</h2>
        <p>
          Kami berhak mengubah Syarat & Ketentuan ini kapan saja. Perubahan akan diberitahukan melalui:
        </p>
        <ul>
          <li>Notifikasi di aplikasi atau WhatsApp</li>
          <li>Email ke alamat yang terdaftar (jika ada)</li>
          <li>Pembaruan di halaman ini</li>
        </ul>
        <p>
          Penggunaan Layanan setelah perubahan berarti Anda menyetujui syarat yang baru.
        </p>
      </section>

      <section>
        <h2>12. Penyelesaian Sengketa</h2>
        <p>
          Setiap sengketa yang timbul dari atau terkait dengan Layanan ini akan diselesaikan melalui:
        </p>
        <ol>
          <li><strong>Musyawarah:</strong> Upaya penyelesaian secara musyawarah terlebih dahulu</li>
          <li><strong>Mediasi:</strong> Jika musyawarah gagal, akan dilakukan mediasi</li>
          <li><strong>Pengadilan:</strong> Jika mediasi gagal, sengketa akan diselesaikan di Pengadilan Negeri yang berwenang di Indonesia</li>
        </ol>
      </section>

      <section>
        <h2>13. Kontak</h2>
        <p>
          Jika Anda memiliki pertanyaan tentang Syarat & Ketentuan ini, silakan hubungi kami melalui:
        </p>
        <ul>
          <li>WhatsApp: [Nomor WhatsApp Support]</li>
          <li>Email: support@catatbot.id</li>
        </ul>
      </section>

      <section>
        <h2>14. Ketentuan Lainnya</h2>
        <p>
          Jika salah satu ketentuan dalam dokumen ini dianggap tidak valid atau tidak dapat dilaksanakan, ketentuan lainnya tetap berlaku. Kegagalan kami untuk menegakkan hak atau ketentuan tidak berarti kami melepaskan hak tersebut.
        </p>
      </section>
    </div>

    <div class="legal-footer">
      <a href="#hero" class="btn-back">Kembali ke Beranda</a>
    </div>
  </div>
</div>

<style>
  .legal-page {
    min-height: 100vh;
    background: #f8fafc;
    padding: 2rem 0 4rem;
  }

  .legal-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
  }

  .legal-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
  }

  .legal-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-text-heading);
    margin-bottom: 0.5rem;
  }

  .last-updated {
    color: #64748b;
    font-size: 0.95rem;
  }

  .legal-content {
    background: #fff;
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .legal-content section {
    margin-bottom: 2.5rem;
  }

  .legal-content section:last-child {
    margin-bottom: 0;
  }

  .legal-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-text-heading);
    margin-bottom: 1rem;
    margin-top: 0;
  }

  .legal-content h3 {
    font-size: 1.15rem;
    font-weight: 600;
    color: var(--color-text-heading);
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
  }

  .legal-content p {
    color: #475569;
    line-height: 1.8;
    margin-bottom: 1rem;
  }

  .legal-content ul,
  .legal-content ol {
    color: #475569;
    line-height: 1.8;
    margin-left: 1.5rem;
    margin-bottom: 1rem;
  }

  .legal-content li {
    margin-bottom: 0.5rem;
  }

  .legal-content a {
    color: var(--color-primary);
    text-decoration: underline;
  }

  .legal-content a:hover {
    color: var(--color-primary-dark);
  }

  .legal-footer {
    text-align: center;
    margin-top: 3rem;
  }

  .btn-back {
    display: inline-block;
    padding: 0.75rem 2rem;
    background: var(--color-primary);
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
  }

  .btn-back:hover {
    background: var(--color-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  @media (max-width: 768px) {
    .legal-header h1 {
      font-size: 2rem;
    }

    .legal-content {
      padding: 2rem 1.5rem;
    }

    .legal-content h2 {
      font-size: 1.25rem;
    }
  }
</style>

