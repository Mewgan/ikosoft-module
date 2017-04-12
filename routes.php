<?php

return [

    '/module/ikosoft/register-:theme_id' => [
        'use' => 'FrontIkosoftController@register',
        'name' => 'ikosoft.registration.register',
        'ajax' => true,
        'arguments' => ['theme_id' => '[0-9]*'],
        'method' => 'POST',
    ],

    '/module/ikosoft/cron' => [
        'use' => 'ApiIkosoftController@update',
        'name' => 'api.ikosoft.update'
    ],

    '/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true
    ],

];