<?php

namespace App\Repository;

use App\Entity\ApiToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 *
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

    public function save(ApiToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    
    /**
     * deleteAfter
     *
     * @param  int $minutes
     * @return void
     * 
     * Removes obsolete tokens, older than $minutes
     */
    public function deleteAfter($minutes = 60)
    {
        $mindate = date('Y-m-d H:i:s', mktime(date('H'),date('i') - $minutes,date('s'), date('n'), date('j'), date('Y')));

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
