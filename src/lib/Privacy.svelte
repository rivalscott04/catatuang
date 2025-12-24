<script>
  import { onMount } from "svelte";

  onMount(() => {
    window.scrollTo(0, 0);
  });
</script>

<div class="legal-page">
  <div class="legal-container">
    <div class="legal-header">
      <h1>Kebijakan Privasi</h1>
      <p class="last-updated">Terakhir diperbarui: {new Date().toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
    </div>

    <div class="legal-content">
      <section>
        <h2>1. Pengantar</h2>
        <p>
          CatatBot ("kami", "kita", atau "Layanan") menghormati privasi Anda dan berkomitmen untuk melindungi data pribadi yang Anda berikan kepada kami. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda saat menggunakan layanan CatatBot.
        </p>
        <p>
          Dengan menggunakan Layanan, Anda menyetujui pengumpulan dan penggunaan informasi sesuai dengan Kebijakan Privasi ini.
        </p>
      </section>

      <section>
        <h2>2. Informasi yang Kami Kumpulkan</h2>
        
        <h3>2.1. Informasi yang Anda Berikan</h3>
        <p>Kami mengumpulkan informasi yang Anda berikan secara langsung, termasuk:</p>
        <ul>
          <li><strong>Nomor WhatsApp:</strong> Nomor telepon yang digunakan untuk mendaftar dan mengakses Layanan</li>
          <li><strong>Nama:</strong> Nama yang Anda berikan saat pendaftaran (opsional)</li>
          <li><strong>Data Transaksi:</strong> Informasi transaksi keuangan yang Anda catat melalui chat atau struk</li>
          <li><strong>Gambar Struk:</strong> Foto atau gambar struk pembayaran yang Anda unggah</li>
          <li><strong>Preferensi:</strong> Pengaturan reminder, gaya balasan, dan preferensi lainnya</li>
        </ul>

        <h3>2.2. Informasi yang Dikumpulkan Otomatis</h3>
        <p>Kami juga mengumpulkan informasi secara otomatis saat Anda menggunakan Layanan:</p>
        <ul>
          <li><strong>Data Penggunaan:</strong> Jumlah chat, struk yang diunggah, dan aktivitas penggunaan</li>
          <li><strong>Log Teknis:</strong> Alamat IP, jenis perangkat, browser, dan informasi teknis lainnya</li>
          <li><strong>Metadata:</strong> Timestamp, durasi penggunaan, dan metadata lainnya</li>
        </ul>
      </section>

      <section>
        <h2>3. Cara Kami Menggunakan Informasi</h2>
        <p>Kami menggunakan informasi yang dikumpulkan untuk:</p>
        <ul>
          <li><strong>Menyediakan Layanan:</strong> Memproses dan menyimpan transaksi keuangan Anda</li>
          <li><strong>Mengirim Notifikasi:</strong> Mengirim reminder dan notifikasi penting melalui WhatsApp</li>
          <li><strong>Memproses Struk:</strong> Menggunakan OCR untuk mengekstrak informasi dari struk yang diunggah</li>
          <li><strong>Meningkatkan Layanan:</strong> Menganalisis penggunaan untuk meningkatkan fitur dan pengalaman pengguna</li>
          <li><strong>Komunikasi:</strong> Menghubungi Anda terkait Layanan, pembaruan, atau dukungan teknis</li>
          <li><strong>Keamanan:</strong> Mendeteksi dan mencegah aktivitas penipuan atau penyalahgunaan</li>
          <li><strong>Kepatuhan Hukum:</strong> Memenuhi kewajiban hukum dan peraturan yang berlaku</li>
        </ul>
      </section>

      <section>
        <h2>4. Penyimpanan Data</h2>
        <h3>4.1. Lokasi Penyimpanan</h3>
        <p>
          Data Anda disimpan di server yang berlokasi di Indonesia. Kami menggunakan layanan cloud yang mematuhi standar keamanan internasional.
        </p>

        <h3>4.2. Periode Penyimpanan</h3>
        <p>
          Kami menyimpan data Anda selama:
        </p>
        <ul>
          <li>Selama akun Anda aktif dan menggunakan Layanan</li>
          <li>Minimal 30 hari setelah akun dinonaktifkan (untuk keperluan backup dan pemulihan)</li>
          <li>Lebih lama jika diwajibkan oleh hukum atau untuk keperluan penyelesaian sengketa</li>
        </ul>

        <h3>4.3. Penghapusan Data</h3>
        <p>
          Anda dapat meminta penghapusan data dengan menghubungi kami. Setelah permintaan diverifikasi, kami akan menghapus data Anda sesuai dengan ketentuan hukum yang berlaku.
        </p>
      </section>

      <section>
        <h2>5. Pembagian Data</h2>
        <p>Kami tidak menjual, menyewakan, atau membagikan data pribadi Anda kepada pihak ketiga, kecuali dalam situasi berikut:</p>

        <h3>5.1. Penyedia Layanan</h3>
        <p>Kami dapat membagikan data kepada penyedia layanan pihak ketiga yang membantu kami mengoperasikan Layanan:</p>
        <ul>
          <li><strong>WhatsApp:</strong> Untuk mengirim dan menerima pesan</li>
          <li><strong>Payment Gateway:</strong> Untuk memproses pembayaran</li>
          <li><strong>Cloud Storage:</strong> Untuk menyimpan data dan backup</li>
          <li><strong>OCR Service:</strong> Untuk memproses struk (jika menggunakan layanan pihak ketiga)</li>
        </ul>
        <p>Semua penyedia layanan ini terikat oleh perjanjian kerahasiaan dan hanya menggunakan data untuk tujuan yang telah disepakati.</p>

        <h3>5.2. Kepatuhan Hukum</h3>
        <p>Kami dapat membagikan data jika diwajibkan oleh hukum, perintah pengadilan, atau permintaan dari otoritas yang berwenang.</p>

        <h3>5.3. Perlindungan Hak</h3>
        <p>Kami dapat membagikan data untuk melindungi hak, properti, atau keamanan CatatBot, pengguna, atau pihak lain.</p>
      </section>

      <section>
        <h2>6. Keamanan Data</h2>
        <p>Kami menerapkan berbagai langkah keamanan untuk melindungi data Anda:</p>
        <ul>
          <li><strong>Enkripsi:</strong> Data ditransmisikan menggunakan enkripsi SSL/TLS</li>
          <li><strong>Autentikasi:</strong> Sistem autentikasi berbasis nomor WhatsApp dan API key</li>
          <li><strong>Isolasi Data:</strong> Data pengguna diisolasi berdasarkan nomor WhatsApp</li>
          <li><strong>Backup Rutin:</strong> Backup data dilakukan secara berkala</li>
          <li><strong>Monitoring:</strong> Sistem keamanan dipantau 24/7 untuk mendeteksi ancaman</li>
          <li><strong>Akses Terbatas:</strong> Hanya personel yang berwenang yang dapat mengakses data</li>
        </ul>
        <p>
          Meskipun kami berusaha melindungi data Anda, tidak ada metode transmisi atau penyimpanan yang 100% aman. Kami tidak dapat menjamin keamanan absolut, namun kami berkomitmen untuk terus meningkatkan keamanan sistem.
        </p>
      </section>

      <section>
        <h2>7. Hak Anda</h2>
        <p>Anda memiliki hak-hak berikut terkait data pribadi Anda:</p>
        <ul>
          <li><strong>Akses:</strong> Meminta salinan data pribadi yang kami simpan</li>
          <li><strong>Perbaikan:</strong> Meminta perbaikan data yang tidak akurat atau tidak lengkap</li>
          <li><strong>Penghapusan:</strong> Meminta penghapusan data pribadi Anda</li>
          <li><strong>Pembatasan:</strong> Meminta pembatasan pemrosesan data dalam kondisi tertentu</li>
          <li><strong>Portabilitas:</strong> Meminta transfer data ke penyedia layanan lain</li>
          <li><strong>Keberatan:</strong> Menolak pemrosesan data untuk tujuan tertentu</li>
          <li><strong>Penarikan Persetujuan:</strong> Menarik persetujuan untuk pemrosesan data (jika berlaku)</li>
        </ul>
        <p>
          Untuk menggunakan hak-hak ini, silakan hubungi kami melalui WhatsApp atau email yang tertera di bagian kontak.
        </p>
      </section>

      <section>
        <h2>8. Cookie dan Teknologi Pelacakan</h2>
        <p>
          Saat ini, Layanan kami beroperasi melalui WhatsApp dan tidak menggunakan cookie atau teknologi pelacakan di website. Jika di masa depan kami menggunakan teknologi tersebut, kami akan memperbarui Kebijakan Privasi ini dan memberitahu Anda.
        </p>
      </section>

      <section>
        <h2>9. Privasi Anak</h2>
        <p>
          Layanan kami tidak ditujukan untuk anak di bawah 18 tahun. Kami tidak secara sengaja mengumpulkan data pribadi dari anak di bawah 18 tahun. Jika kami mengetahui bahwa kami telah mengumpulkan data dari anak di bawah 18 tahun tanpa persetujuan orang tua, kami akan segera menghapus data tersebut.
        </p>
      </section>

      <section>
        <h2>10. Perubahan Kebijakan Privasi</h2>
        <p>
          Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan diberitahukan melalui:
        </p>
        <ul>
          <li>Notifikasi di aplikasi atau WhatsApp</li>
          <li>Email ke alamat yang terdaftar (jika ada)</li>
          <li>Pembaruan di halaman ini dengan tanggal "Terakhir diperbarui"</li>
        </ul>
        <p>
          Kami menyarankan Anda untuk meninjau Kebijakan Privasi ini secara berkala. Penggunaan Layanan setelah perubahan berarti Anda menyetujui kebijakan yang baru.
        </p>
      </section>

      <section>
        <h2>11. Transfer Data Internasional</h2>
        <p>
          Data Anda terutama disimpan di server di Indonesia. Jika di masa depan kami perlu mentransfer data ke luar negeri, kami akan memastikan bahwa transfer tersebut dilakukan sesuai dengan peraturan perlindungan data yang berlaku dan dengan langkah-langkah keamanan yang memadai.
        </p>
      </section>

      <section>
        <h2>12. Kontak</h2>
        <p>
          Jika Anda memiliki pertanyaan, kekhawatiran, atau permintaan terkait Kebijakan Privasi ini atau penggunaan data pribadi Anda, silakan hubungi kami:
        </p>
        <ul>
          <li><strong>WhatsApp:</strong> [Nomor WhatsApp Support]</li>
          <li><strong>Email:</strong> privacy@catatbot.id</li>
          <li><strong>Alamat:</strong> [Alamat Perusahaan]</li>
        </ul>
        <p>
          Kami akan merespons permintaan Anda dalam waktu maksimal 30 hari kerja.
        </p>
      </section>

      <section>
        <h2>13. Persetujuan</h2>
        <p>
          Dengan menggunakan Layanan CatatBot, Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui Kebijakan Privasi ini. Jika Anda tidak setuju dengan kebijakan ini, mohon untuk tidak menggunakan Layanan kami.
        </p>
      </section>
    </div>

    <div class="legal-footer">
      <a href="#hero" class="btn-back legal-content-link">Kembali ke Beranda</a>
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

  .legal-content ul {
    color: #475569;
    line-height: 1.8;
    margin-left: 1.5rem;
    margin-bottom: 1rem;
  }

  .legal-content li {
    margin-bottom: 0.5rem;
  }

  .legal-content-link {
    color: var(--color-primary);
    text-decoration: underline;
  }

  .legal-content-link:hover {
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

