<?php

return [
    '/module/ikosoft/registration' => [
        'use' => 'Registration/index'
    ],
    /* in dev */
    '/admin/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true
    ],
    /* in prod */
    '{subdomain}.{host}/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true,
        'subdomain' => 'admin'
    ],
];