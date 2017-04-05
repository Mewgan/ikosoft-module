<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadMedia
 * @package JJet\Modules\Ikosoft\Import
 */
class LoadMedia extends LoadFixture
{

    /**
     * @return string
     * @throws \Exception
     */
    private function getWebsitePath()
    {
        $path = '/public/media/sites/' . $this->import->data['website_id'] . '/';
        if(is_writable(ROOT . '/public/media/sites/') !== true) throw new \Exception('Vous n\'avez pas les permissions pour écrire dans le dossier : ' . ROOT . '/public/media/sites/');
        if (!is_dir(ROOT . $path))
            if(mkdir(ROOT . $path) === false) throw new \Exception('Impossible de créer le dossier : ' . ROOT . $path);
        return $path;
    }

    /**
     * @return array|bool
     */
    public function load()
    {
        $medias = [];
        $files = glob($this->import->data['instance_path'] . '*.{jpg,gif,png}', GLOB_BRACE);
        foreach ($files as $media) {
            $file = pathinfo($media);
            $medias[$file['filename']] = $this->getWebsitePath() . $file['filename'] . '.' . $file['extension'];
        }
        if(!empty($medias)) {
            $media_in_db = $this->getMediaFromDb();
            $medias = $this->removeUnusedMedia($media_in_db, $medias);
            return $this->createMedia($medias);
        }
        return true;
    }

    /**
     * @return array
     */
    private function getMediaFromDb()
    {
        $req = $this->import->pdo->prepare('SELECT m.id, m.path FROM ' . $this->import->db['prefix'] . 'medias m       
            WHERE m.website_id = :website_id
        ');
        $req->execute(['website_id' => $this->import->data['website_id']]);
        return $req->fetchAll();
    }

    /**
     * @param array $media_in_db
     * @param array $medias
     * @return array
     */
    private function removeUnusedMedia($media_in_db = [], $medias = [])
    {
        $flip = array_flip($medias);
        foreach ($media_in_db as $media) {
            unset($flip[$media['path']]);
        }
        return array_flip($flip);

    }

    private function createMedia($medias = [])
    {
        $pictures = [];
        $date = new \DateTime();
        $keys = ['account_id', 'website_id', 'title', 'path', 'type', 'size', 'created_at', 'updated_at'];
        $sql = '';
        $i = 0;
        foreach ($medias as $key => $media) {
            if (!empty($media)) {
                $values = [
                    $i . '_account_id' => $this->import->data['account_id'],
                    $i . '_website_id' => $this->import->data['website_id'],
                    $i . '_title' => $key,
                    $i . '_path' => $media,
                    $i . '_type' => 'image/png',
                    $i . '_size' => 0,
                    $i . '_created_at' => $date->format('Y-m-d H:i:s'),
                    $i . '_updated_at' => $date->format('Y-m-d H:i:s')
                ];
                $pictures = array_merge($pictures, $values);
                $sql .= '(:' . implode(',:', array_keys($values)) . '),';
                ++$i;
            }
        }
        if (!empty($pictures)) {
            $sql = rtrim($sql, ',');
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'medias (' . implode(',', $keys) . ') VALUES ' . $sql);
            if ($req->execute($pictures) === true) {
                foreach ($medias as $media => $new_path) {
                    $ext = explode('.', $new_path);
                    $ext = end($ext);
                    if (copy($this->import->data['instance_path'] . $media . '.' . $ext, ROOT . $new_path) === false)
                        return ['status' => 'error', 'message' => 'Erreur lors de la copie du fichier : ' . $this->import->data['instance_path'] . $media . '.' . $ext . ' vers : ' .ROOT . $new_path];
                }
            }
        }
        return true;
    }
}