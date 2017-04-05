<?php

namespace Jet\Modules\Ikosoft\Import;

/**
 * Class LoadTeam
 * @package Jet\Modules\Ikosoft\Import
 */
class LoadTeam extends LoadFixture
{
    /**
     * @param $service
     * @return array|bool
     */
    public function load($service)
    {

        $team = $duplicate = [];
        $i = 0;
        foreach ($service->s as $supplier) {
            $data = [];
            foreach ($supplier->e as $entry) {
                if ((string)$entry['n'] == 'DisplayName') $duplicate[(string)$entry['v']] = $i;
                $data[(string)$entry['n']] = (string)$entry['v'];
            }
            $team[$i] = $data;
            ++$i;
        }

        $this->loadTeamData($team, $duplicate);

        return true;
    }

    /**
     * @param array $team
     * @param array $duplicate
     */
    private function loadTeamData($team = [], $duplicate = [])
    {
        if ($this->hasModule('team')) {
            $team_in_db = $this->getTeamFromDb();
            $team = $this->deleteUnusedSuppliers($team_in_db, $team, $duplicate);
            if(!empty($team)) $this->createTeam($team);
        }
    }

    /**
     * @return array
     */
    private function getTeamFromDb()
    {
        $req = $this->import->pdo->prepare('SELECT t.id, t.full_name, t.website_id FROM ' . $this->import->db['prefix'] . 'teams t WHERE t.website_id IN (' . implode(',', $this->import->data['websites']) . ')');
        $req->execute();
        return $req->fetchAll();
    }


    /**
     * @param array $team_in_db
     * @param array $team
     * @param array $duplicate
     * @return array
     */
    private function deleteUnusedSuppliers($team_in_db = [], $team = [], $duplicate = [])
    {
        if (!empty($team)) {

            $delete_ids = $exclude_ids = [];
            foreach ($team_in_db as $member) {
                if (isset($duplicate[$member['full_name']])) {
                    unset($team[$duplicate[$member['full_name']]]);
                } else {
                    if ($member['website_id'] == $this->import->data['website_id'])
                        $delete_ids[] = (int)$member['id'];
                    else
                        $exclude_ids[] = $member['id'];
                }
            }

            $data = is_array($this->import->data['website']['data'])
                ? $this->import->data['website']['data']
                : json_decode($this->import->data['website']['data'], true);
            $data['parent_exclude']['teams'] = isset($data['parent_exclude']['teams'])
                ? array_merge($data['parent_exclude']['teams'], $exclude_ids)
                : $exclude_ids;
            $this->import->data['website']['data'] = $data;

            if (!empty($delete_ids)) {
                $req = $this->import->pdo->prepare('DELETE FROM ' . $this->import->db['prefix'] . 'teams WHERE id IN (' . implode(',', $delete_ids) . ')');
                $req->execute();
            }
        }
        return $team;
    }

    /**
     * @return array
     */
    private function getTeamPictures()
    {
        $req = $this->import->pdo->prepare('SELECT m.id, m.title FROM ' . $this->import->db['prefix'] . 'medias m WHERE m.website_id = :website_id');
        $req->execute(['website_id' => $this->import->data['website_id']]);
        $all = $req->fetchAll();
        $result = [];
        foreach ($all as $media) $result[$media['title']] = $media['id'];
        return $result;
    }

    /**
     * @param array $team
     * @return bool
     */
    private function createTeam($team = [])
    {
        $pictures = $this->getTeamPictures();

        $data = [];
        $date = new \DateTime();
        $sql = '';
        $i = 0;

        $keys = ['photo_id', 'website_id', 'full_name', 'gender', 'position', 'description', 'created_at', 'updated_at'];

        foreach ($team as $key => $member) {
            if (!empty($member)) {
                $values = [
                    $key . '_photo_id' => (isset($pictures[$member['GuidPicture']])) ? $pictures[$member['GuidPicture']] : null,
                    $key . '_website_id' => $this->import->data['website_id'],
                    $key . '_full_name' => $member['DisplayName'],
                    $key . '_gender' => (int)$member['Gender'],
                    $key . '_position' => $i,
                    $key . '_description' => $member['Comment'],
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
            $req = $this->import->pdo->prepare('INSERT INTO ' . $this->import->db['prefix'] . 'teams (' . implode(',', $keys) . ') VALUES ' . $sql);
            $req->execute($data);
        }
        return true;
    }


}