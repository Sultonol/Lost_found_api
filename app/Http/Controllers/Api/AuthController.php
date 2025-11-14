<?php

// PASTIKAN NAMESPACE-NYA BENAR
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Verified;

class AuthController extends Controller
{
    /**
     * Menangani registrasi user baru.
     */
    public function register(Request $request)
    {
        // KODE VALIDASI LENGKAP
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', 'string', Rule::in(['mahasiswa', 'admin'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Pastikan 422
        }

        // KODE LENGKAP UNTUK CEK ADMIN
        if ($request->role === 'admin') {
            if (User::where('role', 'admin')->exists()) {
                return response()->json([
                    'message' => 'Registrasi Gagal: Role admin sudah terdaftar dan hanya boleh ada satu.'
                ], 409);
            }
        }

        // KODE LENGKAP UNTUK MEMBUAT USER
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // KODE LENGKAP UNTUK MENGIRIM EMAIL
        event(new Registered($user));

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email Anda untuk verifikasi.',
            'user' => $user
        ], 201);
    }

    /**
     * Menangani login user.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Kredensial tidak valid'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Belum Terverifikasi. Silahkan cek inbox Anda'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Menangani logout user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout Berhasil']);
    }

    /**
     * Menangani verifikasi email.
     */
    public function verifyEmail(Request $request)
    {
        $user = User::find($request->route('id'));

        if (! $user) {
             return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
             return response()->json(['message' => 'Link verifikasi tidak valid.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi sebelumnya.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email berhasil diverifikasi! Anda sekarang bisa login.']);
    }
}
