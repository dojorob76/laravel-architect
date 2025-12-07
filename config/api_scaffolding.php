<?php

return [
    'paths' => [
        'models' => 'app/Models',
        'repositories' => 'app/Repositories',
        'contracts' => 'app/Contracts',
        'services' => 'app/Services',
        'controllers' => 'app/Http/Controllers/Api',
        'requests' => 'app/Http/Requests',
        'resources' => 'app/Http/Resources',
        'migrations' => 'database/migrations',
        'tests' => 'tests/Feature',
        'factories' => 'database/factories',
        'routes' => 'routes/api.php',
    ],
    'namespaces' => [
        'models' => 'App\Models',
        'repositories' => 'App\Repositories',
        'contracts' => 'App\Contracts',
        'services' => 'App\Services',
        'controllers' => 'App\Http\Controllers\Api',
        'base_controller' => 'App\Http\Controllers\Controller',
        'requests' => 'App\Http\Requests',
        'resources' => 'App\Http\Resources',
        'factories' => 'Database\Factories',
    ],
    // Driver configuration for each operation type
    // Options: 'eloquent', 'mysql', 'postgres'
    'drivers' => [
        'crud' => 'eloquent',           // CRUD operations (create, read, update, delete)
        'bulk' => 'eloquent',           // Bulk operations (createMany, updateMany, deleteMany)
        'soft_delete' => 'eloquent',    // Soft delete operations (restore, forceDelete)
        'bulk_soft_delete' => 'eloquent', // Bulk soft delete operations (restoreMany, forceDeleteMany)
    ],
    'response_settings' => [
        'json_key' => 'data',
        'pagination_limit' => 15,
    ],
    'status_codes' => [
        'success' => 200,
        'created' => 201,
        'accepted' => 202,
        'no_content' => 204,
        'bad_request' => 400,
        'unauthorized' => 401,
        'forbidden' => 403,
        'not_found' => 404,
        'unprocessable_entity' => 422,
        'server_error' => 500,
    ],
    'messages' => [
        'created' => 'Resource created successfully.',
        'updated' => 'Resource updated successfully.',
        'deleted' => 'Resource deleted successfully.',
        'restored' => 'Resource restored successfully.',
        'not_found' => 'Resource not found.',
    ],
];
