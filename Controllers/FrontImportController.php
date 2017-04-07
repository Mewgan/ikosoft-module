<?php

namespace Jet\Modules\Ikosoft\Controllers;

use JetFire\Framework\System\Controller;
use JetFire\Framework\System\Request;

/**
 * Class FrontImportController
 * @package Jet\Modules\Ikosoft\Controllers
 */
class FrontImportController extends Controller
{

    public function registration(Request $request)
    {
        $template = ROOT . '/src/Modules/Ikosoft/Views/Registration/index.html.twig';
        if ($request->has('uid')) {
            $data['uid'] = $request->get('uid');
            $data['path'] = $this->findInstancePath($this->app->data['app']['imports']['ikosoft']['path'], $data['uid']);
            if (!is_null($data['path'])) {
                return compact('template', 'data');
            }
        }
        return null;
    }

    private function findInstancePath($path, $uid)
    {
        $path = rtrim($path, '/') . '/';
        $files = glob_recursive($path . '*.zip', GLOB_BRACE);
        foreach ($files as $file) {
            $instance = pathinfo($file);
            if ($instance['filename'] == $uid) return $file;
        }
        return null;
    }
}