@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @php $url_kembali = $method == 'edit' ? url('category/view/' . $category->id) : url('category'); @endphp
            <div class="form-group mb-2">
                <a href="{{ $url_kembali }}" class="btn btn-outline-secondary">← {{ $method == 'edit' ? 'Kembali ke Detail Kategori' : 'Kembali ke Daftar Kategori' }}</a>
            </div>
            <div class="card">

                @if($method == 'new')
                <div class="card-header">Buat category Item Baru</div>
                @else
                <div class="card-header">Edit category Item</div>
                @endif

                <div class="card-body">
                    @include('category.form.form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
@endsection
