<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'BeyondChat Articles API',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /api/articles' => 'List all articles',
            'GET /api/articles/latest' => 'Get latest article',
            'GET /api/articles/{id}' => 'Get article by ID',
            'POST /api/articles' => 'Create article',
            'PUT /api/articles/{id}' => 'Update article',
            'DELETE /api/articles/{id}' => 'Delete article',
        ]
    ]);
});

