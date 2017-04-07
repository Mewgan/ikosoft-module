<?php

namespace Jet\Modules\Ikosoft\Controllers;

use Jet\Models\Media;
use Jet\Models\Module;
use Jet\Models\Status;
use Jet\Modules\Post\Models\PostCategory;
use JetFire\Db\Model;
use JetFire\Framework\App;
use JetFire\Framework\Providers\DbProvider;
use JetFire\Framework\System\Controller;
use ZipArchive;

/**
 * Class ImportController
 * @package Jet\Modules\Ikosoft\Controllers
 */
class ImportController extends Controller
{

    /**
     * @var \PDO
     */
    public $pdo;
    /**
     * @var object
     */
    public $db;
    /**
     * @var array
     */
    public $global_data = [];
    /**
     * @var array
     */
    public $data = [];
    /**
     * @var array
     */
    public $entries = [];
    /**
     * @var array
     */
    public $params = [
        'action' => 'create',
        'activate' => 0,
        'zip_password' => 'XfdghT@15_BrP4',
        'automatic_update' => true,
        'theme' => null
    ];
    /**
     * @var array
     */
    private $callback = [];

    /**
     * ImportController constructor.
     * @param App $app
     * @param DbProvider $dbProvider
     */
    public function __construct(App $app, DbProvider $dbProvider)
    {
        parent::__construct($app);
        $this->pdo = Model::orm('pdo')->getOrm();
        $this->pdo->exec('SET NAMES utf8');
        $this->db = $dbProvider->getParams('default');
    }

