<?php

return [
    'default_translation' => 'WEB',

    'book_aliases' => [
        'psalm' => 'Psalms',
    ],

    'translations' => [
        'WEB' => [
            'name' => 'World English Bible',
            'edition' => 'Classic',
            'data_file' => resource_path('data/bible/web.json'),
            'source_url' => 'https://ebible.org/bible/details.php?id=eng-web',
            'copyright_url' => 'https://ebible.org/engwebp/copyright.htm',
            'license' => 'Public Domain',
            'availability' => 'free',
        ],
        'KJV' => [
            'name' => 'King James Version',
            'edition' => 'Standardized 1769 text',
            'data_file' => resource_path('data/bible/kjv.json'),
            'source_url' => 'https://ebible.org/bible/details.php?id=eng-kjv2006',
            'copyright_url' => 'https://ebible.org/eng-kjv2006/copyright.htm',
            'license' => 'Public Domain outside the United Kingdom; UK Crown rights apply',
            'availability' => 'free',
        ],
        'ASV' => [
            'name' => 'American Standard Version',
            'edition' => '1901',
            'data_file' => resource_path('data/bible/asv.json'),
            'source_url' => 'https://ebible.org/find/details.php?id=eng-asv',
            'copyright_url' => 'https://ebible.org/eng-asv/copyright.htm',
            'license' => 'Public Domain',
            'availability' => 'free',
        ],
    ],

    // Future licensed translations belong here only after their provider license is approved.
    'licensed_translations' => [],
];
