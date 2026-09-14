<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeInternalAdmin extends Command
{
    protected $signature = 'internal:make-admin';

    protected $description = 'Buat atau reset password akun admin untuk area internal (/internal)';

    public function handle(): int
    {
        $name = $this->ask('Nama');
        $email = $this->ask('Email');
        $password = $this->secret('Password (minimal 8 karakter)');
        $passwordConfirmation = $this->secret('Ulangi password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $this->info($existing
            ? "Password untuk {$user->email} berhasil diperbarui."
            : "Akun admin {$user->email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
