## Catatan Minggu 2

1. **Baris mana di `routes/web.php` yang menangkapnya?**

`/tentang` diambil dari code di `web.php`

```php
Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');
```

---
**2. Kalau ditangani controller, berkas dan method mana?**

`/tentang` diatur di dalam route bukan di controller, jika diatur kedalam controller maka code akan menambahkan kode method dan class didalamnya dan di taruh kedalam folder `controller`

---
**3. View mana yang dikembalikan? Di path apa persisnya?**

