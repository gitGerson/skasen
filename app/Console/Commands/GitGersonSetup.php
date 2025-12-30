<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class GitGersonSetup extends Command
{
    protected $signature = 'gitGerson:setup
        {--db=mysql : Database driver: sqlite|mysql (default: mysql)}
        {--panel=admin : Filament panel id}
        {--force : Do not ask interactive questions}
        {--name=user : Default user name}
        {--email=user@gitGerson.com : Default user email}
        {--password=password : Default user password (PLEASE change in production)}
        {--migrate : Run migrations (default: yes)}
        {--no-migrate : Do not run migrations}';

    protected $description = 'Run gitGerson Filament Starter Kit setup (DB + migrations + default user + Shield)';

    public function handle(): int
    {
        $this->info('🚀 gitGerson setup');

        // Ensure .env exists
        if (! File::exists(base_path('.env')) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->line('✅ Created .env from .env.example');
        }

        // Ensure app key exists
        if (empty(config('app.key'))) {
            $this->call('key:generate');
        }

        $force = (bool) $this->option('force');
        $db = strtolower((string) $this->option('db'));
        $panel = (string) $this->option('panel');

        if (! $force) {
            $db = $this->choice('Which database do you want to use?', ['mysql', 'sqlite'], $db === 'sqlite' ? 1 : 0);
        }

        if ($db === 'mysql') {
            $this->setupMysql($force);
        } elseif ($db === 'sqlite') {
            $this->setupSqlite();
        } else {
            $this->error("Unsupported DB driver: {$db}. Use sqlite or mysql.");
            return self::FAILURE;
        }

        // Migrations (default yes)
        $runMigrate = $this->option('migrate')
            ? true
            : (! $this->option('no-migrate'));

        if (! $force && ! $this->option('migrate') && ! $this->option('no-migrate')) {
            $runMigrate = $this->confirm('Run migrations now?', true);
        }

        if ($runMigrate) {
            $this->call('migrate', ['--force' => true]);
        }

        // Create/update default user
        $name = (string) $this->option('name');
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            $this->line("✅ Created default user: {$email} (id: {$user->id})");
        } else {
            // Keep it deterministic: ensure it matches the defaults
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($password),
            ])->save();

            $this->line("✅ Updated existing user: {$email} (id: {$user->id})");
        }

        // Shield: assign super admin + generate permissions
        $this->call('shield:super-admin', [
            '--user' => (int) $user->id,
            '--panel' => $panel,
        ]);

        $this->call('shield:generate', [
            '--all' => true,
            '--ignore-existing-policies' => true,
            '--panel' => $panel,
        ]);

        $this->newLine();
        $this->info('✅ Setup complete!');
        $this->line("Panel: {$panel}");
        $this->line("Login: {$email}");
        $this->line("Password: {$password}  ⚠️ change this in real deployments");

        return self::SUCCESS;
    }

    private function setupSqlite(): void
    {
        $dbPath = database_path('database.sqlite');

        if (! File::exists($dbPath)) {
            File::ensureDirectoryExists(database_path());
            File::put($dbPath, '');
            $this->line("✅ Created SQLite database: {$dbPath}");
        }

        $this->setEnvValues([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $dbPath,
            'DB_HOST' => null,
            'DB_PORT' => null,
            'DB_USERNAME' => null,
            'DB_PASSWORD' => null,
        ]);

        $this->line('✅ Configured .env for SQLite');
        Artisan::call('config:clear');
    }

    private function setupMysql(bool $force): void
    {
        $this->line('ℹ️ Configuring MySQL in .env');

        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $database = env('DB_DATABASE', 'laravel');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');

        if (! $force) {
            $host = $this->ask('DB_HOST', $host);
            $port = $this->ask('DB_PORT', $port);
            $database = $this->ask('DB_DATABASE', $database);
            $username = $this->ask('DB_USERNAME', $username);
            $password = $this->secret('DB_PASSWORD (leave blank for none)') ?? $password;
        }

        $this->setEnvValues([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        $this->line('✅ Configured .env for MySQL');
        Artisan::call('config:clear');
    }

    private function setEnvValues(array $values): void
    {
        $envFile = base_path('.env');
        $contents = File::exists($envFile) ? File::get($envFile) : '';

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*$/m";

            if ($value === null) {
                $contents = preg_replace($pattern, '', $contents);
                continue;
            }

            $escaped = $this->escapeEnvValue((string) $value);

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, "{$key}={$escaped}", $contents);
            } else {
                $contents .= (str_ends_with($contents, "\n") || $contents === '' ? '' : "\n") . "{$key}={$escaped}\n";
            }
        }

        $contents = preg_replace("/\n{3,}/", "\n\n", $contents);
        File::put($envFile, $contents);
    }

    private function escapeEnvValue(string $value): string
    {
        if (preg_match('/\s|#|=|"/', $value)) {
            $value = str_replace('"', '\"', $value);
            return "\"{$value}\"";
        }

        return $value;
    }
}
