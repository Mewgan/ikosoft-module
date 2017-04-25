<?php

namespace Jet\Modules\Ikosoft\Import;

use PDO;

/**
 * Class LoadCustomField
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadCustomField extends LoadFixture
{

    /**
     * @param $website_id
     * @param $name
     * @return bool
     */
    protected function hasField($website_id, $name)
    {
        $req = $this->import->pdo->prepare('SELECT COUNT(*)
            FROM ' . $this->import->db['prefix'] . 'admin_custom_fields acf
            LEFT JOIN ' . $this->import->db['prefix'] . 'custom_fields cf ON acf.custom_field_id = cf.id
            LEFT JOIN ' . $this->import->db['prefix'] . 'websites w ON cf.website_id = w.id
            WHERE w.id = :id
            AND acf.name = :name'
        );
        $req->execute(['id' => $website_id, 'name' => $name]);
        return ($req->fetchColumn() > 0);
    }

    /**
     * @param $name
     * @return
     * @throws \Exception
     */
    protected function getCustomField($name)
    {
        $req = $this->import->pdo->prepare('SELECT cf.id, cf.rule_id, cf.title, cf.operation, cf.value, cf.access_level
            FROM ' . $this->import->db['prefix'] . 'admin_custom_fields acf
            LEFT JOIN ' . $this->import->db['prefix'] . 'custom_fields cf ON acf.custom_field_id = cf.id
            LEFT JOIN ' . $this->import->db['prefix'] . 'websites w ON cf.website_id = w.id
            WHERE w.id IN (' . implode(',', $this->import->data['websites']) . ')
            AND acf.name = :name
            ORDER BY w.id ASC'
        );
        $req->execute(['name' => $name]);
        $res = $req->fetchAll();
        if(!isset($res[0]))
            throw new \Exception('Impossible de trouver le champ personnalisé');
        return $res[0];
    }

    /**
     * @param array $custom_field
     * @return null|string
     */
    protected function createNewCustomField($custom_field = [])
    {
        $cf_values = [
            'rule_id' => $custom_field['rule_id'],
            'website_id' => $this->import->data['website_id'],
            'title' => $custom_field['title'],
            'operation' => $custom_field['operation'],
            'access_level' => $custom_field['access_level']
        ];
        if (!is_null($custom_field['value'])) $cf_values['value'] = $custom_field['value'];
        $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'custom_fields (' . implode(',', array_keys($cf_values)) . ') VALUES (:' . implode(",:", array_keys($cf_values)) . ')');
        $req->execute($cf_values);
        return $this->import->pdo->lastInsertId();
    }

    /**
     * @param $custom_field_id
     * @return array
     */
    protected function getAdminCustomFields($custom_field_id)
    {
        $req = $this->import->pdo->prepare('SELECT * FROM ' . $this->import->db['prefix'] . 'admin_custom_fields acf WHERE acf.custom_field_id = ? ORDER BY acf.id');
        $req->execute([$custom_field_id]);
        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $acf
     * @param $custom_field_id
     * @param $name
     * @param array $callback
     * @param array $args
     */
    protected function createAdminCustomFields($acf, $custom_field_id, $name, $callback = [], $args = [])
    {
        $sql = '';
        $acf_values = [];
        $keys = ['parent_id', 'custom_field_id', 'title', 'name', 'description', 'type', 'position', 'access_level', 'required', 'data', 'content'];
        foreach ($acf as $key => $field) {
            $values = [
                $key . 'parent_id' => is_null($field['parent_id']) ? NULL : $field['parent_id'],
                $key . 'custom_field_id' => $custom_field_id,
                $key . 'title' => $field['title'],
                $key . 'name' => $field['name'],
                $key . 'description' => is_null($field['description']) ? NULL : $field['description'],
                $key . 'type' => $field['type'],
                $key . 'position' => $field['position'],
                $key . 'access_level' => $field['access_level'],
                $key . 'required' => $field['required'],
                $key . 'data' => $field['data'],
                $key . 'content' => ($field['name'] == $name) ? call_user_func_array($callback, array_merge([$field['content'], $args])) : $field['content'],
            ];
            $acf_values = array_merge($acf_values, $values);
            $sql .= '(:' . implode(',:', array_keys($values)) . '),';
        }
        if (!empty($acf_values)) {
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'admin_custom_fields (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($acf_values);
        }
    }


    /**
     * @param $name
     * @param array $callback
     * @param array $args
     */
    protected function createField($name, $callback = [], $args = [])
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
                $this->createAdminCustomFields($acf, $custom_field_id, $name, $callback, $args);
            }
        }
    }

    /**
     * @param $name
     * @param $callback
     * @param array $args
     */
    protected function updateField($name, $callback, $args = [])
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
            $content = call_user_func_array($callback, array_merge([$acf['content'], $args]));
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'admin_custom_fields SET `content` = :content WHERE id = :id');
            $req->execute(['id' => $acf['id'], 'content' => $content]);
        }
    }
}