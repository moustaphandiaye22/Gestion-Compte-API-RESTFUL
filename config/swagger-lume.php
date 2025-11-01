<?php

return [
    'api' => [
        'title' => 'Gestion Compte API',
        'description' => 'API for managing bank accounts',
        'version' => '1.0.0',
        'termsOfService' => '',
        'contact' => [
            'email' => 'support@example.com',
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
    ],
    'routes' => [
        'api' => '/docs',
        'docs' => '/docs-json',
        'oauth2_callback' => '/ndiaye/oauth2-callback',
        'assets' => '/swagger-ui-assets',
        'middleware' => [
            'api' => [],
            'asset' => [],
            'docs' => [],
            'oauth2_callback' => [],
        ],
    ],
    'paths' => [
        'docs' => storage_path('api-docs'),
        'docs_json' => 'api-docs.json',
        'annotations' => [
            base_path('app'),
        ],
        'excludes' => [],
        'base' => null,
        'views' => base_path('resources/views/vendor/swagger-lume'),
    ],
    // Default security schemes used in generated OpenAPI docs.
    // Use a Bearer (Authorization header) scheme for Passport (API tokens).
    'security' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'Bearer',
            'description' => 'Enter your access token as: Bearer {token}',
        ],
    ],
    'generate_always' => true,
    'swagger_version' => '3.0',
    'proxy' => false,
    'additional_config_url' => null,
    'operations_sort' => null,
    'validator_url' => null,
    'constants' => [
        'SWAGGER_LUME_CONST_HOST' => env('SWAGGER_LUME_CONST_HOST', 'http://my-default-host.com'),
    ],
    'force_https' => false,
];