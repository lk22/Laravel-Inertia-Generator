<?php

return [
    'output_directory' => 'inertia-extended',

    'frameworks' => [
        'react' => [
            'adapter' => '@inertiajs/react',
            'label' => 'React',
            'entry_files' => [
                'resources/js/app.tsx',
                'resources/js/app.jsx',
                'resources/js/app.ts',
                'resources/js/app.js',
            ],
            'paths' => [
                'pages' => [
                    'resources/js/pages',
                    'resources/js/Pages',
                ],
                'components' => [
                    'resources/js/components',
                    'resources/js/Components',
                ],
                'layouts' => [
                    'resources/js/layouts',
                    'resources/js/Layouts',
                ],
            ],
            'extensions' => [
                'page' => 'tsx',
                'component' => 'tsx',
                'layout' => 'tsx',
            ],
            'stub_set' => 'react',
            'content_signatures' => [
                '@inertiajs/react',
                'react-dom/client',
                'import.meta.glob(',
            ],
        ],
        'vue' => [
            'adapter' => '@inertiajs/vue3',
            'label' => 'Vue',
            'entry_files' => [
                'resources/js/app.ts',
                'resources/js/app.js',
            ],
            'paths' => [
                'pages' => [
                    'resources/js/pages',
                    'resources/js/Pages',
                ],
                'components' => [
                    'resources/js/components',
                    'resources/js/Components',
                ],
                'layouts' => [
                    'resources/js/layouts',
                    'resources/js/Layouts',
                ],
            ],
            'extensions' => [
                'page' => 'vue',
                'component' => 'vue',
                'layout' => 'vue',
            ],
            'stub_set' => 'vue',
            'content_signatures' => [
                '@inertiajs/vue3',
                'createApp(',
                '.vue',
            ],
        ],
        'svelte' => [
            'adapter' => '@inertiajs/svelte',
            'label' => 'Svelte',
            'entry_files' => [
                'resources/js/app.ts',
                'resources/js/app.js',
            ],
            'paths' => [
                'pages' => [
                    'resources/js/pages',
                    'resources/js/Pages',
                ],
                'components' => [
                    'resources/js/components',
                    'resources/js/Components',
                ],
                'layouts' => [
                    'resources/js/layouts',
                    'resources/js/Layouts',
                ],
            ],
            'extensions' => [
                'page' => 'svelte',
                'component' => 'svelte',
                'layout' => 'svelte',
            ],
            'stub_set' => 'svelte',
            'content_signatures' => [
                '@inertiajs/svelte',
                '.svelte',
            ],
        ],
    ],
];
