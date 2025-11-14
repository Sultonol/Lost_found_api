<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Nonaktifkan pengecekan foreign key sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Kosongkan tabel seperti sebelumnya
        DB::table('categories')->truncate();

        // Daftar kategori yang umum untuk lingkungan kampus
        $categories = [
            ['name' => 'Elektronik'], // ID 1
            ['name' => 'Dokumen & Kartu'], // ID 2 (KTM, KTP, SIM, ATM)
            ['name' => 'Aksesoris & Pakaian'], // ID 3 (Jaket, Kacamata, Jam)
            ['name' => 'Kunci'], // ID 4 (Motor, Mobil, Kos)
            ['name' => 'Peralatan Tulis & Buku'], // ID 5
            ['name' => 'Tas & Dompet'], // ID 6
            ['name' => 'Lainnya'], // ID 7
        ];

        // Masukkan data ke dalam tabel menggunakan Model
        foreach ($categories as $category) {
            Category::create($category);
        }

        // 3. Aktifkan kembali pengecekan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

