<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa Validasi
    |--------------------------------------------------------------------------
    |
    | Pesan galat bawaan untuk validator. Beberapa aturan memiliki beberapa
    | versi, misalnya aturan ukuran (array, berkas, angka, dan teks). Pola
    | kalimat dibuat seragam: "Kolom :attribute ...".
    |
    */

    'accepted' => 'Kolom :attribute wajib disetujui.',
    'accepted_if' => 'Kolom :attribute wajib disetujui jika :other bernilai :value.',
    'active_url' => 'Kolom :attribute harus berupa URL yang valid.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berisi tanggal yang sama dengan atau setelah :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Kolom :attribute tidak valid.',
    'array' => 'Kolom :attribute harus berupa array.',
    'array_keys' => 'Kolom :attribute hanya boleh memuat kunci berikut: :values.',
    'ascii' => 'Kolom :attribute hanya boleh berisi huruf, angka, dan simbol ASCII.',
    'base64' => 'Kolom :attribute harus berupa teks Base64 yang valid.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berisi tanggal yang sama dengan atau sebelum :date.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min dan :max item.',
        'file' => 'Kolom :attribute harus berukuran antara :min dan :max kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min dan :max.',
        'string' => 'Kolom :attribute harus berisi antara :min dan :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai benar atau salah.',
    'can' => 'Kolom :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute tidak memuat nilai yang diwajibkan.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Kolom :attribute harus berisi tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute harus sesuai dengan format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Kolom :attribute wajib ditolak.',
    'declined_if' => 'Kolom :attribute wajib ditolak jika :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri atas :digits digit.',
    'digits_between' => 'Kolom :attribute harus terdiri atas :min sampai :max digit.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai ganda.',
    'doesnt_contain' => 'Kolom :attribute tidak boleh memuat salah satu dari: :values.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Kolom :attribute harus menggunakan pengodean :encoding.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => 'Pilihan :attribute tidak valid.',
    'exists' => 'Pilihan :attribute tidak valid.',
    'extensions' => 'Kolom :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute tidak boleh kosong.',
    'gt' => [
        'array' => 'Kolom :attribute harus berisi lebih dari :value item.',
        'file' => 'Kolom :attribute harus berukuran lebih dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih besar dari :value.',
        'string' => 'Kolom :attribute harus berisi lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus berisi :value item atau lebih.',
        'file' => 'Kolom :attribute harus berukuran :value kilobita atau lebih.',
        'numeric' => 'Kolom :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus berisi :value karakter atau lebih.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa kode warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak valid.',
    'in_array' => 'Kolom :attribute harus ada di dalam :other.',
    'in_array_keys' => 'Kolom :attribute harus memuat minimal satu dari kunci berikut: :values.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa teks JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa daftar.',
    'lowercase' => 'Kolom :attribute harus ditulis dengan huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus berisi kurang dari :value item.',
        'file' => 'Kolom :attribute harus berukuran kurang dari :value kilobita.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari :value.',
        'string' => 'Kolom :attribute harus berisi kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :value item.',
        'file' => 'Kolom :attribute harus berukuran :value kilobita atau kurang.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus berisi :value karakter atau kurang.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh berukuran lebih dari :max kilobita.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh lebih dari :max digit.',
    'mimes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus berisi minimal :min item.',
        'file' => 'Kolom :attribute harus berukuran minimal :min kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai minimal :min.',
        'string' => 'Kolom :attribute harus berisi minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute harus terdiri atas minimal :min digit.',
    'missing' => 'Kolom :attribute tidak boleh ada.',
    'missing_if' => 'Kolom :attribute tidak boleh ada jika :other bernilai :value.',
    'missing_unless' => 'Kolom :attribute tidak boleh ada kecuali :other bernilai :value.',
    'missing_with' => 'Kolom :attribute tidak boleh ada jika :values ada.',
    'missing_with_all' => 'Kolom :attribute tidak boleh ada jika :values semuanya ada.',
    'multiple_of' => 'Kolom :attribute harus kelipatan :value.',
    'not_in' => 'Pilihan :attribute tidak valid.',
    'not_regex' => 'Format kolom :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus memuat minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus memuat minimal satu huruf kapital dan satu huruf kecil.',
        'numbers' => 'Kolom :attribute harus memuat minimal satu angka.',
        'symbols' => 'Kolom :attribute harus memuat minimal satu simbol.',
        'uncompromised' => ':Attribute tersebut pernah muncul dalam kebocoran data. Silakan gunakan :attribute lain.',
    ],
    'present' => 'Kolom :attribute wajib ada.',
    'present_if' => 'Kolom :attribute wajib ada jika :other bernilai :value.',
    'present_unless' => 'Kolom :attribute wajib ada kecuali :other bernilai :value.',
    'present_with' => 'Kolom :attribute wajib ada jika :values ada.',
    'present_with_all' => 'Kolom :attribute wajib ada jika :values semuanya ada.',
    'prohibited' => 'Kolom :attribute tidak boleh diisi.',
    'prohibited_if' => 'Kolom :attribute tidak boleh diisi jika :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute tidak boleh diisi jika :other disetujui.',
    'prohibited_if_declined' => 'Kolom :attribute tidak boleh diisi jika :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute tidak boleh diisi kecuali :other bernilai salah satu dari: :values.',
    'prohibits' => 'Jika kolom :attribute diisi, :other tidak boleh diisi.',
    'regex' => 'Format kolom :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute wajib memuat entri untuk: :values.',
    'required_if' => 'Kolom :attribute wajib diisi jika :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi jika :other disetujui.',
    'required_if_declined' => 'Kolom :attribute wajib diisi jika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai salah satu dari: :values.',
    'required_with' => 'Kolom :attribute wajib diisi jika :values diisi.',
    'required_with_all' => 'Kolom :attribute wajib diisi jika :values semuanya diisi.',
    'required_without' => 'Kolom :attribute wajib diisi jika :values tidak diisi.',
    'required_without_all' => 'Kolom :attribute wajib diisi jika tidak satu pun dari :values diisi.',
    'same' => 'Kolom :attribute harus sama dengan :other.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Kolom :attribute harus berukuran :size kilobita.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus berisi :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali dengan salah satu dari: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => 'Kolom :attribute harus ditulis dengan huruf kapital.',
    'url' => 'Kolom :attribute harus berupa URL yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi Khusus
    |--------------------------------------------------------------------------
    |
    | Pesan khusus untuk pasangan atribut dan aturan tertentu, dengan pola
    | kunci "atribut.aturan". Pesan khusus milik satu form sebaiknya tetap
    | ditulis di method messages() pada Form Request-nya.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Nama Atribut
    |--------------------------------------------------------------------------
    |
    | Pengganti placeholder :attribute yang berlaku di seluruh aplikasi, untuk
    | kolom akun dan autentikasi (Fortify, pengaturan profil). Nama atribut
    | milik satu form ditulis di method attributes() pada Form Request-nya.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'code' => 'kode autentikasi',
        'recovery_code' => 'kode pemulihan',
    ],

];
