<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'saas:create-superadmin
        {--name= : Nome do superadmin}
        {--email= : Email do superadmin}
        {--password= : Senha do superadmin}';

    protected $description = 'Cria o usuário superadmin (Gregório) do SaaS';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Nome do superadmin', 'Gregório');
        $email = $this->option('email') ?? $this->ask('Email do superadmin', 'gregorio@saasmesa.com.br');
        $password = $this->option('password') ?? $this->secret('Senha do superadmin');

        if (User::where('email', $email)->exists()) {
            $this->error('Já existe um usuário com este email.');
            return Command::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'superadmin',
            'tenant_id' => null,
            'is_staff' => true,
        ]);

        $this->info("Superadmin '{$user->name}' criado com sucesso!");
        $this->warn("Email: {$email}");

        return Command::SUCCESS;
    }
}
