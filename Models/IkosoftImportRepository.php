<?php

namespace Jet\Modules\Ikosoft\Models;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Jet\Models\Theme;

/**
 * Class IkosoftImportRepository
 * @package Jet\Modules\Ikosoft\Models
 */
class IkosoftImportRepository extends EntityRepository
{


    /**
     * @param $start
     * @param $max
     * @param array $params
     * @return array
     */
    public function listAll($start = 1, $max = -1, $params = [])
    {

        $countSearch = false;

        $query = IkosoftImport::queryBuilder();

        /* Query */
        $query->select(['w.id AS id', 's.name as society', 'concat(a.first_name, \' \', a.last_name) as full_name', 'a.email as email', 'a.registered_at as registered_at', 'w.domain as website', 'w.state as state'])
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->leftJoin('w.society', 's')
            ->leftJoin('s.account', 'a')
            ->where($query->expr()->isNotNull('w.id'))
            ->setFirstResult($start);

        if ($max >= 0) $query->setMaxResults($max);

        /* Search params */
        if (!empty($params['search'])) $countSearch = true;

        $query = $this->getQueryParams($query, $params);

        $pg = new Paginator($query);
        $data = $pg->getQuery()->getArrayResult();
        return ['data' => $data, 'total' => ($countSearch) ? count($data) : $this->countWebsite()];
    }

    /**
     * @return mixed
     */
    public function countWebsite()
    {
        $query = IkosoftImport::queryBuilder()
            ->select('COUNT(w)')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w');

        return (int)$query->andWhere($query->expr()->isNotNull('w.id'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param QueryBuilder $query
     * @param array $params
     * @return QueryBuilder
     */
    private function getQueryParams(QueryBuilder $query, $params = [])
    {
        /* Search params */
        if (!empty($params['search'])) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('s.name', ':search'),
                $query->expr()->like('a.first_name', ':search'),
                $query->expr()->like('a.last_name', ':search'),
                $query->expr()->like('a.email', ':search'),
                $query->expr()->like('w.domain', ':search')
            ))->setParameter('search', '%' . $params['search'] . '%');
        }

        /* Order params */
        if (!empty($params['order'])) {
            $columns = ['w.id', 's.name', 'a.first_name', 'a.email', 'w.domain', 'w.state', 'a.registered_at'];
            foreach ($params['order'] as $order) {
                if (isset($columns[$order['column']]))
                    $query->addOrderBy($columns[$order['column']], strtoupper($order['dir']));
            }
        } else {
            $query->orderBy('s.id', 'DESC');
        }

        if (isset($params['active']) && isset($params['trial_days']) && $params['active'] == true) {
            $date = new \DateTime($params['trial_days']);
            $now = new \DateTime();
            $query->andWhere('a.registered_at < :date')
                ->setParameter('date', $now->add($date->diff($now)))
                ->andWhere('w.state = 1')
                ->andWhere('a.state = 1')
                ->andWhere('a.expiration_date > CURRENT_DATE()');
        }

        return $query;
    }

    /**
     * @param $uid
     * @return mixed
     */
    public function getImportAccount($uid)
    {
        $query = IkosoftImport::queryBuilder()
            ->select('partial i.{id}')
            ->addSelect('partial a.{id, first_name, last_name, email}')
            ->addSelect('partial w.{id, domain}')
            ->addSelect('partial s.{id}')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->leftJoin('w.society', 's')
            ->leftJoin('s.account', 'a');
        $result = $query->where($query->expr()->eq('i.uid', ':uid'))
            ->setParameter('uid', $uid)
            ->getQuery()
            ->getArrayResult();
        return (isset($result[0])) ? $result[0] : null;
    }

    /**
     * @return int
     */
    public function countUser()
    {
        $query = IkosoftImport::queryBuilder();
        $query->select('COUNT(a)')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->leftJoin('w.society', 'st')
            ->leftJoin('st.account', 'a')
            ->leftJoin('a.status', 's')
            ->where($query->expr()->eq('s.role', ':role'))
            ->setParameter('role', 'user');
        return (int)$query->getQuery()->getSingleScalarResult();
    }

    /**
     * @return mixed
     */
    public function countActiveWebsite()
    {
        $query = IkosoftImport::queryBuilder()
            ->select('COUNT(i)')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w');

        $query->where($query->expr()->eq('w.state', ':state'))
            ->setParameter('state', 1);
        return (int)$query->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countTheme()
    {
        $query = Theme::queryBuilder()
            ->select('COUNT(t)')
            ->from('Jet\Models\Theme', 't')
            ->leftJoin('t.profession', 'p');

        $query->where($query->expr()->eq('t.state', ':state'))
            ->andWhere($query->expr()->in('p.slug', ':professions'))
            ->setParameter('state', 1)
            ->setParameter('professions', ['barber', 'spa']);

        return (int)$query->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $start
     * @param $end
     * @return int
     */
    public function listBetweenDates($start, $end)
    {
        $query = IkosoftImport::queryBuilder();
        $query->select('COUNT(w)')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->where($query->expr()->eq('w.state', 1));
        $query->andWhere($query->expr()->between('w.created_at', ':start', ':end'))
            ->setParameter('start', $start)
            ->setParameter('end', $end);
        return (int)$query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $max
     * @return array
     */
    public function getLast($max = 5)
    {
        $query = IkosoftImport::queryBuilder();
        $query->select('partial i.{id}')
            ->addSelect('partial w.{id,domain,created_at}')
            ->addSelect('partial s.{id,name}')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->leftJoin('w.society', 's');

        $query->setMaxResults($max)
            ->orderBy('w.id', 'DESC');

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param $uid
     * @return array
     */
    public function getWebsiteByUid($uid)
    {
        $query = IkosoftImport::queryBuilder();
        $query->select('partial i.{id}')
            ->addSelect('partial w.{id, state}')
            ->addSelect('partial s.{id}')
            ->addSelect('partial a.{id, state, expiration_date}')
            ->from('Jet\Modules\Ikosoft\Models\IkosoftImport', 'i')
            ->leftJoin('i.website', 'w')
            ->leftJoin('w.society', 's')
            ->leftJoin('s.account', 'a');

        $query->where($query->expr()->eq('i.uid', ':uid'))
            ->setParameter('uid', $uid);

        $result = $query->getQuery()->getArrayResult();
        return isset($result[0]) ? $result[0] : null;
    }


} 