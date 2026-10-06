@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-group mb-2">
                <a href="{{url('master-items')}}" class="btn btn-outline-secondary">← Kembali ke Data Barang</a>
            </div>
            <div class="card">
                <div class="card-header">Master Item</div>

                <div class="card-body">
                    <table class="mb-3">
                        @if($data->foto)
                        <tr>
                            <th class="pe-3 pb-2">Foto</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">
                                <a href="{{ url('master-items/foto/' . basename($data->foto)) }}" target="_blank">
                                    <img src="{{ url('master-items/foto/' . basename($data->foto)) }}" alt="Foto barang" width="200" class="img-thumbnail">
                                </a>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th class="pe-3 pb-2">Nama</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">{{$data->nama}}</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2">Harga Beli</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">Rp {{ number_format($data->harga_beli, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2">Laba</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">{{$data->laba}}%</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2">Harga Jual</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">Rp {{ number_format($data->harga_beli + ($data->harga_beli * $data->laba / 100), 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2">Supplier</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">{{$data->supplier}}</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2">Jenis</th>
                            <td class="pe-3 pb-2">:</td>
                            <td class="pb-2">{{$data->jenis}}</td>
                        </tr>
                        <tr>
                            <th class="pe-3 pb-2 align-top">Kategori</th>
                            <td class="pe-3 pb-2 align-top">:</td>
                            <td class="pb-2">
                                @if($data->categories && $data->categories->count() > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($data->categories as $kat)
                                            <span class="badge bg-primary">{{ $kat->nama }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">- Tidak ada kategori -</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a class="btn btn-info text-white" href="{{url('master-items/form/edit')}}/{{$data->id}}">Ubah</a>
                        <form action="{{ url('master-items/delete/'.$data->id) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus barang ini?');">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
@endsection
