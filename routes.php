<?php

return [
    '/module/ikosoft/register-:theme_id' => [
        'use' => 'FrontImportController@register',
        'name' => 'ikosoft.registration.register',
        'ajax' => true,
        'arguments' => ['theme_id' => '[0-9]*'],
        'method' => 'POST',
    ],
    '/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true
    ],

];