<?php

return [

    'app' => [
        'Ikosoft' => [
            'order' => 100,
            'hook' => [
                'left_sidebar' => true
            ],
            'routes' => [
                [
                    'title' => 'Ikosoft',
                    'name'=> 'module:ikosoft',
                ]
            ],
            'trial_days' => '+1month'
        ],
        'blocks' => [
            'IkosoftModule' => [
                'path' => 'src/Modules/Ikosoft/',
                'namespace' => '\\Jet\\Modules\\Ikosoft',
                'view_dir' => 'src/Modules/Ikosoft/Views/',
            ],
        ],
        'fixtures' => [
            'src/Modules/Ikosoft/Fixtures/'
        ]
    ]
];