    @if($method == 'edit')
        <div class="form-group">
            <label>Kode Barang</label>
            <input type="text" class="form-control" name="kode_barang" required readonly
                   value="{{ old("kode_barang", $item->kode ?? '')}}">
        </div>
    @endif

    <div class="form-group">
        <label>Nama</label>
        <input type="text" class="form-control" name="nama" required value="{{ old("nama", $item->nama ?? '') }}">
    </div>

    <div class="form-group">
        <label>Harga Beli</label>
        <input type="number" class="form-control" name="harga_beli" required
               value="{{old("harga_beli", $item->harga_beli ?? '')}}">
    </div>

    <div class="form-group">
        <label>Laba (dalam persen)</label>
        <input type="number" class="form-control" name="laba" required value="{{old("laba", $item->laba ?? '') }}">
    </div>

    @php $selected = old('supplier', $item->supplier ?? ''); @endphp
    <div class="form-group">
        <label>Supplier</label>
        <select class="form-control" required name="supplier">
            <option @if($selected == '') selected @endif value="">--Pilih--</option>
            <option @if($selected == 'Tokopaedi') selected @endif>Tokopaedi</option>
            <option @if($selected == 'Bukulapuk') selected @endif>Bukulapuk</option>
            <option @if($selected == 'TokoBagas') selected @endif>TokoBagas</option>
            <option @if($selected == 'E Commurz') selected @endif>E Commurz</option>
            <option @if($selected == 'Blublu') selected @endif>Blublu</option>
        </select>
    </div>

    @php $selected = old('jenis', $item->jenis ?? ''); @endphp
    <div class="form-group">
        <label>Jenis</label>
        <select class="form-control" required name="jenis">
            <option @if($selected == '') selected @endif value="">--Pilih--</option>
            <option @if($selected == 'Obat') selected @endif>Obat</option>
            <option @if($selected == 'Alkes') selected @endif>Alkes</option>
            <option @if($selected == 'Matkes') selected @endif>Matkes</option>
            <option @if($selected == 'Umum') selected @endif>Umum</option>
            <option @if($selected == 'ATK') selected @endif>ATK</option>
        </select>
    </div>

    <div class="form-group mt-3">
        <label>Foto Barang (Opsional)</label>
        <div class="input-group">
            <input type="file" name="foto" id="foto-input" class="form-control" accept="image/jpeg,image/png,image/webp">
            <button type="button" class="btn btn-outline-secondary" id="btn-cancel-foto" style="display: none;">Batal Pilih</button>
        </div>
        
        <div class="mt-2" id="foto-preview-container" style="display: {{ $item->foto ? 'block' : 'none' }};">
            <a id="foto-preview-link" href="{{ $item->foto ? url('master-items/foto/' . basename($item->foto)) : '#' }}" target="_blank" style="{{ $item->foto ? '' : 'pointer-events: none;' }}">
                <img id="foto-preview" src="{{ $item->foto ? url('master-items/foto/' . basename($item->foto)) : '#' }}" alt="Preview Foto" width="150" class="img-thumbnail">
            </a>
            
            @if ($item->foto)
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapus-foto">
                    <label class="form-check-label text-danger" for="hapus-foto">
                        Hapus Foto Saat Ini
                    </label>
                </div>
            @endif
        </div>
    </div>

    <div class="form-group mt-3">
        <div class="d-flex align-items-center mb-2">
            <label class="mb-0">Kategori (Boleh Pilih Lebih dari Satu)</label> 
            <a href="{{ url('/category/form/new') }}" class="btn btn-sm btn-outline-primary ms-3" target="_blank">
                + Tambah Kategori Baru
            </a>
            <button type="button" class="btn btn-sm btn-outline-warning ms-2" id="btn-reset-kategori" title="Kembalikan ke pilihan asal">
                Reset
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="btn-clear-kategori" title="Hapus semua pilihan kategori">
                Kosongkan
            </button>
        </div>
        <div class="input-group input-group-sm mb-2">
            <input type="text" id="search-kategori" class="form-control" placeholder="Cari nama kategori di sini...">
            <button class="btn btn-outline-secondary" type="button" id="btn-clear-search">Bersihkan</button>
        </div>
        <select name="category_ids[]" multiple class="form-control" id="kategori-select">
            @if($categories->isEmpty())
                <option disabled>Data Kategori masih kosong. Klik tombol tambah di atas!</option>
            @else
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCategoryIds))>
                        {{ $category->nama }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    <button class="btn btn-primary mt-3">Submit</button>
