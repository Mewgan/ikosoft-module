<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadService
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadService extends LoadFixture
{

    /**
     * @param $service
     * @return array|bool
     */
    public function load($service)
    {

        $services = $duplicate = [];
        $i = 0;
        foreach ($service->s as $key => $price) {
            $data = [];
            foreach ($price->e as $entry) {
                if ((string)$entry['n'] == 'Caption') $duplicate[(string)$entry['v']] = $i;
                $data[(string)$entry['n']] = (string)$entry['v'];
            }
            $services[$i] = $data;
            ++$i;
        }
        return (!empty($services))
            ? $this->loadServiceData($services, $duplicate)
            : true;
    }

    /**
     * @param array $services
     * @param array $duplicate
     * @return bool
     */
    private function loadServiceData($services = [], $duplicate = [])
    {
        if ($this->hasModule('price')) {
            $services_in_db = $this->getServicesFromDb();
            $services = $this->deleteUnusedServices($services_in_db, $services, $duplicate);
            if (!empty($services)) $this->createServices($services);
        }
        return true;
    }

    /**
     * @return array
     */
    private function getServicesFromDb()
    {
        $req = $this->import->pdo->prepare('SELECT s.id, s.title, s.website_id FROM ' . $this->import->db['prefix'] . 'services s WHERE s.website_id IN (' . implode(',', $this->import->data['websites']) . ')');
        $req->execute();
        return $req->fetchAll();
    }


    /**
     * @param array $services_in_db
     * @param array $services
     * @param array $duplicate
     * @return array
     */
    private function deleteUnusedServices($services_in_db = [], $services = [], $duplicate = [])
    {
        if (!empty($services)) {

            $delete_ids = $exclude_ids = [];
            foreach ($services_in_db as $service) {
                if (isset($duplicate[$service['title']])) {
                    unset($services[$duplicate[$service['title']]]);
                } else {
                    if ($service['website_id'] == $this->import->data['website_id'])
                        $delete_ids[] = (int)$service['id'];
                    else
                        $exclude_ids[] = $service['id'];
                }
            }

            $data = is_array($this->import->data['website']['data'])
                ? $this->import->data['website']['data']
                : json_decode($this->import->data['website']['data'], true);
            $data['parent_exclude']['services'] = isset($data['parent_exclude']['services'])
                ? array_merge($data['parent_exclude']['services'], $exclude_ids)
                : $exclude_ids;
            $this->import->data['website']['data'] = $data;

            if (!empty($delete_ids)) {
                $req = $this->import->pdo->prepare('DELETE FROM ' . $this->import->db['prefix'] . 'services WHERE id IN (' . implode(',', $delete_ids) . ')');
                $req->execute();
            }
        }
        return $services;
    }

    /**
     * @return array
     */
    private function getServiceCategories()
    {
        $req = $this->import->pdo->prepare('SELECT c.id, c.name FROM ' . $this->import->db['prefix'] . 'service_categories c WHERE c.website_id = :website_id');
        $req->execute(['website_id' => $this->import->data['website_id']]);
        $all = $req->fetchAll();
        $result = [];
        foreach ($all as $cat) $result[$cat['name']] = $cat['id'];
        return $result;
    }

    /**
     * @param array $services
     * @return bool
     */
    private function createServices($services = [])
    {
        $categories = $this->getServiceCategories();

        $data = [];
        $date = new \DateTime();
        $keys = ['category_id', 'website_id', 'title', 'price', 'position', 'description', 'created_at', 'updated_at'];
        $sql = '';
        $i = 0;

        foreach ($services as $key => $service) {
            if (!empty($service)) {
                $category = (isset($this->import->data['service_categories'][$service['Family']]) && isset($categories[$this->import->data['service_categories'][$service['Family']]]))
                    ? $categories[$this->import->data['service_categories'][$service['Family']]]
                    : null;
                $values = [
                    $key . '_category_id' => $category,
                    $key . '_website_id' => $this->import->data['website_id'],
                    $key . '_title' => $service['Caption'],
                    $key . '_price' => $this->formatPrice($service['Price']),
                    $key . '_position' => $i,
                    $key . '_description' => '',
                    $key . '_created_at' => $date->format('Y-m-d H:i:s'),
                    $key . '_updated_at' => $date->format('Y-m-d H:i:s')
                ];
                $data = array_merge($data, $values);
                $sql .= '(:' . implode(',:', array_keys($values)) . '),';
                ++$i;
            }
        }
        if (!empty($data)) {

            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'services (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($data);

        }

        return true;
    }


    /**
     * @param $price
     * @return float|int|string
     */
    private function formatPrice($price)
    {
        $price = ((int)$price)/100;
        $locales = [
            'FRA' => [
                'locale' => 'fr_FR.utf8',
                'format' => '%(#1n'
            ]
        ];
        if(isset($this->import->global_data['information']['Language']) && isset($locales[$this->import->global_data['information']['Language']])){
            $locale = $locales[$this->import->global_data['information']['Language']];
            setlocale(LC_MONETARY, $locale['locale']);
            return money_format($locale['format'], $price);
        }
        return $price;
    }
}