    /**
     * @param array $params
     */
    public function setParams($params = [])
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     *
     */
    public function initCallback()
    {
        $this->callback = [
            'SalonInformation' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadWebsite@load'
            ],
            'TimeTable' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadSchedule@load',
                'depend' => 'SalonInformation'
            ],
            'WithAppointment' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadBookingLink@load',
                'depend' => 'SalonInformation'
            ],
            'Suppliers' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadSupplier@load',
                'depend' => 'SalonInformation'
            ],
            'Pictures' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadMedia@load',
                'depend' => 'SalonInformation'
            ],
            'Employees' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadTeam@load',
                'depend' => 'Pictures'
            ],
            'ServicesFamilies' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadServiceCategory@load',
                'depend' => 'SalonInformation'
            ],
            'Services' => [
                'call' => 'Jet\Modules\Ikosoft\Import\LoadService@load',
                'depend' => 'ServicesFamilies'
            ]
        ];
    }

    /**
     *
     */
    public function loadGlobalData()
    {
        $media = Media::select('id')->where('path', '/public/media/default/user-photo.png')->get(true);
        if (!is_null($media)) $this->global_data['account_photo'] = $media['id'];
        $status = Status::select('id')->where('role', 'user')->get(true);
        if (!is_null($status)) $this->global_data['account_status'] = $status['id'];
        $modules = Module::select('id', 'slug')->get();
        $this->global_data['modules'] = [];
        foreach ($modules as $module) $this->global_data['modules'][$module['slug']] = $module['id'];

        $supplier_category = PostCategory::select('id')->where('slug', 'partenaire')->get(true);
        if (!is_null($supplier_category)) $this->global_data['supplier_category'] = $supplier_category['id'];
    }

    /**
     * @param $file
     * @return array|bool|mixed
     */
    public function load($file)
    {
        if (is_file($file) && substr($file, -4) === ".zip") {
            $dir = ((substr($file, 0, 1) === '/') ? dirname($file) : ROOT . '/' . dirname($file)) . '/';
            $instance = pathinfo($file);
            $response = $this->extractZip($file, $dir . $instance['filename']);
            if ($response === true) {
                if (is_dir($dir . $instance['filename'])) {
                    if (is_file($xml = ($dir . $instance['filename'] . '/' . $instance['filename'] . '.xini'))) {
                        try {
                            $this->initCallback();
                            $this->entries = new \SimpleXMLElement(file_get_contents($xml));
                            $this->pdo->beginTransaction();

                            $this->data = ['instance' => $instance['filename'], 'instance_path' => $dir . $instance['filename'] . '/'];

                            $this->params['action'] = ($this->instanceInDb($instance['filename'])) ? 'update' : 'create';
                            if (!$this->params['automatic_update']) return false;

                            $this->recursiveCall($this->entries);

                            if (isset($this->data['website_id'])) {
                                if (isset($this->data['website']['data']))
                                    $this->updateWebsiteData($this->data['website_id'], $this->data['website']['data']);
                                $this->createOrUpdateImport($this->data['website_id'], $instance['filename']);
                            }
                            $this->pdo->commit();

                        } catch (\Exception $e) {
                            $this->pdo->rollBack();
                            return ['status' => 'error', 'message' => $instance['filename'] . ' => ' . $e->getMessage()];
                        }

                        return $this->params['action'];
                    }
                    return ['status' => 'error', 'message' => 'Impossible de trouver le fichier d\'import : "' . $instance['filename'] . '.xini"'];
                }
                return ['status' => 'error', 'message' => 'Impossible de trouver le dossier de l\'instance : "' . $dir . $instance['filename'] . '"'];
            }
            return $response;
        }
        return ['status' => 'error', 'message' => 'Impossible de trouver l\'archive zip : "' . $file . '"'];
    }

    /**
     * @param $entries
     */
    public function recursiveCall($entries)
    {
        foreach ($entries->s as $service) {
            $this->callCallback($service, $entries);
            if ($service->s) $this->recursiveCall($service);
        }
    }

    /**
     * @param $entry
     * @param $entries
     * @throws \Exception
     */
    public function callCallback($entry, $entries = null)
    {
        $key = (string)$entry['n'];
        $entries = is_null($entries) ? $this->entries : $entries;
        if (isset($this->callback[$key]['call'])) {

            if (isset($this->params['instance_data']) && isset($this->params['instance_data'][$key]) && (string)$this->params['instance_data'][$key] == '0') return;

            if (isset($this->callback[$key]['depend']) && !empty($this->callback[$key]['depend'])) {
                $e = $this->findEntry($entries, $this->callback[$key]['depend']);
                if (!is_null($e)) $this->callCallback($e, $entries);
            }
            $callback = explode('@', $this->callback[$key]['call']);
            unset($this->callback[$key]);
            if (!isset($callback[1]))
                throw new \Exception('Impossible de trouver le callback : ' . $this->callback[$key]);
            $this->callMethod($callback[0], $callback[1], ['entry' => $entry], ['import' => $this]);
        }
    }

    /**
     * @param $entries
     * @param $key
     * @return null
     */
    private function findEntry($entries, $key)
    {
        foreach ($entries->s as $service) {
            if ((string)$service['n'] == $key)
                return $service;
        }
        return null;
    }

    /**
     * @param $uid
     * @return bool
     */
    private function instanceInDb($uid)
    {
        $req = $this->pdo->prepare('SELECT * FROM ' . $this->db['prefix'] . 'ikosoft_imports i WHERE i.uid = :uid');
        $req->execute(['uid' => $uid]);
        $import = $req->fetch();
        if ($import !== false) {
            $this->params['automatic_update'] = (isset($import['to_update']) && ($import['to_update'] == true || $import['to_update'] == 1));
            $this->params['instance_data'] = json_decode($import['data'], true);
            $this->data['website_id'] = $import['website_id'];
            $this->getWebsites($this->data['website_id']);
        }
        return ($import !== false);
    }

    /**
     * @param $website_id
     * @param $uid
     */
    private function createOrUpdateImport($website_id, $uid)
    {
        $date = new \DateTime();
        $values = [
            'uid' => $uid,
            'website_id' => $website_id,
            'to_update' => 1,
            'created_at' => $date->format("Y-m-d H:i:s"),
            'updated_at' => $date->format("Y-m-d H:i:s"),
            'data' => json_encode([
                'SalonInformation' => 1,
                'TimeTable' => 1,
                'WithAppointment' => 1,
                'Suppliers' => 1,
                'Pictures' => 1,
                'Employees' => 1,
                'ServicesFamilies' => 1,
                'Services' => 1
            ])
        ];
        $keys = array_keys($values);
        if ($this->params['action'] == 'create') {
            $req = $this->pdo->prepare('INSERT INTO ' . $this->db['prefix'] . 'ikosoft_imports (' . implode(',', $keys) . ') VALUES (:' . implode(',:', $keys) . ')');
            $req->execute($values);
        } else {
            $req = $this->pdo->prepare('UPDATE ' . $this->db['prefix'] . 'ikosoft_imports SET `updated_at` = :updated_at WHERE uid = :uid');
            $req->execute(['updated_at' => $values['updated_at'], 'uid' => $values['uid']]);
        }
    }

    /**
     * @param $website_id
     * @param array $data
     */
    private function updateWebsiteData($website_id, $data = [])
    {
        $data = is_array($data) ? json_encode($data) : $data;
        $req = $this->pdo->prepare('UPDATE ' . $this->db['prefix'] . 'websites SET `data` = :data WHERE id = :id');
        $req->execute(['data' => $data, 'id' => $website_id]);
    }

    /**
     * @param $website_id
     */
    public function getWebsites($website_id)
    {
        $this->data['websites'][] = $website_id;
        $req = $this->pdo->prepare('SELECT t.website_id 
          FROM ' . $this->db['prefix'] . 'websites w
          LEFT JOIN ' . $this->db['prefix'] . 'themes t ON t.id = w.theme_id
          WHERE w.id = :website_id'
        );
        $req->execute(['website_id' => $website_id]);
        $res = $req->fetch();
        if ($res !== false && $website_id != $res['website_id']) {
            $this->getWebsites($res['website_id']);
        }
    }

    /**
     * @param $file
     * @param $dir
     * @return array|bool
     */
    private function extractZip($file, $dir)
    {
        exec('unzip -oP ' . $this->params['zip_password'] . ' ' . $file . ' -d ' . $dir);
        return true;
        /*$zip = new ZipArchive();
        if ($zip->open($file) === true) {
            if ($zip->setPassword($this->params['zip_password'])) {
                if (!$zip->extractTo($dir))
                    return ['status' => 'error', 'message' => 'Erreur lors de l\'extraction du fichier : ' . $file];
            }
            $zip->close();
            return true;
        }
        return ['status' => 'error', 'message' => 'Impossible de dézipper le fichier'];*/
    }
}