<?php

namespace Jet\Modules\Ikosoft\Controllers;


use Jet\AdminBlock\Controllers\AdminController;
use Jet\Modules\Ikosoft\Models\IkosoftImport;
use JetFire\Framework\System\Request;

class AdminIkosoftController extends AdminController
{
    /**
     * @param $id
     * @return array
     */
    public function getByWebsite($id)
    {
        $import = IkosoftImport::findOneByWebsite($id);
        return (!is_null($import))
            ? ['status' => 'success', 'resource' => $import]
            : ['status' => 'error', 'message' => 'Impossible de trouver l\'instance'];
    }

    /**
     * @param $id
     * @param $state
     * @return array
     */
    public function updateState($id, $state)
    {
        $state = ((int)$state == 1 || $state == 'true') ? 1 : 0;
        /** @var IkosoftImport $import */
        $import = IkosoftImport::findOneById($id);
        if (!is_null($import)) {
            $import->setToUpdate($state);
            return (IkosoftImport::watchAndSave($import))
                ? ['status' => 'success', 'message' => 'Les informations sur l\'import des données à bien été mis à jour']
                : ['status' => 'error', 'message' => 'Erreur lors de la mise à jour'];
        }
        return ['status' => 'error', 'message' => 'Impossible de trouver l\'instance'];
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     */
    public function update(Request $request, $id)
    {
        if ($request->method() == 'PUT' && $request->has('data')) {
            $data = $request->get('data');
            /** @var IkosoftImport $import */
            $import = IkosoftImport::findOneById($id);
            if (!is_null($import) && is_array($data)) {
                $import->setData($data);
                return (IkosoftImport::watchAndSave($import))
                    ? ['status' => 'success', 'message' => 'Les informations sur l\'import des données à bien été mis à jour']
                    : ['status' => 'error', 'message' => 'Erreur lors de la mise à jour'];
            }
            return ['status' => 'error', 'message' => 'Impossible de trouver l\'instance'];
        }
        return ['status' => 'error', 'message' => 'Requête non autorisée'];
    }
}