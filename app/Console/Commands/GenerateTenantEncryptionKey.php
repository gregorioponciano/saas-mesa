<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateTenantEncryptionKey extends Command
{
    protected $signature = 'tenant:generate-encryption-key
        {--show : Exibir a chave sem copiar para o .env}';

    protected $description = 'Gera uma TENANT_CREDENTIAL_ENCRYPTION_KEY (32 bytes, base64) para o EncryptedCredentialService';

    public function handle(): int
    {
        $key = base64_encode(random_bytes(32));

        if ($this->option('show')) {
            $this->line($key);

            return Command::SUCCESS;
        }

        $envPath = base_path('.env');
        $wrote = false;

        if (file_exists($envPath)) {
            $contents = file_get_contents($envPath);
            $pattern = '/^TENANT_CREDENTIAL_ENCRYPTION_KEY=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, 'TENANT_CREDENTIAL_ENCRYPTION_KEY='.$key, $contents);
            } else {
                $contents .= PHP_EOL.'TENANT_CREDENTIAL_ENCRYPTION_KEY='.$key.PHP_EOL;
            }

            $wrote = file_put_contents($envPath, $contents) !== false;
        }

        if ($wrote) {
            $this->info('Chave gerada e salva em .env como TENANT_CREDENTIAL_ENCRYPTION_KEY.');

            return Command::SUCCESS;
        }

        $this->warn('Não foi possível gravar o .env. Gere a chave com --show e adicione manualmente:');

        return Command::SUCCESS;
    }
}
