<?php

namespace Jet\Modules\Ikosoft\Import;

use Cocur\Slugify\Slugify;

/**
 * Class LoadSupplier
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadSupplier extends LoadFixture
{

    /**
     * @param $service
     * @return array|bool
     */
    public function load($service)
    {

        $suppliers = [];

        foreach ($service->s as $supplier) {
            foreach ($supplier->e as $entry) {
                if ((string)$entry['n'] == 'Caption')
                    $suppliers[] = (string)$entry['v'];
            }
        }
        return (!empty($suppliers))
            ? $this->loadSupplierData($suppliers)
            : true;
    }

    /**
     * @param array $suppliers
     * @return bool
     */
    private function loadSupplierData($suppliers = [])
    {
        if ($this->hasModule('single-post')) {
            $suppliers_in_db = $this->getSuppliersFromDb();
            $suppliers = $this->deleteUnusedSuppliers($suppliers_in_db, $suppliers);
            if (!empty($suppliers)) $this->createSuppliers($suppliers);
        }
        return true;
    }

    /**
     * @return array
     */
    private function getSuppliersFromDb()
    {
        $req = $this->import->pdo->prepare('SELECT p.id, p.title, p.website_id 
            FROM ' . $this->import->db['prefix'] . 'posts p
            LEFT JOIN ' . $this->import->db['prefix'] . 'posts_categories pc ON pc.post_id = p.id
            LEFT JOIN ' . $this->import->db['prefix'] . 'post_categories c ON c.id = pc.postcategory_id
            WHERE c.id = :id
            AND p.website_id IN (' . implode(',', $this->import->data['websites']) . ')
        ');
        $req->execute(['id' => $this->import->global_data['supplier_category']]);
        return $req->fetchAll();
    }


    /**
     * @param array $suppliers_in_db
     * @param array $suppliers
     * @return array
     */
    private function deleteUnusedSuppliers($suppliers_in_db = [], $suppliers = [])
    {
        if (!empty($suppliers)) {

            $flip = array_flip($suppliers);
            $delete_ids = $exclude_ids = [];
            foreach ($suppliers_in_db as $supplier) {
                if (isset($flip[$supplier['title']])) {
                    unset($flip[$supplier['title']]);
                } else {
                    if ($supplier['website_id'] == $this->import->data['website_id'])
                        $delete_ids[] = $supplier['id'];
                    else
                        $exclude_ids[] = $supplier['id'];
                }
            }

            $data = is_array($this->import->data['website']['data'])
                ? $this->import->data['website']['data']
                : json_decode($this->import->data['website']['data'], true);
            $data['parent_exclude']['posts'] = isset($data['parent_exclude']['posts'])
                ? array_merge($data['parent_exclude']['posts'], $exclude_ids)
                : $exclude_ids;
            $this->import->data['website']['data'] = $data;

            if (!empty($delete_ids)) {
                $req = $this->import->pdo->prepare('DELETE FROM ' . $this->import->db['prefix'] . 'posts WHERE id IN (' . implode(',', $delete_ids) . ')');
                $req->execute();
            }

            return array_flip($flip);
        }
        return $suppliers;
    }

    /**
     * @param array $suppliers
     * @return bool
     */
    private function createSuppliers($suppliers = [])
    {
        $data = [];
        $slug = new Slugify();
        $date = new \DateTime();
        $keys = ['title', 'slug', 'website_id', 'created_at', 'updated_at', 'published'];
        $sql = '';
        foreach ($suppliers as $key => $supplier) {
            if (!empty($supplier)) {
                $values = [
                    $key . '_title' => $supplier,
                    $key . '_slug' => $slug->slugify($supplier),
                    $key . '_website_id' => $this->import->data['website_id'],
                    $key . '_created_at' => $date->format('Y-m-d H:i:s'),
                    $key . '_updated_at' => $date->format('Y-m-d H:i:s'),
                    $key . '_published' => 1
                ];
                $data = array_merge($data, $values);
                $sql .= '(:' . implode(',:', array_keys($values)) . '),';
            }
        }
        if (!empty($data)) {

            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'posts (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($data);

            if (($count = $req->rowCount()) > 0) {
                $id = (int)$this->import->pdo->lastInsertId();
                $ids = [];
                for ($i = 0; $i < $count; ++$i) $ids[] = $id + $i;
                if (!empty($ids)) {
                    $sql = '';
                    foreach ($ids as $id) $sql .= '(' . $id . ',' . $this->import->global_data['supplier_category'] . '),';
                    $sql = rtrim($sql, ',');

                    $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'posts_categories (post_id, postcategory_id) VALUES ' . $sql);
                    $req->execute();
                }
            }
        }
        return true;
    }


}