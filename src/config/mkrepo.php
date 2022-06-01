<?php

return [
    /**
     * Namespaces
     */
    'namespace' => [
        'contract' => 'Repositories\Contracts',
        'eloquent' => 'Repositories\Eloquent',
    ],

    /**
     * Paths will be used with the `app()->basePath().'/app/'` function to reach app directory.
     */
    'path' => [
        'contract' => 'Repositories/Contracts/',
        'eloquent' => 'Repositories/Eloquent/',
    ],

    /**
     *
     */
    'classname' => [
        'contract' => '{{name}}Repository',
        'eloquent' => 'Eloquent{{name}}Repository',
    ]
];
