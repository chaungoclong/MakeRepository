<?php

namespace Longtnt\MakeRepository\Commands;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeRepository extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:repo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Repository';

    /**
     * Type Command
     *
     * @var string
     */
    protected $type = 'Repository';


    protected function getStub()
    {
        return $this->option('model')
            ? $this->resolveStubPath('/stubs/repository.stub')
            : $this->resolveStubPath('/stubs/repository.plain.stub');
    }

    public function handle()
    {
        (parent::handle() === false) ? 0 : 1;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $model = $this->option('model');

        // If use '--model' -> replace model in stub + try create model
        if ($model) {
            $stub = $this->replaceModel($stub, $model);

            $this->createModel($model);
        }

        // If use '--contract' -> try create contract
        if ($this->option('contract')) {
            $this->createContract($name);
        }

        $stub = $this->replaceContract($stub, $name);

        return $stub;
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
        $subNamespaceSegments = explode('\\', $subNamespace);

        $classBaseName = Str::studly(
            str_replace(
                '{{name}}',
                Str::studly(array_pop($subNamespaceSegments)),
                config(('mkrepo.classname.eloquent'))
            )
        );

        $subPath = implode('/', $subNamespaceSegments);

        return $this->laravel['path']
            . '/'
            . $subPath
            . '/'
            . $classBaseName
            . '.php';
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            '{{namespace}}',
            $this->getNamespace($name),
            $stub
        );

        return $this;
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
        $class = Str::studly(str_replace(
            '{{name}}',
            Str::studly($class),
            config('mkrepo.classname.eloquent')
        ));

        return str_replace('{{class}}', $class, $stub);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        return $this->qualifyModel($model);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $modelClass = $this->parseModel($model);

        $searches = [
            '{{namespacedModel}}' => $modelClass,
            '{{model}}' => class_basename($modelClass),
        ];

        return str_replace(
            array_keys($searches),
            array_values($searches),
            $stub
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function parseContract($name)
    {
        $namespacedContract = str_replace(
            config('mkrepo.namespace.eloquent'),
            config('mkrepo.namespace.contract'),
            $this->qualifyClass($name)
        );

        $namespace = $this->getNamespace($namespacedContract) . '\\';

        $contract = str_replace($namespace, '', $namespacedContract);

        $contract = Str::studly(str_replace(
            '{{name}}',
            Str::studly($contract),
            config('mkrepo.classname.contract')
        ));

        return $namespace . $contract;
    }

    /**
     * Create model when use option '--model'
     *
     * @param [type] $model
     * @return void
     */
    protected function createModel($model)
    {
        $namespacedModel = $this->parseModel($model);

        // If model not exist
        if (!class_exists($namespacedModel)) {
            $this->call(
                'make:model',
                ['name' => $namespacedModel]
            );
        }
    }

    /**
     * Create contract when use option '--contract'
     *
     * @param [type] $model
     * @return void
     */
    protected function createContract($name)
    {
        $namespacedContract = $this->parseContract($name);

        // If contract not exist
        if (!interface_exists($namespacedContract)) {
            $this->call(
                'make:repo-contract',
                ['name' => $this->getNameInput()]
            );
        }
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceContract($stub, $name)
    {
        $contract = $this->parseContract($name);

        $searches = [
            '{{namespacedContract}}' => $contract,
            '{{contract}}' => class_basename($contract),
        ];

        return str_replace(
            array_keys($searches),
            array_values($searches),
            $stub
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\' . config('mkrepo.namespace.eloquent');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel
            ->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'model',
                'm',
                InputOption::VALUE_OPTIONAL,
                'The model that the Repository applies to.'
            ],
            [
                'contract',
                'c',
                InputOption::VALUE_NONE,
                'The contract that the Repository implements.'
            ],
        ];
    }
}
