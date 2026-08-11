<?php

$compiledViewPath = env('VIEW_COMPILED_PATH');

if (! is_string($compiledViewPath) || trim($compiledViewPath) === '') {
    $storageCompiledPath = storage_path('framework/views');
    $resolvedStoragePath = realpath($storageCompiledPath);

    if ($resolvedStoragePath && is_writable($resolvedStoragePath)) {
        $compiledViewPath = $resolvedStoragePath;
    } else {
        $compiledViewPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'alumni-link-views';

        if (! is_dir($compiledViewPath)) {
            @mkdir($compiledViewPath, 0777, true);
        }
    }
}

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => $compiledViewPath,
];
