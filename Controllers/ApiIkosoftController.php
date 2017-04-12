<?php

namespace Jet\Modules\Ikosoft\Controllers;

use JetFire\Framework\System\Controller;

/**
 * Class ApiIkosoftController
 * @package Jet\Modules\Ikosoft\Controllers
 */
class ApiIkosoftController extends Controller
{

    /**
     * @return bool
     */
    public function update()
    {
        $path = $this->app->data['setting']['imports']['ikosoft']['path'];
        $date = new \DateTime('-1day');
        $date = $date->format('Y-m-d');
        if (is_dir($folder = (rtrim($path, '/') . '/' . $date))) {
            exec('php jet import:ikosoft:data ' . $folder);
            $delete_date = new \DateTime('-3day');
            $delete_date = $delete_date->format('Y-m-d');
            if (is_dir($folder = (rtrim($path, '/') . '/' . $delete_date))) {
                delTree($folder);
            }
        }
        return true;
    }

}