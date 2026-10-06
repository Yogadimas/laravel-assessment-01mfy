@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <a href="{{url('master-items')}}" class="btn btn-outline-secondary">← Kembali ke Data Barang</a>
                <a href="{{url('category/form/new')}}" class="btn btn-primary">+ Tambah Kategori Baru</a>
            </div>
            <div class="card">
                <div class="card-header">Daftar Kategori</div>

                <div class="card-body">
                    @include('category.index.filter')
                    @include('category.index.table')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
@include('category.index.js')
@endsection
