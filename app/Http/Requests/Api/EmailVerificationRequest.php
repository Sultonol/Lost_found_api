<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

/**
 * @property-read string $hash
 * @property-read int $id
 */
class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Di sinilah kita mengubah aturan bawaan Laravel.
     * Kita tidak lagi memeriksa Auth::user(), tetapi langsung memvalidasi
     * ID pengguna dari URL dengan tanda tangan (signature) yang valid.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Ambil ID user dari parameter rute, contoh: /email/verify/{id}/{hash}
        // Peringatan Intelephense akan hilang dengan PHPDoc di atas
        $userId = $this->route('id');

        // Ambil user dari database berdasarkan ID tersebut.
        $user = \App\Models\User::find($userId);

        // Jika user tidak ditemukan, maka otorisasi gagal.
        if (!$user) {
            return false;
        }

        // Cek apakah hash di URL cocok dengan hash yang seharusnya untuk user ini.
        // Ini adalah pemeriksaan keamanan untuk memastikan link tidak dimanipulasi.
        if (! hash_equals((string) $this->route('hash'), sha1($user->getEmailForVerification()))) {
            return false;
        }

        // Jika semua pemeriksaan lolos, berikan izin untuk melanjutkan.
        return true;
    }
}

