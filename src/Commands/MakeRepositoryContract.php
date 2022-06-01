<?php

namespace Longtnt\MakeRepository\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;

class MakeRepositoryContract extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:repo-contract';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Interface for Repository class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'RepositoryInterface';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false) {
            return 0;
        }

        return 1;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/repository.contract.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('mkrepo.namespace.contract');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $class = Str::studly(
            str_replace(
                '{{name}}',
                $class,
                config('mkrepo.classname.contract')
            )
        );

        return str_replace('{{contractName}}', $class, $stub);
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $subNamespace = Str::replaceFirst($this->rootNamespace(), '', $name);
        $namespaceSegments = explode('\\', $subNamespace);

        $contractBaseName = Str::studly(str_replace(
            '{{name}}',
            array_pop($namespaceSegments),
            config('mkrepo.classname.contract')
        ));

        $relativePath = implode('/', $namespaceSegments);

        $path = $this->laravel['path']
            . '/'
            . $relativePath
            . '/'
            . $contractBaseName
            . '.php';

        return $path;
    }
}
