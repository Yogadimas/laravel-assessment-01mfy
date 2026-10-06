# Assessment

Aplikasi Laravel 9 untuk mengelola Master Items (ada upload foto) dan Kategori (relasi many-to-many dengan item). Export Excel dan PDF dikerjakan lewat queue, jadi tidak membuat halaman menunggu.

## Yang dibutuhkan

- PHP 8.2 ke atas, dengan ekstensi `gd`, `mbstring`, `zip`, `xml`, dan `pdo_mysql`
- Composer
- Node.js 16 ke atas
- MySQL atau MariaDB

## Cara install

```bash
composer install
npm install

copy .env.example .env      # di Linux/Mac: cp .env.example .env
php artisan key:generate
```

Buat database kosong, lalu sesuaikan bagian ini di `.env`:

```
DB_DATABASE=db_medify_test
DB_USERNAME=root
DB_PASSWORD=
```

Setelah itu jalankan migrasi. Tabel `jobs` dan `report_exports` untuk queue ikut terbuat di sini.

```bash
php artisan migrate
```

Kalau mau data contoh (beberapa kategori dan 10.000 item), jalankan:

```bash
php artisan db:seed
```

## Cara menjalankan

```bash
npm run start
```

Perintah ini menyalakan tiga hal sekaligus: web server (`php artisan serve`), queue worker, dan Vite. Aplikasi bisa dibuka di http://127.0.0.1:8000.

Kalau ingin menjalankannya satu per satu:

```bash
npm run serve   # web server
npm run queue   # queue worker
npm run dev     # vite
```

Queue worker wajib hidup. Kalau tidak, tombol Download Excel dan Download PDF akan menunggu terus karena tidak ada yang memproses jobnya.

Untuk masuk ke aplikasi, daftar dulu lewat `/register`, lalu login.

## Cara kerja export

1. Tombol download mengirim request ke server. Server mencatatnya di tabel `report_exports` dan memasukkan job ke antrian.
2. Browser mengecek status setiap 2 detik.
3. Begitu worker selesai, file langsung terunduh. File hanya bisa diunduh oleh user yang memintanya.
4. File disimpan di `storage/app/exports` dan dihapus otomatis setelah 1 hari. Penghapusan ini butuh scheduler: `php artisan schedule:work` di lokal, atau cron `* * * * * php artisan schedule:run` di server.

Kalau ada job yang gagal, lihat dengan `php artisan queue:failed` dan ulangi dengan `php artisan queue:retry all`.

## Pengamanan

- Rate limit per fitur (ada di `app/Providers/RouteServiceProvider.php`): pencarian 120/menit, export Excel 5/menit, PDF 10/menit, simpan/hapus 30/menit, foto 240/menit.
- Validasi dipisah ke Form Request (`app/Http/Requests`), format response DataTables dipisah ke Resource (`app/Http/Resources`).
- Foto yang diupload divalidasi, di-resize dan di-encode ulang ke JPEG, diberi nama UUID, lalu disimpan di folder privat. Foto hanya bisa dilihat lewat route yang butuh login.
- Kolom `kode`, `nama`, dan `harga_beli` di tabel `master_items` sudah diberi index.



## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


