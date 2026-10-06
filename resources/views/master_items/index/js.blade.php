<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    var start_date = '';
    var end_date = '';
    var data_per_fetch = 500;
    var data_fetched = 0;

    $(document).ready(function () {
        $('#table').DataTable({
            searching: false,
            order: [[0, 'desc']],
            columnDefs: [{targets: [0, 1, 2, 3, 4, 5], render: $.fn.dataTable.render.text()}],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-12 mb-2 text-center text-md-start'i><'col-12 d-flex justify-content-center justify-content-md-end'p>>",
            drawCallback: function (settings) {
                var api = this.api();
                var info = api.page.info();
                var $pagination = $(settings.nTableWrapper).find('ul.pagination');

                $pagination.find('.dt-jump-page').remove(); // Cegah duplikat

                if (info.pages > 1) {
                    var html = `<li class="page-item dt-jump-page ms-3 d-flex align-items-center">
                            <input type="number" class="form-control form-control-sm jump-input" min="1" max="${info.pages}" placeholder="Hal" style="width: 70px;">
                            <button class="btn btn-sm btn-primary ms-1 btn-jump">Go</button>
                        </li>`;
                    $pagination.append(html);
                }
            }
        });

        $(document).on('click', '.btn-jump', function(e) {
            e.preventDefault();
            var page = parseInt($(this).siblings('.jump-input').val());
            var dataTableObj = $('#table').DataTable();
            var info = dataTableObj.page.info();

            if (page > 0 && page <= info.pages) {
                dataTableObj.page(page - 1).draw('page');
            } else {
                alert('Halaman tidak valid! Masukkan angka antara 1 sampai ' + info.pages);
            }
        });
        getData()
    });

    $('.btn-get-data').click(function () {
        getData()
    })

    function getData() {

        $('#loading-filter').show();
        var dataTableObj = $('#table').DataTable();
        var filter_kode = $('#filter-kode').val()
        var filter_nama = $('#filter-nama').val()
        var filter_harga_min = $('#filter-harga-min').val()
        var filter_harga_max = $('#filter-harga-max').val()
        dataTableObj.clear().draw();

        $.ajax({
            url: '{{url("master-items/search")}}',
            dataType: 'json',
            tryCount: 0,
            retryLimit: 3,
            data: {
                kode: filter_kode,
                nama: filter_nama,
                hargamin: filter_harga_min,
                hargamax: filter_harga_max
            },
            success: function (results) {
                // Gunakan map agar urutan kolom eksplisit 1 per 1 (mencegah kolom berantakan jika backend berubah)
                const rows = results.data.map(item => [
                    item.kode,
                    item.nama,
                    item.jenis,
                    Number(item.harga_beli), // Pastikan format angka
                    Math.round(Number(item.harga_beli) * (1 + Number(item.laba) / 100)), // Hitungan harga jual
                    item.supplier,
                    `<a class="btn btn-primary" href="{{ url('master-items/view') }}/${encodeURIComponent(item.kode)}">View</a>`
                ]);

                dataTableObj.clear();
                dataTableObj.rows.add(rows).draw();
                $('#loading-filter').hide();
            },
            error: function (xhr, textStatus, errorThrown) {
                // Tangkap khusus error validasi (422) dari backend
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = 'Validasi Gagal:\n';
                    for (let key in errors) {
                        errorMessage += '- ' + errors[key][0] + '\n';
                    }
                    alert(errorMessage);
                    $('#loading-filter').hide();
                    return; // Hentikan proses, tidak perlu retry
                }

                // Jika error lain (seperti 500 / koneksi putus), lakukan retry bawaan Anda
                this.tryCount++;
                if (this.tryCount <= this.retryLimit) {
                    $.ajax(this);
                    return;
                }
                alert('Terjadi kesalahan server, tidak dapat mengambil data');
                $('#loading-filter').hide();
            }
        });
    }
</script>
