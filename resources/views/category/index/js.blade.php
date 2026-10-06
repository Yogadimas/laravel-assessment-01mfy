<!-- Muat jQuery (dibutuhkan DataTables dan semua event handler di bawah) -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<!-- Muat inti library DataTables -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<!-- Muat integrasi tampilan DataTables dengan Bootstrap 5 -->
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Jalankan kode setelah seluruh DOM (halaman) siap dipakai
    $(document).ready(function() {
        // Inisialisasi DataTables pada <table id="table"> dan simpan objeknya untuk dipakai ulang (reload, ganti halaman)
        var dataTableObj = $('#table').DataTable({
            // Pagination, sorting, dan filter diproses server (data dimuat per halaman, bukan sekaligus)
            serverSide: true,
            // Tampilkan indikator "Sedang memuat data..." saat request berjalan
            processing: true,
            // Matikan kotak pencarian bawaan karena kita memakai form filter sendiri
            searching: false,
            // Atur tata letak elemen tabel: baris 1 = pilihan jumlah baris (l) dan search (f)
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 // baris 2 = tabel (t) dan indikator processing (r)
                 "<'row'<'col-sm-12'tr>>" +
                 // baris 3 = teks info jumlah data (i) dan tombol pagination (p)
                 "<'row mt-3'<'col-12 mb-2 text-center text-md-start'i><'col-12 d-flex justify-content-center justify-content-md-end'p>>",
            // Urutan awal: kolom pertama (index 0 = kode) dari terbesar ke terkecil
            order: [[0, 'desc']],
            // Pengaturan khusus per kolom
            columnDefs: [{
                // Kolom kode dan nama dirender sebagai teks biasa (bukan HTML) agar aman dari XSS
                targets: [0, 1],
                render: $.fn.dataTable.render.text()
            }, {
                // Kolom jumlah item (index 2) rata kanan karena berisi angka
                targets: [2],
                className: 'text-end'
            }, {
                // Kolom aksi (index 3) tidak bisa diurutkan
                targets: [3],
                orderable: false
            }],
            // Terjemahan teks bawaan DataTables ke Bahasa Indonesia
            language: {
                // Pemisah ribuan memakai titik
                "thousands":     ".",
                // Teks saat tabel kosong
                "sEmptyTable":   "Tidak ada data yang tersedia",
                // Teks saat data sedang dimuat
                "sProcessing":   "Sedang memuat data...",
                // Label dropdown jumlah baris per halaman (_MENU_ diganti angka pilihan)
                "sLengthMenu":   "Tampilkan _MENU_ kategori",
                // Teks saat filter tidak menemukan hasil
                "sZeroRecords":  "Tidak ditemukan data yang sesuai",
                // Info posisi data (_START_, _END_, _TOTAL_ diisi otomatis)
                "sInfo":         "Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori",
                // Info saat tidak ada data
                "sInfoEmpty":    "Menampilkan 0 sampai 0 dari 0 kategori",
                // Info tambahan saat data sedang difilter (_MAX_ = total tanpa filter)
                "sInfoFiltered": "(disaring dari _MAX_ kategori keseluruhan)",
                // Label kotak pencarian bawaan
                "sSearch":       "Cari:",
                // Teks tombol pagination
                "oPaginate": {
                    "sFirst":    "Awal",
                    "sPrevious": "Sebelumnya",
                    "sNext":     "Selanjutnya",
                    "sLast":     "Akhir"
                }
            },
            // Konfigurasi pengambilan data dari server via AJAX
            ajax: {
                // Endpoint pencarian kategori (dibuat oleh Laravel helper url())
                url: '{{url("category/search")}}',
                // Request memakai method GET
                type: 'GET',
                // Tambahkan nilai filter dari form ke parameter request tiap kali tabel dimuat
                data: function(d) {
                    // Filter kode kategori
                    d.kode = $('#filter-kode').val();
                    // Filter nama kategori
                    d.nama = $('#filter-nama').val();
                },
                // Dijalankan jika request gagal
                error: function(xhr, textStatus, errorThrown) {
                    // Status 422 = validasi gagal di server
                    if (xhr.status === 422) {
                        // Ambil daftar error validasi dari response JSON
                        let errors = xhr.responseJSON.errors;
                        // Awali pesan dengan judul
                        let errorMessage = 'Validasi Gagal:\n';
                        // Loop setiap field yang error
                        for (let key in errors) {
                            // Tambahkan pesan error pertama dari tiap field
                            errorMessage += '- ' + errors[key][0] + '\n';
                        }
                        // Tampilkan semua pesan validasi dalam satu alert
                        alert(errorMessage);
                    } else {
                        // Error lain (misalnya 500): munculkan elemen pesan error di halaman
                        $('#pesan-error').removeClass('d-none');
                    }
                    // Sembunyikan indikator loading bawaan DataTables
                    $('#table_processing').hide();
                }
            },
            // Urutan kolom harus sama dengan <th> di tabel; 'data' = nama key pada JSON dari server
            columns: [
                { data: 'kode' },
                { data: 'nama' },
                // Jumlah barang pada kategori (hasil withCount di controller)
                { data: 'master_items_count' },
                // Kolom tombol aksi: tidak bisa diurutkan dan tidak ikut pencarian
                { data: 'action', orderable: false, searchable: false }
            ],
            // Dijalankan setiap kali tabel selesai digambar ulang (ganti halaman, filter, dll)
            drawCallback: function(settings) {
                // API DataTables untuk instance ini
                var api = this.api();
                // Info halaman: halaman aktif, total halaman, dll
                var info = api.page.info();
                // Cari elemen daftar pagination milik tabel ini
                var $pagination = $(settings.nTableWrapper).find('ul.pagination');
                
                // Hapus kotak "loncat halaman" lama agar tidak dobel
                $pagination.find('.dt-jump-page').remove();
                
                // Tampilkan kotak loncat hanya bila halaman lebih dari satu
                if (info.pages > 1) {
                    // Buat HTML input nomor halaman dan tombol "Pindah" (max diisi jumlah halaman)
                    var html = `<li class="page-item dt-jump-page ms-3 d-flex align-items-center">
                                    <input type="number" class="form-control form-control-sm jump-input" min="1" max="${info.pages}" placeholder="Hal" style="width: 70px;">
                                    <button class="btn btn-sm btn-primary ms-1 btn-jump">Pindah</button>
                                </li>`;
                    // Tambahkan ke akhir pagination
                    $pagination.append(html);
                }
            }
        });
        
        // Sebelum request AJAX dikirim: tampilkan indikator loading dan matikan semua tombol filter
        $('#table').on('preXhr.dt', function () {
            $('#loading-filter').removeClass('d-none');
            $('#filter-form button').prop('disabled', true);
        });
        // Setelah response diterima: sembunyikan indikator loading dan aktifkan lagi tombol filter
        $('#table').on('xhr.dt', function () {
            $('#loading-filter').addClass('d-none');
            $('#filter-form button').prop('disabled', false);
        });

        // Event handler global untuk tombol Pindah halaman
        // Memakai $(document) karena tombol dibuat ulang dinamis pada setiap drawCallback
        $(document).on('click', '.btn-jump', function(e) {
            // Cegah perilaku default tombol
            e.preventDefault();
            // Ambil angka halaman dari input di sebelah tombol, ubah menjadi integer
            var page = parseInt($(this).siblings('.jump-input').val());
            // Ambil info halaman terkini (untuk tahu jumlah halaman)
            var info = dataTableObj.page.info();
            
            // Halaman valid: lebih dari 0 dan tidak melebihi total halaman
            if (page > 0 && page <= info.pages) {
                // Pindah ke halaman tersebut (index DataTables mulai dari 0, maka dikurangi 1)
                dataTableObj.page(page - 1).draw('page');
            } else {
                // Beri tahu pengguna rentang halaman yang benar
                alert('Halaman tidak valid! Masukkan angka antara 1 sampai ' + info.pages);
            }
        });

        // Saat form filter di-submit (tombol Cari / Enter)
        $('#filter-form').on('submit', function(e) {
            // Cegah reload halaman penuh
            e.preventDefault();
            // Sembunyikan pesan error lama
            $('#pesan-error').addClass('d-none');
            // Muat ulang data tabel dengan filter terbaru
            dataTableObj.ajax.reload();
        });

        // Saat tombol Reset filter diklik
        $('#filter-reset').on('click', function() {
            // Kosongkan semua input filter (reset form bawaan browser)
            $('#filter-form')[0].reset();
            // Sembunyikan pesan error lama
            $('#pesan-error').addClass('d-none');
            // Muat ulang data tanpa filter
            dataTableObj.ajax.reload();
        });

        // Timer untuk debounce: menunda reload sampai pengguna berhenti mengetik
        let debounceTimer;
        // Saat pengguna mengetik/mengubah input kode atau nama
        $('#filter-kode, #filter-nama').on('keyup change', function() {
            // Batalkan timer sebelumnya agar tidak ada banyak request beruntun
            clearTimeout(debounceTimer);
            // Mulai timer baru: reload dijalankan 500 ms setelah ketikan terakhir
            debounceTimer = setTimeout(function() {
                // Sembunyikan pesan error lama
                $('#pesan-error').addClass('d-none');
                // Muat ulang data tabel dengan filter terbaru
                dataTableObj.ajax.reload();
            }, 500);
        });
    });
</script>
