<script>
    /**
     * Helper export berbasis queue.
     * requestExport(url, data, $btn): POST -> polling status -> otomatis download saat selesai.
     */
    // IIFE: membungkus kode agar variabel/fungsi internal tidak bocor ke global
    (function () {
        // Menampilkan notifikasi kecil (toast) di pojok kanan atas; type = info/success/warning/danger
        function toast(message, type) {
            // Cari wadah toast yang sudah ada
            var $c = $('#export-toast-container');
            // Jika belum ada, buat wadahnya dan tempel ke <body>
            if (!$c.length) {
                $c = $('<div id="export-toast-container" style="position:fixed;top:1rem;right:1rem;z-index:2000;width:340px;"></div>').appendTo('body');
            }
            // Buat alert Bootstrap; .text() mengisi pesan sebagai teks biasa (aman dari XSS), lalu masukkan ke wadah
            var $t = $('<div class="alert alert-' + (type || 'info') + ' shadow-sm mb-2"></div>').text(message).appendTo($c);
            // Setelah 6 detik, pudarkan lalu hapus toast dari DOM
            setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 6000);
            // Kembalikan elemen toast
            return $t;
        }

        // Polling: bertanya ke server secara berkala "apakah file sudah jadi?"
        // statusUrl = URL cek status, $btn = tombol pemicu, label = teks asli tombol, tries = jumlah percobaan sejauh ini
        function poll(statusUrl, $btn, label, tries) {
            // Batas 300 percobaan x 2 detik = sekitar 10 menit, setelah itu menyerah
            if (tries > 300) { // ~10 menit
                // Beri tahu pengguna bahwa proses terlalu lama
                toast('Proses terlalu lama. Silakan coba lagi nanti.', 'warning');
                // Kembalikan tombol ke keadaan semula lalu berhenti
                return done($btn, label);
            }
            // Request GET ke endpoint status dan baca responnya sebagai JSON
            $.getJSON(statusUrl).done(function (res) {
                // Status 'done' = file sudah selesai dibuat oleh worker
                if (res.status === 'done') {
                    // Informasikan bahwa unduhan dimulai
                    toast('File siap, mengunduh...', 'success');
                    // Arahkan browser ke URL download sehingga file langsung terunduh
                    window.location.href = res.download_url;
                    // Aktifkan kembali tombol
                    return done($btn, label);
                }
                // Status 'failed' = job gagal di server
                if (res.status === 'failed') {
                    // Tampilkan pesan error dari server (atau pesan umum bila kosong)
                    toast(res.error || 'Gagal membuat file.', 'danger');
                    // Aktifkan kembali tombol
                    return done($btn, label);
                }
                // Status masih pending/processing: cek lagi 2 detik kemudian dengan percobaan + 1
                setTimeout(function () { poll(statusUrl, $btn, label, tries + 1); }, 2000);
            // Jika request status itu sendiri gagal (jaringan putus, 404, dll)
            }).fail(function () {
                // Beri tahu pengguna
                toast('Gagal memeriksa status export.', 'danger');
                // Aktifkan kembali tombol
                done($btn, label);
            });
        }

        // Mengembalikan tombol ke keadaan normal (aktif dan teks asli)
        function done($btn, label) {
            // $btn bisa null (misalnya pada export PDF dari modal), jadi dicek dulu
            if ($btn) { $btn.prop('disabled', false).text(label); }
        }

        // Fungsi publik yang dipanggil dari halaman lain: kirim permintaan export lalu mulai polling
        window.requestExport = function (url, data, $btn) {
            // Simpan teks asli tombol agar bisa dikembalikan nanti
            var label = $btn ? $btn.text() : '';
            // Nonaktifkan tombol dan ubah teks agar tidak diklik berulang
            if ($btn) { $btn.prop('disabled', true).text('Memproses...'); }

            // Ambil token CSRF dari meta tag di layout (wajib untuk request POST Laravel)
            var csrf = $('meta[name="csrf-token"]').attr('content');

            // Kirim permintaan export ke server
            $.ajax({
                // Endpoint pembuat export (Excel atau PDF)
                url: url,
                // Memakai POST karena membuat data baru (record export)
                method: 'POST',
                // Kirim filter/parameter; jika kosong kirim objek kosong
                data: data || {},
                // Sertakan token CSRF dan minta response JSON
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            // Server menjawab 202 (diterima): job sudah masuk antrean
            }).done(function (res) {
                // Beri tahu pengguna bahwa file sedang dibuat di background
                toast('Permintaan diterima. File sedang dibuat di background...', 'info');
                // Mulai polling ke status_url yang diberikan server, percobaan ke-0
                poll(res.status_url, $btn, label, 0);
            // Permintaan ditolak atau error
            }).fail(function (xhr) {
                // Pesan default untuk error tak terduga
                var msg = 'Terjadi kesalahan, silakan coba lagi.';
                // 422 = validasi gagal: ambil pesan error pertama dari response
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors)[0][0];
                // 429 = terkena rate limit (terlalu sering meminta export)
                } else if (xhr.status === 429) {
                    msg = 'Terlalu banyak permintaan. Coba lagi sebentar lagi.';
                }
                // Tampilkan pesan error
                toast(msg, 'danger');
                // Aktifkan kembali tombol
                done($btn, label);
            });
        };
    })();
</script>
