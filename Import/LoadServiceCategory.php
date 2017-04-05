<?php

namespace Jet\Modules\Ikosoft\Import;

use Cocur\Slugify\Slugify;

/**
 * Class LoadServiceCategory
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadServiceCategory extends LoadFixture
{

    /**
     * @param $service
     * @return array|bool
     */
    public function load($service)
    {

        $categories = [];

        $this->import->data['service_categories'] = [];

        foreach ($service->s as $category) {
            foreach ($category->e as $entry) {
                if ((string)$entry['n'] == 'Caption') {
                    $categories[] = (string)$entry['v'];
                    $this->import->data['service_categories'][(string)$category['n']] = (string)$entry['v'];
                }
            }
        }
        return (!empty($categories))
            ? $this->loadServiceCategoryData($categories)
            : true;
    }

    /**
     * @param array $categories
     * @return bool
     */
    private function loadServiceCategoryData($categories = [])
    {
        if ($this->hasModule('price')) {
            $categories_in_db = $this->getCategoriesFromDb();
            $categories = $this->deleteUnusedCategories($categories_in_db, $categories);
            if(!empty($categories)) $this->createCategories($categories);
        }
        return true;
    }

    /**
     * @return array
     */
    private function getCategoriesFromDb()
    {
        $req = $this->import->pdo->prepare('SELECT cat.id, cat.name, cat.website_id FROM ' . $this->import->db['prefix'] . 'service_categories cat WHERE cat.website_id IN (' . implode(',', $this->import->data['websites']) . ') OR cat.website_id IS NULL');
        $req->execute();
        return $req->fetchAll();
    }


    /**
     * @param array $categories_in_db
     * @param array $categories
     * @return array
     */
    private function deleteUnusedCategories($categories_in_db = [], $categories = [])
    {
        if (!empty($categories)) {
            
            $flip = array_flip($categories);
            $delete_ids = $exclude_ids = [];
            foreach ($categories_in_db as $category) {
                if(isset($flip[$category['name']])) {
                    unset($flip[$category['name']]);
                }else{
                    if ($category['website_id'] == $this->import->data['website_id'])
                        $delete_ids[] = (int)$category['id'];
                    else
                        $exclude_ids[] = $category['id'];
                }
            }
           
            $data = is_array($this->import->data['website']['data'])
                ? $this->import->data['website']['data']
                : json_decode($this->import->data['website']['data'], true);
            $data['parent_exclude']['service_categories'] = isset($data['parent_exclude']['service_categories'])
                ? array_merge($data['parent_exclude']['service_categories'], $exclude_ids)
                : $exclude_ids;
            $this->import->data['website']['data'] = $data;
            
            if(!empty($delete_ids)) {
                $req = $this->import->pdo->prepare('DELETE FROM ' . $this->import->db['prefix'] . 'service_categories WHERE id IN (' . implode(',', $delete_ids) . ')');
                $req->execute();
            }

            return array_flip($flip);
        }
        return $categories;
    }

    /**
     * @param array $categories
     * @return bool
     */
    private function createCategories($categories = [])
    {
        $data = [];
        $slug = new Slugify();
        $date = new \DateTime();
        $keys = ['name', 'slug', 'website_id', 'created_at', 'updated_at'];
        $sql = '';

        foreach ($categories as $key => $category) {
            if (!empty($category)) {
                $values = [
                    $key . '_name' => $category,
                    $key . '_slug' => $slug->slugify($category),
                    $key . '_website_id' => $this->import->data['website_id'],
                    $key . '_created_at' => $date->format('Y-m-d H:i:s'),
                    $key . '_updated_at' => $date->format('Y-m-d H:i:s')
                ];
                $data = array_merge($data, $values);
                $sql .= '(:' . implode(',:', array_keys($values)) . '),';
            }
        }
        if (!empty($data)) {

            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'service_categories (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($data);

        }
        return true;
    }


}