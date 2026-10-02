<?php

return [
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => array_merge(
            [resource_path('js/Pages')],
            glob(base_path('Modules/*/resources/js/Pages'), GLOB_ONLYDIR) ?: []
        ),
        'page_extensions' => ['js', 'jsx', 'svelte', 'ts', 'tsx', 'vue'],
    ],
];
