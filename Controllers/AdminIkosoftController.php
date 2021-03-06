<?php

namespace Jet\Modules\Ikosoft\Controllers;

use Jet\AdminBlock\Controllers\AdminController;
use Jet\Models\ModuleCategory;
use Jet\Modules\Ikosoft\Models\IkosoftImport;
use JetFire\Framework\System\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminIkosoftController extends AdminController
{

    /**
     * @param Request $request
     * @return array
     */
    public function all(Request $request)
    {
        $max = ($request->has('length')) ? (int)$request->query('length') : 10;
        $start = ($request->has('start')) ? (int)$request->query('start') : 1;
        $params = [
            'order' => ($request->has('order')) ? $request->query('order') : [],
            'search' => $request->query('search')['value']
        ];

        $response = IkosoftImport::repo()->listAll($start, $max, $params);
        $websites = [
            'draw' => (int)$request->query('draw'),
            'recordsTotal' => $response['total'],
            'recordsFiltered' => $response['total'],
            'data' => $response['data']
        ];
        return $websites;
    }

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
     * @return array
     */
    public function getPanelSummary()
    {
        $users = IkosoftImport::repo()->countUser();
        $websites = IkosoftImport::repo()->countActiveWebsite();
        $themes = IkosoftImport::repo()->countTheme();
        $modules = ModuleCategory::count();
        return compact('users', 'websites', 'themes', 'modules');
    }


    /**
     * @param Request $request
     * @return array
     */
    public function listBetweenDates(Request $request)
    {
        if ($request->has('start') && $request->has('end')) {
            $months = ['01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'];
            $dates = $labels = [];
            $start = new \DateTime($request->get('start'));
            $end = new \DateTime($request->get('end'));
            $month_interval = $start->diff($end)->m + ($start->diff($end)->y * 12);
            for ($i = 0; $i <= $month_interval; ++$i) {
                $start = new \DateTime($request->get('start'));
                $end = new \DateTime($request->get('start'));
                $start->add(new \DateInterval('P' . $i . 'M'));
                $end->add(new \DateInterval('P' . ($i + 1) . 'M'));
                $labels[] = $months[$start->format('m')] . ' ' . $start->format('Y');
                $dates[] = IkosoftImport::repo()->listBetweenDates($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));
            }
            return compact('dates', 'labels');
        }
        return ['status' => 'error', 'message' => 'Paramètres manquants'];
    }

    /**
     * @param int $max
     * @return mixed
     */
    public function getLast($max = 5)
    {
        return IkosoftImport::repo()->getLast($max);
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

    /**
     * @description export all active website into csv
     */
    public function exportUsers()
    {
        $all = IkosoftImport::repo()->listAll(1, -1, ['active' => true]);
        $response = new StreamedResponse();
        $response->setCallback(function() use($all) {
            $out = fopen('php://output', 'w+');
            $cols = ['id' => 'Identifiant unique', 'society' => 'Société', 'full_name' => 'Client', 'email' => 'E-mail', 'registered_at' => 'Date de création', 'website' => 'Site web', 'expiration_date' => 'Date d\'expiration'];
            fputcsv($out, $cols, ';');
            foreach ($all['data'] as $fields) {
                $fields['registered_at'] = $fields['registered_at']->format('d/m/Y à H:i:s');
                $fields['expiration_date'] = $fields['expiration_date']->format('d/m/Y à H:i:s');
                foreach ($fields as $key => $field) if(!isset($cols[$key])) unset($fields[$key]);
                fputcsv($out, $fields, ';');
            }

            fclose($out);
        });

        $response->setStatusCode(200);
        $date = new \DateTime();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "export{$date->format('dmyHis')}.csv"
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->send();
    }
}