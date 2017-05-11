<?php

namespace Jet\Modules\Ikosoft\Controllers;

use Jet\Modules\Ikosoft\Models\IkosoftImport;
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

    /**
     * @param $uid
     * @return string
     */
    public function checkBeforeCreate($uid)
    {
        return (IkosoftImport::where('uid', $uid)->count() > 0)
            ? json_encode(true)
            : json_encode(false);
    }

    /**
     * @param $uid
     * @return string
     */
    public function checkBeforeUpdate($uid)
    {
        $import = IkosoftImport::getWebsiteByUid($uid);
        if(!is_null($import) && isset($import['website']['society']['account'])){
            if($import['website']['state'] == 1 && $import['website']['society']['account']['state'] == 1){
                if($import['website']['society']['account']['expiration_date'] > new \DateTime())
                    return json_encode(true);
            }
        }
        return json_encode(false);
    }

}