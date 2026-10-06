<form id="filter-form" class="mb-3" novalidate>
    <h4>Filter</h4>
    <div class="row g-2">
        <div class="col-12 col-sm-6">
            <label for="filter-nama" class="form-label mb-1">Nama</label>
            <input type="text" class="form-control" id="filter-nama" autocomplete="off">
        </div>
        <div class="col-12 col-sm-6">
            <label for="filter-kode" class="form-label mb-1">Kode</label>
            <input type="text" class="form-control" id="filter-kode" autocomplete="off">
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="button" class="btn btn-outline-secondary" id="filter-reset">Reset</button>
        <span id="loading-filter" class="d-none text-muted small" role="status">
            <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Memuat data...
        </span>
    </div>
    <div id="pesan-error" class="alert alert-danger mt-2 mb-0 d-none" role="alert">
        Terjadi kesalahan server, data tidak dapat diambil. Coba lagi beberapa saat.
    </div>
</form>
