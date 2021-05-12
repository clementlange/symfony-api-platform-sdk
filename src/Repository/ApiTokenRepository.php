<?php

namespace App\Repository;

use App\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ApiToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiToken[]    findAll()
 * @method ApiToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    
    /**
     * deleteAfterDays
     *
     * @param  int $days
     * @return void
     * 
     * Removes obsolete tokens, older than $days
     */
    public function deleteAfterDays($days = 14)
    {
        $mindate = date('Y-m-d', mktime(0,0,0, date('n'), date('j') - $days, date('Y')));

        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.createdAt < :mindate')
            ->setParameter('mindate', $mindate)
            ->getQuery()
            ->getResult();
    }

    
    /**
     * deleteUserToken
     *
     * @param  string $user
     * @param  string $domain
     * @return void
     * 
     * Deletes token for a specific user and domain
     */
    public function deleteUserToken($user = '', $domain = '')
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->where('a.user = :user')
            ->andWhere('a.domain = :domain')
            ->setParameter('user', $user)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getResult();
    }

}
