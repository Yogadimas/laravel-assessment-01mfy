@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-group mb-2">
                <a href="{{url('master-items')}}" class="btn btn-outline-secondary">← Kembali ke Daftar Item</a>
            </div>
            <div class="card">

                @if($method == 'new')
                <div class="card-header">Buat Master Item Baru</div>
                @else
                <div class="card-header">Edit Master Item</div>
                @endif

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{url('master-items/form')}}/{{$method}}{{ $method == 'edit' ? '/' . $item->id : '' }}" enctype="multipart/form-data">
                        @csrf
                        @include('master_items.form.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[multiple] option').forEach(option => {
            option.addEventListener('mousedown', function(e) {
                e.preventDefault();
                this.selected = !this.selected;
                this.parentElement.focus();
            });
        });

        const searchInput = document.getElementById('search-kategori');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const options = document.querySelectorAll('#kategori-select option');
                
                options.forEach(option => {
                    if (option.value) { 
                        if (option.text.toLowerCase().includes(filter)) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    }
                });
            });
        }

        // Tombol Reset Pilihan Kategori (Kembali ke Default)
        const btnResetKategori = document.getElementById('btn-reset-kategori');
        if (btnResetKategori) {
            btnResetKategori.addEventListener('click', function() {
                const options = document.querySelectorAll('#kategori-select option');
                options.forEach(option => {
                    option.selected = option.defaultSelected;
                });
            });
        }

        // Tombol Kosongkan Pilihan Kategori (Unselect Semua)
        const btnClearKategori = document.getElementById('btn-clear-kategori');
        if (btnClearKategori) {
            btnClearKategori.addEventListener('click', function() {
                const options = document.querySelectorAll('#kategori-select option');
                options.forEach(option => {
                    option.selected = false;
                });
            });
        }

        // Tombol Clear Keyword Pencarian
        const btnClearSearch = document.getElementById('btn-clear-search');
        if (btnClearSearch && searchInput) {
            btnClearSearch.addEventListener('click', function() {
                searchInput.value = '';
                // Picu ulang event keyup agar list tampil semua lagi
                searchInput.dispatchEvent(new Event('keyup'));
            });
        }

        // Preview Image sebelum upload
        const fotoInput = document.getElementById('foto-input');
        const fotoPreview = document.getElementById('foto-preview');
        const fotoPreviewLink = document.getElementById('foto-preview-link');
        const fotoPreviewContainer = document.getElementById('foto-preview-container');
        const btnCancelFoto = document.getElementById('btn-cancel-foto');
        
        if (fotoInput) {
            fotoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fotoPreview.setAttribute('src', e.target.result);
                        if (fotoPreviewLink) {
                            fotoPreviewLink.setAttribute('href', e.target.result);
                            fotoPreviewLink.style.pointerEvents = 'auto'; // biarkan bisa di-klik jika valid
                        }
                        fotoPreviewContainer.style.display = 'block';
                        if (btnCancelFoto) btnCancelFoto.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    if (btnCancelFoto) btnCancelFoto.style.display = 'none';
                    
                    @if(empty($item->foto))
                        fotoPreviewContainer.style.display = 'none';
                        fotoPreview.setAttribute('src', '#');
                        if (fotoPreviewLink) {
                            fotoPreviewLink.setAttribute('href', '#');
                            fotoPreviewLink.style.pointerEvents = 'none';
                        }
                    @else
                        // Kembalikan ke foto lama (di database) jika batal upload foto baru
                        fotoPreview.setAttribute('src', '{{ url("master-items/foto/" . basename($item->foto)) }}');
                        if (fotoPreviewLink) {
                            fotoPreviewLink.setAttribute('href', '{{ url("master-items/foto/" . basename($item->foto)) }}');
                            fotoPreviewLink.style.pointerEvents = 'auto';
                        }
                    @endif
                }
            });

            if (btnCancelFoto) {
                btnCancelFoto.addEventListener('click', function() {
                    fotoInput.value = ''; // Hapus file yang sudah dipilih
                    fotoInput.dispatchEvent(new Event('change')); // Picu event change untuk kembalikan preview
                });
            }
        }
    });
</script>
@endsection

