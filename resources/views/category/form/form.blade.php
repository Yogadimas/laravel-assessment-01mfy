<form method="POST" data-cegah-ganda>
    @csrf
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="form-group">
        <label for="kode">Kode</label>
        <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" name="kode" required maxlength="50" value="{{ old('kode', $category->kode ?? '') }}">
        @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label for="nama">Nama</label>
        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" required maxlength="255" value="{{ old('nama', $category->nama ?? '') }}">
        @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ $url_kembali }}" class="btn btn-outline-secondary">Batal</a>
    </div>

</form>
