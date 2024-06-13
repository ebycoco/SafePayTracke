<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findPaymentPaginated(int $page, int $limit = 2): array
    {
        
        $limit = abs($limit);

        $result = [];

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Payment', 'p')
            ->where('p.isVisibilite = :visibilite')
            ->setParameter('visibilite', true)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        
        //On vérifie qu'on a des données

        if(empty($data)){
            return $result;
        }

        //On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }

    public function findPaymentHistoryPaginated(int $page, int $limit = 2): array
    {

        $limit = abs($limit);

        $result = [];

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Payment', 'p')
            ->where('p.isVisibilite = :visibilite')
            ->setParameter('visibilite', false)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        //On vérifie qu'on a des données

        if(empty($data)){
            return $result;
        }

        //On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }

    public function findPaymentNouvellePaginated(int $page, int $limit = 2): array
    {

        $limit = abs($limit);

        $result = [];

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Payment', 'p')
            ->where('p.status = :status')
            ->setParameter('status', "en attente")
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        //On vérifie qu'on a des données

        if(empty($data)){
            return $result;
        }

        //On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }

    public function findPaymentAjoutMontantPrevuPaginated(int $page, int $limit = 2): array
    {

        $limit = abs($limit);

        $result = [];

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Payment', 'p')
            ->where('p.isVisibilite = :visibilite')
            ->setParameter('visibilite', false)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        //On vérifie qu'on a des données

        if(empty($data)){
            return $result;
        }

        //On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }

    public function findPaymentUserPaginated(User $user, int $page, int $limit = 8): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findPaymentUserRetardPaginated(User $user, int $page, int $limit = 8, string $retard = "retard",$status="non payé"): array
{
    $offset = ($page - 1) * $limit;

    return $this->createQueryBuilder('p')
        ->andWhere('p.users = :user')
        ->andWhere('p.typePaiement = :type_paiement')
        ->andWhere('p.status = :status')
        ->setParameter('user', $user)
        ->setParameter('type_paiement', $retard)
        ->setParameter('status', $status)
        ->orderBy('p.datePaiement', 'DESC')
        ->setMaxResults($limit)
        ->setFirstResult($offset)
        ->getQuery()
        ->getResult();
}



    public function findSecondLatestPaymentByUser(User $user): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(2) // Récupérer les deux paiements les plus récents
            ->getQuery()
            ->getResult()[1] ?? null; // Obtenir le deuxième résultat
    }

    public function findSecondLatestDEPaymentByUser(User $user): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(2) // Récupérer les deux paiements les plus récents
            ->getQuery()
            ->getResult()[1] ?? null; // Obtenir le deuxième résultat
    }
    public function findSecondLatestDEPaymentByUserOne(User $user): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(2) // Récupérer les deux paiements les plus récents
            ->getQuery()
            ->getResult()[0] ?? null; // Obtenir le premier résultat
    }
    public function findSecondLatestDEPaymentByUserCi(User $user): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(2) // Récupérer les deux paiements les plus récents
            ->getQuery()
            ->getResult()[0] ?? null; // Obtenir le deuxième résultat
    }

    public function updateSoldeForUser(User $user, int $nouveauSolde): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.solde', $nouveauSolde)
            ->where('p.users = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findPaymentsByUserAndMonth($userId,$year, $month)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->andWhere("YEAR(p.datePaiement) = :year") // Utilisez le bon nom de champ pour 'datePayment'
            ->andWhere("MONTH(p.datePaiement) = :month") // Utilisez le bon nom de champ pour 'datePayment'
            ->setParameter('user', $userId)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(1);
        return  $qb->getQuery()->getOneOrNullResult();
    }
    public function findPaymentsByUserPrecedent($userId,$year, $month)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->andWhere("YEAR(p.datePaiement) = :year") // Utilisez le bon nom de champ pour 'datePayment'
            ->andWhere("MONTH(p.datePaiement) = :month") // Utilisez le bon nom de champ pour 'datePayment'
            ->setParameter('user', $userId)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(1);
        return  $qb->getQuery()->getOneOrNullResult();
    }

    


    public function findPayemntMoisSelectionne($userId,$year, $month)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->andWhere("YEAR(p.datePaiement) = :year") // Utilisez le bon nom de champ pour 'datePayment'
            ->andWhere("MONTH(p.datePaiement) = :month")
            ->setParameter('user', $userId)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('p.datePaiement', 'DESC');
        return  $qb->getQuery()->getResult();
    }

    public function findPaymentsByUser($userId)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->setParameter('user', $userId)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults(1);
        return  $qb->getQuery()->getOneOrNullResult();
    }

    public function findPaymentsByUserAll($userId)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->setParameter('user', $userId)
            ->orderBy('p.datePaiement', 'DESC');
        return  $qb->getQuery()->getResult();
    }

    public function findPaymentsByUserAndMonthTout($userId,$year, $month)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->andWhere('p.users = :user') // Assurez-vous que l'alias 'p.user' correspond à votre mappage d'entité
            ->andWhere("YEAR(p.datePaiement) = :year") // Utilisez le bon nom de champ pour 'datePayment'
            ->andWhere("MONTH(p.datePaiement) = :month")
            ->setParameter('user', $userId)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('p.datePaiement', 'DESC');
        return  $qb->getQuery()->getResult();
    }

     /**
     * Recherche un paiement pour un utilisateur donné et un mois donné.
     *
     * @param User   $user L'utilisateur
     * @param string $month Le mois au format 'Y-m'
     * @return Payment|null Le paiement trouvé ou null s'il n'existe pas
     */
    public function findPaymentByUserAndMonth(User $user, string $month): ?Payment
    {
        $beginOfMonth = new \DateTimeImmutable($month . '-01');
        $endOfMonth = $beginOfMonth->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('p')
            ->where('p.users = :user')
            ->andWhere('p.datePaiement BETWEEN :beginOfMonth AND :endOfMonth')
            ->setParameter('user', $user)
            ->setParameter('beginOfMonth', $beginOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a payment by user and month.
     *
     * @param User $user
     * @param string $monthYear
     * @return Payment|null
     */
    public function findPaymentByUserAndMonthnow($user, $monthYear)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.users = :user')
            ->andWhere('p.datePaiement = :monthYear')
            ->setParameter('user', $user)
            ->setParameter('monthYear', $monthYear)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // verifier si la table est vide
        public function isPaymentTableEmpty(): bool
    {
        $query = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery();

        $count = $query->getSingleScalarResult();

        return $count === 0;
    }

    public function findPaymentNombre(): int
{
    $query = $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->andWhere('p.isVerifier = :isVerifier')
        ->setParameter('isVerifier', false)
        ->getQuery();

    return (int) $query->getSingleScalarResult();
}



    //    /**
    //     * @return Payment[] Returns an array of Payment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Payment
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
