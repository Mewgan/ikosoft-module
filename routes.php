<?php

return [

    /* Front */

    '/module/ikosoft/themes'	=> [
        'use' => 'FrontIkosoftController@theme',
        'name' => 'ikosoft.registration.theme',
        'template' => '/Registration/theme'
    ],

    '/module/ikosoft/inscription/theme-:theme_id' => [
        'use' => 'FrontIkosoftController@registration',
        'name' => 'ikosoft.registration.index',
        'arguments' => ['theme_id' => '[0-9]*'],
        'template' => '/Registration/index',
    ],

    '/module/ikosoft/register-:theme_id' => [
        'use' => 'FrontIkosoftController@register',
        'name' => 'ikosoft.registration.register',
        'ajax' => true,
        'arguments' => ['theme_id' => '[0-9]*'],
        'method' => 'POST',
    ],

    /* Api */

    '/module/ikosoft/check/:uid' => [
        'use' => 'ApiIkosoftController@check',
    ],

    '/module/ikosoft/cron' => [
        'use' => 'ApiIkosoftController@update',
        'name' => 'api.ikosoft.update'
    ],

    /* Admin */

    /* prod */
    '{subdomain}.{host}/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true,
        'subdomain' => 'admin'
    ],

    /* dev */
    '/admin/module/ikosoft/*' => [
        'use' => 'AdminIkosoftController@{method}',
        'ajax' => true
    ],

];