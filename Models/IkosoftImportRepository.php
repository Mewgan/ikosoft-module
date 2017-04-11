<?php

namespace Jet\Modules\Ikosoft\Models;

use Doctrine\ORM\EntityRepository;

/**
 * Class IkosoftImportRepository
 * @package Jet\Modules\Ikosoft\Models
 */
class IkosoftImportRepository extends EntityRepository
{

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

} 