<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'API Gestion Comptes Bancaires',
                'version' => '1.0.0',
                'openapi' => '3.0.0',
            ],
            'routes' => [
                'api' => 'docs',
            ],
            'paths' => [
                'docs_json' => 'api.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',
                'annotations' => [
                    base_path('app'),
                ],
            ],
            'servers' => [
                [
                    'url' => env('APP_URL') . '/api/v1',
                    'description' => 'Production server',
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
        ],
        'group_options' => [],
    ],
];