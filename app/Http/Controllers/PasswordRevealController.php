<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordRevealController extends Controller
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        if (blank($user->password_ciphertext)) {
            return $this->response(['message' => 'This older password cannot be shown. Set a new password first.'], 409);
        }

        try {
            $password = Crypt::decryptString($user->password_ciphertext);
        } catch (DecryptException $exception) {
            return $this->response(['message' => 'This password cannot be decrypted. Set a new password to restore access.'], 503);
        }

        if (!Hash::check($password, $user->password)) {
            return $this->response(['message' => 'This saved password is out of date. Set a new password first.'], 409);
        }

        Log::notice('Administrator revealed a staff password.', [
            'administrator_id' => $request->user()->id,
            'staff_id' => $user->id,
        ]);

        return $this->response(['password' => $password]);
    }

    private function response(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
