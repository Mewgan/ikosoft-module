<?php

return [

    'app' => [
        'blocks' => [
            'IkosoftModule' => [
                'path' => 'src/Modules/Ikosoft/',
                'namespace' => '\\Jet\\Modules\\Ikosoft',
                'view_dir' => 'src/Modules/Ikosoft/Views/',
                'prefix' => 'admin',
            ],
        ],
        'fixtures' => [
            'src/Modules/Ikosoft/Fixtures/'
        ]
    ]
];