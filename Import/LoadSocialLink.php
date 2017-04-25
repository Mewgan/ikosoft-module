<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadSocialLink
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadSocialLink extends LoadCustomField
{

    private $callback = [
        'social_networks' => 'callRepeater',
        'name' => 'callName',
        'link' => 'callLink'
    ];

    /**
     * @return array|bool
     */
    public function load()
    {
        $this->loadSocialLink();
        return true;
    }

    /**
     *
     */
    private function loadSocialLink()
    {
        if ($this->hasField($this->import->data['website_id'], 'social_networks') == true) {
            $this->updateSocialField('social_networks');
        } else {
            $this->createSocialField('social_networks');
        }
    }

    /**
     * @param $name
     */
    private function createSocialField($name)
    {
        if (isset($this->import->data['websites'])) {
            $custom_field = $this->getCustomField($name);
            if (isset($custom_field['id'])) {
                $data = is_array($this->import->data['website']['data']) ? $this->import->data['website']['data'] : json_decode($this->import->data['website']['data'], true);
                $data['parent_exclude']['custom_fields'] = isset($data['parent_exclude']['custom_fields'])
                    ? array_merge($data['parent_exclude']['custom_fields'], [$custom_field['id']])
                    : [$custom_field['id']];
                $this->import->data['website']['data'] = $data;
                $custom_field_id = $this->createNewCustomField($custom_field);
                $acf = $this->getAdminCustomFields($custom_field['id']);
                $this->createSocialAdminCustomFields($acf, $custom_field_id);
            }
        }
    }

    /**
     * @param $name
     */
    protected function updateSocialField($name)
    {
        $req = $this->import->pdo->prepare('SELECT acf.id, acf.content FROM ' . $this->import->db['prefix'] . 'admin_custom_fields acf
            LEFT JOIN ' . $this->import->db['prefix'] . 'custom_fields cf ON acf.custom_field_id = cf.id
            LEFT JOIN ' . $this->import->db['prefix'] . 'websites w ON cf.website_id = w.id
            WHERE w.id = :id
            AND acf.name = :name
        ');
        $req->execute(['id' => $this->import->data['website_id'], 'name' => $name]);
        $acf = $req->fetch();
        if ($acf !== false) {
            $req2 = $this->import->pdo->prepare('SELECT acf.id, acf.name, acf.content FROM ' . $this->import->db['prefix'] . 'admin_custom_fields acf
                LEFT JOIN ' . $this->import->db['prefix'] . 'custom_fields cf ON acf.custom_field_id = cf.id
                LEFT JOIN ' . $this->import->db['prefix'] . 'websites w ON cf.website_id = w.id
                WHERE w.id = :id
                AND acf.parent_id = :parent_id
            ');
            $acf['content'] = $this->callRepeater($acf['content']);
            $req2->execute(['id' => $this->import->data['website_id'], 'parent_id' => $acf['id']]);
            $all = $req2->fetchAll();
            foreach ($all as $field){
                if($field['name'] == 'name') $field['content'] = $this->callName($field['content']);
                if($field['name'] == 'link') $field['content'] = $this->callLink($field['content']);
                $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'admin_custom_fields SET `content` = :content WHERE id = :id');
                $req->execute(['id' => $field['id'], 'content' => $field['content']]);
            }
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'admin_custom_fields SET `content` = :content WHERE id = :id');
            $req->execute(['id' => $acf['id'], 'content' => $acf['content']]);
        }
    }

    /**
     * @param $acf
     * @param $custom_field_id
     */
    private function createSocialAdminCustomFields($acf, $custom_field_id)
    {
        $sql = '';
        $acf_values = [];
        $keys = ['parent_id', 'custom_field_id', 'title', 'name', 'description', 'type', 'position', 'access_level', 'required', 'data', 'content'];
        $parent_id = null;
        foreach ($acf as $key => $field) {
            $values = [
                $key . 'parent_id' => $parent_id,
                $key . 'custom_field_id' => $custom_field_id,
                $key . 'title' => $field['title'],
                $key . 'name' => $field['name'],
                $key . 'description' => is_null($field['description']) ? NULL : $field['description'],
                $key . 'type' => $field['type'],
                $key . 'position' => $field['position'],
                $key . 'access_level' => $field['access_level'],
                $key . 'required' => $field['required'],
                $key . 'data' => $field['data'],
                $key . 'content' => (isset($this->callback[$field['name']])) ? call_user_func_array([$this, $this->callback[$field['name']]], [$field['content']]) : $field['content'],
            ];
            if($field['name'] == 'social_networks') {
                $sql2 = '(:' . implode(',:', array_keys($values)) . ')';
                $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'admin_custom_fields (' . implode(',', $keys) . ') VALUES ' . $sql2);
                $req->execute($values);
                $parent_id = $this->import->pdo->lastInsertId();
            }else {
                $sql .= '(:' . implode(',:', array_keys($values)) . '),';
                $acf_values = array_merge($acf_values, $values);
            }
        }

        if (!empty($acf_values)) {
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'admin_custom_fields (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($acf_values);
        }
    }

    /**
     * @param $content
     * @return string
     */
    public function callRepeater($content)
    {
        $content = is_array($content) ? $content : json_decode($content, true);
        if (isset($content['type'])) {
            $content['type'] = 'repeater';
            $content['rows'] = [0];
        }
        return json_encode($content);
    }

    /**
     * @param $content
     * @return string
     */
    public function callName($content)
    {
        $content = is_array($content) ? $content : json_decode($content, true);
        if (isset($content['value']))
            $content['value'] = ['Facebook'];
        return json_encode($content);
    }

    /**
     * @param $content
     * @return string
     */
    public function callLink($content)
    {
        $content = is_array($content) ? $content : json_decode($content, true);
        if (isset($content['value']) && isset($this->import->global_data['information']['Facebook']))
            $content['value'] = [$this->import->global_data['information']['Facebook']];
        return json_encode($content);
    }

}