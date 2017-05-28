<?php

return [

    /* Front */

    '/module/ikosoft/themes'	=> [
        'use' => 'FrontIkosoftController@theme',
        'name' => 'ikosoft.registration.theme',
        'template' => '/Registration/theme'
    ],

    '/module/ikosoft/preview'	=> [
        'use' => 'preview_layout',
        'name' => 'ikosoft.preview',
    ],

    '/module/ikosoft/registration/theme-:theme_id' => [
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

    '/module/ikosoft/check-before-create/:uid' => [
        'use' => 'ApiIkosoftController@checkBeforeCreate',
    ],

    '/module/ikosoft/check-before-update/:uid' => [
        'use' => 'ApiIkosoftController@checkBeforeUpdate',
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