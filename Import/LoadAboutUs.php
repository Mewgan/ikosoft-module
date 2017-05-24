<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadAboutUs
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadAboutUs extends LoadFixture
{

    /**
     * @return array|bool
     */
    public function load()
    {
        if ($this->hasModule('post')) {
            $post = $this->getPost();
            if(isset($post['id'])){
                ($post['website_id'] == $this->import->data['website_id'])
                    ? $this->createPost($post)
                    : $this->updatePost($post);
            }
        }
        return true;
    }


    /**
     * @return mixed
     */
    private function getPost()
    {
        $req = $this->import->pdo->prepare('SELECT * 
            FROM ' . $this->import->db['prefix'] . 'posts p          
            WHERE p.slug = :slug
            AND p.website_id IN (' . implode(',', $this->import->data['websites']) . ')
        ');
        $req->execute(['slug' => 'a-propos-de-nous']);
        return $req->fetch();
    }


    /**
     * @param array $post
     * @return array
     */
    private function createPost($post = [])
    {
        if (!empty($post)) {

            $data = is_array($this->import->data['website']['data'])
                ? $this->import->data['website']['data']
                : json_decode($this->import->data['website']['data'], true);

            $data['parent_exclude']['posts'] = isset($data['parent_exclude']['posts'])
                ? array_merge($data['parent_exclude']['posts'], [$post['id']])
                : [$post['id']];

            $date = new \DateTime();
            $values = [
                'title' => $post['title'],
                'slug' => $post['slug'],
                'website_id' => $this->import->data['website_id'],
                'description' => substr($this->import->global_data['information']['Comment'], 0 , 300),
                'content' => $this->import->global_data['information']['Comment'],
                'created_at' => $date->format('Y-m-d H:i:s'),
                'updated_at' => $date->format('Y-m-d H:i:s'),
                'published' => 1
            ];
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'posts (' . implode(',', array_keys($values)) . ') VALUES (:' . implode(',:', array_keys($values)) . ')');
            $req->execute($values);

            $post_id = $this->import->pdo->lastInsertId();

            $data['parent_replace']['posts'] = isset($data['parent_replace']['posts'])
                ? array_merge($data['parent_replace']['posts'], [$post['id'] => $post_id])
                : [$post['id'] => $post_id];

            $this->import->data['website']['data'] = $data;

        }
    }

    /**
     * @param array $post
     * @return array
     */
    private function updatePost($post = [])
    {
        if (!empty($post)) {

            $date = new \DateTime();
            $values = [
                'id' => $post['id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'website_id' => $this->import->data['website_id'],
                'description' => substr($this->import->global_data['information']['Comment'], 0 , 100),
                'content' => $this->import->global_data['information']['Comment'],
                'created_at' => $date->format('Y-m-d H:i:s'),
                'updated_at' => $date->format('Y-m-d H:i:s'),
                'published' => 1
            ];
            $sql = '';
            foreach ($values as $key => $value) $sql .= '`' . $key . '` = :' . $key . ',';
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('UPDATE ' . $this->import->db['prefix'] . 'posts SET ' . $sql . ' WHERE id = :id');
            $req->execute($values);
        }
    }

}