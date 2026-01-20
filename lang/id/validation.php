<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':Attribute harus diterima.',
    'accepted_if' => ':Attribute harus diterima jika :other bernilai :value.',
    'active_url' => ':Attribute harus berupa URL yang valid.',
    'after' => ':Attribute harus tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':Attribute tidak valid.',
    'array' => ':Attribute harus berupa array.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik dan simbol single-byte.',
    'before' => ':Attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus berisi :min sampai :max elemen.',
        'file' => ':Attribute harus berukuran :min sampai :max kilobyte.',
        'numeric' => ':Attribute harus bernilai diantara :min sampai :max.',
        'string' => ':Attribute harus bernilai diantara :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai true atau false.',
    'can' => ':Attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi kolom :Attribute tidak cocok.',
    'contains' => ':Attribute tidak mengandung nilai yang diharuskan.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':Attribute harus bernilai tanggal yang valid.',
    'date_equals' => ':Attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':Attribute harus sesuai dengan format :format.',
    'decimal' => ':Attribute harus memiliki :decimal nilai tempat desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak jika :other bernilai :value.',
    'different' => ':Attribute dan :other harus bernilai beda.',
    'digits' => ':Attribute harus :digits digit.',
    'digits_between' => ':Attribute harus :min sampai :max digit.',
    'dimensions' => ':Attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'ends_with' => ':Attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus memiliki ekstensi sebagai berikut: :values.',
    'file' => ':Attribute harus berupa file.',
    'filled' => ':Attribute harus diisi.',
    'gt' => [
        'array' => ':Attribute harus berisi lebih dari :value elemen.',
        'file' => ':Attribute harus lebih besar dari :value kilobyte.',
        'numeric' => ':Attribute harus lebih besar dari :value.',
        'string' => ':Attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus berisi :value elemen atau lebih.',
        'file' => ':Attribute harus lebih besar dari atau sama dengan :value kilobyte.',
        'numeric' => ':Attribute harus lebih besar dari atau sama dengan :value.',
        'string' => ':Attribute harus lebih dari atau sama dengan :value karakter.',
    ],
    'hex_color' => ':Attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute harus ada di :other.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa nilai JSON yang valid.',
    'list' => ':Attribute harus berupa list.',
    'lowercase' => ':Attribute harus huruf kecil.',
    'lt' => [
        'array' => ':Attribute harus berisi kurang dari :value elemen.',
        'file' => ':Attribute harus kurang dari :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari :value.',
        'string' => ':Attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :value elemen.',
        'file' => ':Attribute harus kurang dari atau sama dengan :value kilobyte.',
        'numeric' => ':Attribute harus kurang dari atau sama dengan :value.',
        'string' => ':Attribute harus kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :max elemen.',
        'file' => ':Attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => ':Attribute tidak boleh lebih besar dari :max.',
        'string' => ':Attribute tidak boleh lebih besar dari :max karakter.',
    ],
    'max_digits' => ':Attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => ':Attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => ':Attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => ':Attribute harus berisi setidaknya :min elemen.',
        'file' => ':Attribute harus berukuran setidaknya :min kilobyte.',
        'numeric' => ':Attribute harus bernilai setidaknya :min.',
        'string' => ':Attribute harus bernilai setidaknya :min karakter.',
    ],
    'min_digits' => ':Attribute harus memiliki setidaknya :min digit.',
    'missing' => ':Attribute harus kosong.',
    'missing_if' => ':Attribute harus kosong jika :other bernilai :value.',
    'missing_unless' => ':Attribute harus kosong kecuali :other bernilai :value.',
    'missing_with' => ':Attribute harus kosong jika :values ada.',
    'missing_with_all' => ':Attribute harus kosong jika semua :values ada.',
    'multiple_of' => ':Attribute harus kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format kolom :Attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus berisi setidaknya satu huruf.',
        'mixed' => ':Attribute harus berisi setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus berisi setidaknya satu angka.',
        'symbols' => ':Attribute harus berisi setidaknya satu simbol.',
        'uncompromised' => ':Attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :Attribute yang berbeda.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada jika :other bernilai :value.',
    'present_unless' => ':Attribute harus ada kecuali :other bernilai :value.',
    'present_with' => ':Attribute harus ada jika :values ada.',
    'present_with_all' => ':Attribute harus ada jika semua :values ada.',
    'prohibited' => ':Attribute dilarang.',
    'prohibited_if' => ':Attribute dilarang jika :other bernilai :value.',
    'prohibited_if_accepted' => ':Attribute dilarang jika :other diterima.',
    'prohibited_if_declined' => ':Attribute dilarang jika :other ditolak.',
    'prohibited_unless' => ':Attribute dilarang kecuali :other termasuk dari :values.',
    'prohibits' => ':Attribute melarang :other untuk ada.',
    'regex' => 'Format kolom :Attribute tidak valid.',
    'required' => ':Attribute harus diisi.',
    'required_array_keys' => ':Attribute harus berisi entri untuk: :values.',
    'required_if' => ':Attribute harus diisi jika :other bernilai :value.',
    'required_if_accepted' => ':Attribute harus diisi jika :other diterima.',
    'required_if_declined' => ':Attribute harus diisi jika :other ditolak.',
    'required_unless' => ':Attribute harus diisi kecuali :other termasuk dari :values.',
    'required_with' => ':Attribute harus diisi jika :values ada.',
    'required_with_all' => ':Attribute harus diisi jika semua :values ada.',
    'required_without' => ':Attribute harus diisi jika :values tidak ada.',
    'required_without_all' => ':Attribute harus diisi jika tidak ada satu pun dari :values yang ada.',
    'same' => ':Attribute dan :other harus sama.',
    'size' => [
        'array' => ':Attribute harus berisi :size elemen.',
        'file' => ':Attribute harus berukuran :size kilobyte.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus bernilai :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali dengan salah satu dari: :values.',
    'string' => ':Attribute harus berupa string.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute telah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => ':Attribute harus huruf besar.',
    'url' => ':Attribute harus berupa URL yang valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
