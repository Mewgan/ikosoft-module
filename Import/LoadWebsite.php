<?php

namespace Jet\Modules\Ikosoft\Import;

use Cocur\Slugify\Slugify;

/**
 * Class LoadWebsite
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadWebsite extends LoadFixture
{

    /**
     * @param $entry
     * @return array|bool
     */
    public function load($entry)
    {
        $account = $address = $society = $entries = [];

        foreach ($entry->e as $e) {
            $this->getAccountData($e, $account);
            $this->getAddressData($e, $address);
            $this->getSocietyData($e, $society);
            $entries[(string)$e['n']] = (string)$e['v'];
        }

        $data = ($this->import->params['action'] == 'update') ? $this->getInstanceData($entries['Guid']) : [];
        if (!empty($data) && $data === false) return ['status' => 'error', 'message' => $entries['Guid'] . ' => Impossible de  récupérer les données'];
        if (!empty($data)) {
            $account['id'] = $data['a_id'];
            $society['id'] = $data['s_id'];
            $address['id'] = $data['ad_id'];
        }

        /*$response = $this->isDuplicate($entries, $account, $society);
        if (is_array($response)) return $response;*/

        $slug = new Slugify();
        $data['domain'] = $slug->slugify($society['name']);

        if($this->import->getApp()->data['setting']['sub_domain'] == true){
            $request = $this->import->getApp()->get('request');
            $data['domain'] = ($request->has('REQUEST_SCHEME') ? $request->get('REQUEST_SCHEME') : 'http') . '://' . $data['domain'] . '.' . $request->get('SERVER_NAME');
        }

        $this->loadAccountData($account);
        $this->loadSocietyData($society);
        $this->loadAddressData($address);
        $this->loadWebsiteData($data);

        if ($this->import->params['action'] == 'create') {
            $this->import->getWebsites($this->import->data['website_id']);
        }
        $this->import->global_data['information'] = $entries;

        if (isset($entries['WithAppointment']) && (string)$entries['WithAppointment'] == '1') {
            $response = $this->import->callCallback(['n' => 'WithAppointment']);
            if (is_array($response)) return $response;
        }

        return true;
    }

    /**
     * @param $uid
     * @return mixed
     */
    private function getInstanceData($uid)
    {
        $prefix = $this->import->db['prefix'];
        $req = $this->import->pdo->prepare('SELECT a.id as a_id, s.id as s_id, ad.id as ad_id,
            w.id as w_id, t.id as t_id, t.name as t_name, w.modules as w_modules, w.data as w_data
            FROM ' . $prefix . 'websites w 
            LEFT JOIN ' . $prefix . 'ikosoft_imports i ON w.id = i.website_id
            LEFT JOIN ' . $prefix . 'societies s ON s.id = w.society_id
            LEFT JOIN ' . $prefix . 'themes t ON t.id = w.theme_id
            LEFT JOIN ' . $prefix . 'accounts a ON a.id = s.account_id
            LEFT JOIN ' . $prefix . 'addresses ad ON ad.society_id = s.id
            WHERE i.uid = :uid'
        );
        $req->execute(['uid' => $uid]);
        return $req->fetch();
    }

    /**
     * @param $entry
     * @param $account
     */
    private function getAccountData($entry, &$account)
    {
        switch ((string)$entry['n']) {
            case 'Email':
                $account['email'] = (string)$entry['v'];
                break;
            case 'PhoneNumber':
                $account['phone'] = (string)$entry['v'];
                break;
        }
    }

    /**
     * @param $entry
     * @param $address
     */
    private function getAddressData($entry, &$address)
    {
        switch ((string)$entry['n']) {
            case 'Adress':
                $address['address'] = (string)$entry['v'];
                break;
            case 'City':
                $address['city'] = (string)$entry['v'];
                break;
            case 'ZipCode':
                $address['postal_code'] = (string)$entry['v'];
                break;
            case 'CountryCode':
                $address['country'] = 'FRANCE';
                break;
            case 'LocationX':
                $address['latitude'] = (double)$entry['v'];
                break;
            case 'LocationY':
                $address['longitude'] = (double)$entry['v'];
                break;
        }
    }

    /**
     * @param $entry
     * @param $society
     */
    private function getSocietyData($entry, &$society)
    {
        switch ((string)$entry['n']) {
            case 'Name':
                $society['name'] = (string)$entry['v'];
                break;
            case 'Email':
                $society['email'] = (string)$entry['v'];
                break;
            case 'PhoneNumber':
                $society['phone'] = (string)$entry['v'];
                break;
        }
    }

    /**
     * @param $entries
     * @param $account
     * @param $society
     * @return array|bool
     */
    /*  private function isDuplicate($entries, $account, $society)
      {
          $sql = '';
          if ($this->import->params['action'] == 'update' && isset($account['id'])) $sql = ' AND id <> ' . $account['id'];
          $req = $this->import->pdo->prepare('SELECT COUNT(*) FROM ' . $this->import->db['prefix'] . 'accounts i WHERE i.email = :account_email' . $sql);
          $req->execute(['account_email' => $entries['Email']]);
          if ($this->import->params['action'] == 'create' && $req->fetchColumn() > 0)
              return ['status' => 'error', 'message' => $entries['Guid'] . ' => Douplon de l\'e-mail : ' . $entries['Email']];
          $sql = '';
          if ($this->import->params['action'] == 'update' && isset($society['id'])) $sql = ' AND id <> ' . $society['id'];
          $req = $this->import->pdo->prepare('SELECT COUNT(*) FROM ' . $this->import->db['prefix'] . 'societies i WHERE i.name = :society_name' . $sql);
          $req->execute(['society_name' => $entries['Name']]);
          return ($this->import->params['action'] == 'create' && $req->fetchColumn() > 0)
              ? ['status' => 'error', 'message' => $entries['Guid'] . ' => Douplon du nom de salon : ' . $entries['Name']]
              : true;
      }*/

    /**
     * @param array $account
     * @throws \Exception
     */
    private function loadAccountData($account = [])
    {
        if (!isset($account['email']) || empty($account['email']))
            throw new \Exception($this->import->data['instance'] . ' => L\'e-mail est vide');
        $date = new \DateTime();
        $account['first_name'] = 'Compte';
        $account['last_name'] = 'Utilisateur';
        $account['status_id'] = $this->import->global_data['account_status'];
        $account['updated_at'] = $date->format('Y-m-d H:i:s');

        if ($this->import->params['action'] == 'create') {
            $account['registered_at'] = $account['updated_at'];
            $account['state'] = $this->import->params['activate'];
            $account['photo_id'] = $this->import->global_data['account_photo'];
            if ($account['state'] == 1) {
                $date = new \DateTime('+4 weeks');
                $account['expiration_date'] = $date->format('Y-m-d H:i:s');
            }
        }

        $keys = array_keys($account);
        if ($this->import->params['action'] == 'update' && isset($account['id'])) {
            $sql = '';
            foreach ($keys as $key) $sql .= '`' . $key . '` = :' . $key . ',';
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'accounts SET ' . $sql . ' WHERE id = :id');
        } else {
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'accounts (' . implode(",", $keys) . ') VALUES (:' . implode(",:", $keys) . ')');
        }
        $req->execute($account);

        $this->import->data['account_id'] = isset($account['id']) ? $account['id'] : $this->import->pdo->lastInsertId();
    }

    /**
     * @param array $society
     * @throws \Exception
     */
    private function loadSocietyData($society = [])
    {
        if (!isset($society['name']) || empty($society['name']))
            throw new \Exception($this->import->data['instance'] . ' => Le nom de la société est vide');

        $date = new \DateTime();
        $society['account_id'] = $this->import->data['account_id'];
        $society['updated_at'] = $date->format('Y-m-d H:i:s');
        if ($this->import->params['action'] == 'create') {
            $society['created_at'] = $society['updated_at'];
        }

        $keys = array_keys($society);
        if ($this->import->params['action'] == 'update' && isset($society['id'])) {
            $sql = '';
            foreach ($keys as $key) $sql .= '`' . $key . '` = :' . $key . ',';
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'societies SET ' . $sql . ' WHERE id = :id');
        } else {
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'societies(' . implode(",", $keys) . ') VALUES (:' . implode(",:", $keys) . ')');
        }
        $req->execute($society);

        $this->import->data['society_id'] = isset($society['id']) ? $society['id'] : $this->import->pdo->lastInsertId();
    }

    /**
     * @param array $address
     */
    private function loadAddressData($address = [])
    {
        $address['society_id'] = $this->import->data['society_id'];
        $keys = array_keys($address);
        if ($this->import->params['action'] == 'update' && isset($address['id'])) {
            $sql = '';
            foreach ($keys as $key) $sql .= '`' . $key . '` = :' . $key . ',';
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'addresses SET ' . $sql . ' WHERE id = :id');
        } else {
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'addresses (' . implode(",", $keys) . ') VALUES (:' . implode(",:", $keys) . ')');
        }
        $req->execute($address);
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    private function loadWebsiteData($data = [])
    {
        $date = new \DateTime();
        $website = [];
        if ($this->import->params['action'] == 'update') {

            $this->import->data['website_modules'] = $data['w_modules'];
            $this->import->data['theme'] = ['t_id' => $data['t_id'], 't_name' => $data['t_name']];

            $website = [
                'id' => $data['w_id'],
                'society_id' => $data['s_id'],
                'theme_id' => $data['t_id'],
                'data' => $data['w_data'],
                'updated_at' => $date->format('Y-m-d H:i:s'),
            ];

        } else {

            $sql = 'SELECT w.id as w_id, t.id as t_id, w.layout_id as l_id, 
                w.modules as w_modules, w.render_system as w_render_system, w.data as w_data, t.name as t_name 
                FROM ' . $this->import->db['prefix'] . 'websites w 
                INNER JOIN ' . $this->import->db['prefix'] . 'themes t ON w.id = t.website_id
            ';

            if (!is_null($this->import->params['theme'])) {
                $sql .= ' AND t.id = ' . $this->import->params['theme'];
            }

            $res = $this->import->pdo->query($sql);
            $themes = $res->fetchAll();
            if (isset($themes[0])) {

                $theme = (is_null($this->import->params['theme']))
                    ? $themes[rand(0, count($themes) - 1)] : $themes[0];

                $this->import->data['website_modules'] = json_encode(array_merge(json_decode($theme['w_modules'], true), [$this->import->global_data['modules']['ikosoft']]));
                $this->import->data['theme'] = $theme;

                $website = [
                    'society_id' => $this->import->data['society_id'],
                    'theme_id' => $theme['t_id'],
                    'layout_id' => $theme['l_id'],
                    'modules' => $this->import->data['website_modules'],
                    'render_system' => $theme['w_render_system'],
                    'data' => $theme['w_data'],
                    'domain' => $data['domain'],
                    'state' => $this->import->params['activate'],
                    'created_at' => $date->format('Y-m-d H:i:s'),
                    'updated_at' => $date->format('Y-m-d H:i:s'),
                ];
            }

        }

        if (empty($website)) throw new \Exception($this->import->data['instance'] . ' => Impossible de créer le site');

        $this->import->data['website'] = $website;

        $keys = array_keys($this->import->data['website']);
        if ($this->import->params['action'] == 'update' && isset($website['id'])) {
            $sql = '';
            foreach ($keys as $key) $sql .= '`' . $key . '` = :' . $key . ',';
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'websites SET ' . $sql . ' WHERE id = :id');
        } else {
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'websites (' . implode(',', $keys) . ') VALUES (:' . implode(',:', $keys) . ')');
        }
        $req->execute($this->import->data['website']);

        $this->import->data['website_id'] = isset($website['id']) ? $website['id'] : $this->import->pdo->lastInsertId();
    }
}