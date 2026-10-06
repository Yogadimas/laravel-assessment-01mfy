<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        var dataTableObj = $('#table').DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-12 mb-2 text-center text-md-start'i><'col-12 d-flex justify-content-center justify-content-md-end'p>>",
            order: [[0, 'desc']],
            columnDefs: [{
                targets: [0, 1],
                render: $.fn.dataTable.render.text()
            }, {
                targets: [2],
                className: 'text-end'
            }, {
                targets: [3],
                orderable: false
            }],
            language: {
                "thousands":     ".",
                "sEmptyTable":   "Tidak ada data yang tersedia",
                "sProcessing":   "Sedang memuat data...",
                "sLengthMenu":   "Tampilkan _MENU_ kategori",
                "sZeroRecords":  "Tidak ditemukan data yang sesuai",
                "sInfo":         "Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori",
                "sInfoEmpty":    "Menampilkan 0 sampai 0 dari 0 kategori",
                "sInfoFiltered": "(disaring dari _MAX_ kategori keseluruhan)",
                "sSearch":       "Cari:",
                "oPaginate": {
                    "sFirst":    "Awal",
                    "sPrevious": "Sebelumnya",
                    "sNext":     "Selanjutnya",
                    "sLast":     "Akhir"
                }
            },
            ajax: {
                url: '{{url("category/search")}}',
                type: 'GET',
                data: function(d) {
                    d.kode = $('#filter-kode').val();
                    d.nama = $('#filter-nama').val();
                },
                error: function(xhr, textStatus, errorThrown) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = 'Validasi Gagal:\n';
                        for (let key in errors) {
                            errorMessage += '- ' + errors[key][0] + '\n';
                        }
                        alert(errorMessage);
                    } else {
                        $('#pesan-error').removeClass('d-none');
                    }
                    $('#table_processing').hide();
                }
            },
            columns: [
                { data: 'kode' },
                { data: 'nama' },
                { data: 'master_items_count' },
                { data: 'action', orderable: false, searchable: false }
            ],
            drawCallback: function(settings) {
                var api = this.api();
                var info = api.page.info();
                var $pagination = $(settings.nTableWrapper).find('ul.pagination');
                
                $pagination.find('.dt-jump-page').remove();
                
                if (info.pages > 1) {
                    var html = `<li class="page-item dt-jump-page ms-3 d-flex align-items-center">
                                    <input type="number" class="form-control form-control-sm jump-input" min="1" max="${info.pages}" placeholder="Hal" style="width: 70px;">
                                    <button class="btn btn-sm btn-primary ms-1 btn-jump">Pindah</button>
                                </li>`;
                    $pagination.append(html);
                }
            }
        });
        
        $('#table').on('preXhr.dt', function () {
            $('#loading-filter').removeClass('d-none');
            $('#filter-form button').prop('disabled', true);
        });
        $('#table').on('xhr.dt', function () {
            $('#loading-filter').addClass('d-none');
            $('#filter-form button').prop('disabled', false);
        });

        $(document).on('click', '.btn-jump', function(e) {
            e.preventDefault();
            var page = parseInt($(this).siblings('.jump-input').val());
            var info = dataTableObj.page.info();
            
            if (page > 0 && page <= info.pages) {
                dataTableObj.page(page - 1).draw('page');
            } else {
                alert('Halaman tidak valid! Masukkan angka antara 1 sampai ' + info.pages);
            }
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault();
            $('#pesan-error').addClass('d-none');
            dataTableObj.ajax.reload();
        });

        $('#filter-reset').on('click', function() {
            $('#filter-form')[0].reset();
            $('#pesan-error').addClass('d-none');
            dataTableObj.ajax.reload();
        });

        let debounceTimer;
        $('#filter-kode, #filter-nama').on('keyup change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                $('#pesan-error').addClass('d-none');
                dataTableObj.ajax.reload();
            }, 500);
        });
    });
</script>
