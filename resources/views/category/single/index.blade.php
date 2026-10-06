@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-group mb-2">
                <a href="{{url('category')}}" class="btn btn-outline-secondary">← Kembali ke Daftar Kategori</a>
            </div>
            <div class="card">
                <div class="card-header">Detail Kategori</div>

                <div class="card-body">
                    <table class="mb-3">
                        <tr>
                            <th class="pe-2">Nama</th>
                            <td class="pe-2">:</td>
                            <td>{{$category->nama}}</td>
                        </tr>
                        <tr>
                            <th class="pe-2">Kode</th>
                            <td class="pe-2">:</td>
                            <td>{{$category->kode}}</td>
                        </tr>
                    </table>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-info text-white" href="{{url('category/form/edit')}}/{{$category->id}}">Ubah</a>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-download-pdf">Download PDF</button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-konfirmasi-hapus" data-url="{{url('category/delete')}}/{{$category->id}}" data-pesan="Yakin ingin menghapus kategori {{ $category->nama }}?">Hapus</button>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
                        <h5 class="mb-0">Daftar Barang ({{ $category->masterItems->count() }})</h5>
                        <a href="{{ url('master-items/form/new') }}?category={{ $category->id }}" class="btn btn-outline-primary btn-sm">+ Barang di Kategori Ini</a>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Jenis</th>
                                <th>Supplier</th>
                                <th>Lihat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category->masterItems as $item)
                            <tr>
                                <td>{{$item->kode}}</td>
                                <td>{{$item->nama}}</td>
                                <td>{{$item->jenis}}</td>
                                <td>{{$item->supplier}}</td>
                                <td><a href="{{url('master-items/view')}}/{{$item->kode}}" class="btn btn-primary btn-sm">Lihat</a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada barang di kategori ini</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Download PDF -->
<div class="modal fade" id="modal-download-pdf" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download PDF Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @php
                    $totalItems = $category->masterItems->count();
                    $batas = 100;
                    $totalPages = ceil($totalItems / $batas);
                @endphp
                <p>Terdapat <strong>{{ $totalItems }}</strong> barang dalam kategori ini.</p>
                <p>Untuk menghindari kegagalan sistem, maksimal data yang dapat didownload per file PDF adalah <strong>{{ $batas }}</strong> data. Silakan pilih bagian yang ingin diunduh:</p>
                
                @if($totalItems > 0)
                    <div class="list-group mt-3">
                        @for($i = 1; $i <= $totalPages; $i++)
                            @php
                                $start = (($i - 1) * $batas) + 1;
                                $end = min($i * $batas, $totalItems);
                            @endphp
                            <button type="button" data-page="{{ $i }}" class="btn-download-pdf list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Part {{ $i }}</h6>
                                    <small class="text-muted">Data ke-{{ $start }} sampai {{ $end }}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">Download</span>
                            </button>
                        @endfor
                    </div>
                @else
                    <div class="alert alert-warning mb-0">Belum ada data barang untuk diunduh.</div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
@include('partials.export-js')
<script>
    $(document).on('click', '.btn-download-pdf', function () {
        var $btn = $(this);
        requestExport('{{ url('exports/category-pdf/' . $category->id) }}', { page: $btn.data('page') }, null);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-download-pdf')).hide();
    });
</script>
@endsection
