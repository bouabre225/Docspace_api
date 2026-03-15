<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Create a new Service class';

    public function handle()
    {
        $name = $this->argument('name');

        $path = app_path("Services/{$name}.php");

        if (File::exists($path)) {
            $this->error("Service already exists!");
            return Command::FAILURE;
        }

        File::ensureDirectoryExists(app_path('Services'));

        $stub = <<<PHP
<?php

namespace App\Services;

class {$name}
{
    public function handle()
    {
        //
    }
}
PHP;

        File::put($path, $stub);

        $this->info("Service {$name} created successfully.");

        return Command::SUCCESS;
    }
}
