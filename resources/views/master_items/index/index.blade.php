@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-group mb-2 d-flex gap-2">
                <a href="{{url('master-items/form/new')}}" class="btn btn-primary">+ Tambah Data Barang</a>
                <a href="{{url('category')}}" class="btn btn-outline-primary">Kelola Kategori</a>
            </div>
            <div class="card">
                <div class="card-header">Daftar Barang</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @include('master_items.index.filter')
                    @include('master_items.index.table')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    // Menghilangkan alert otomatis setelah 3 detik (3000 ms)
    setTimeout(function() {
        $('.alert-success').fadeOut('slow');
    }, 3000);
</script>
@include('partials.export-js')
@include('master_items.index.js')
@endsection