<?php

namespace Jet\Modules\Ikosoft\Controllers;


use Jet\AdminBlock\Controllers\AdminController;
use Jet\Modules\Ikosoft\Models\IkosoftImport;

class AdminIkosoftController extends AdminController
{
    public function getByWebsite($id)
    {
        $import = IkosoftImport::findOneByWebsite($id);
        return (!is_null($import))
            ? ['status' => 'success', 'resource' => $import]
            : ['status' => 'error', 'message' => 'Impossible de trouver l\'instance'];
    }
}