<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Form\UserModiType;
use App\Form\PaymentRetardType;
use App\Service\SendMailService;
use App\Repository\UserRepository;
use App\Form\PaymentRetardEditType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;


class ProfileController extends AbstractController
{

    #[Route('/profile', name: 'app_profile')]
    public function profile(PaymentRepository $paymentRepository,Request $request): Response
    {
        // Récupérer l'utilisateur connecté
        $utilisateurConnecte = $this->getUser();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        }
        // Récupérer le numéro de la page à afficher
        $page = $request->query->getInt('page', 1); // Par défaut, la première page est affichée

        // Définir le nombre d'éléments par page
        $limit = 4;

        // Récupérer tous les paiements de l'utilisateur connecté par ordre décroissant de la date de paiement
        $paiements = $paymentRepository->findPaymentUserPaginated($utilisateurConnecte, $page, $limit);

        $totalPaiements = $paymentRepository->count(['users' => $utilisateurConnecte]);
        // Calculer le nombre total de pages
        $totalPages = ceil($totalPaiements / $limit);
        return $this->render('profile/profile.html.twig', [
            'paiements' => $paiements,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/profile/change-pass', name: 'app_profile_pass_change')]
    public function changePasse(
        Request $request,
        UserRepository $usersRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        SendMailService $mail
    ):Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //On va chercher l'utilisateur par son email
            $user = $usersRepository->findOneByEmail($form->get('email')->getData());
            $emailConnecter = $this->getUser()->getEmail();
            $emailSaisir = $user->getEmail();

            if($emailConnecter != $emailSaisir){
                $this->addFlash('danger', 'Veuillez saisir votre address Email');
                return $this->redirectToRoute('app_profile_pass_change');
            }

            // On vérifie si on a un utilisateur
            if($user){
                // On génère un token de réinitialisation
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $entityManager->persist($user);
                $entityManager->flush();

                // On génère un lien de réinitialisation du mot de passe
                $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                // On crée les données du mail
                $context = compact('url', 'user');

                // Envoi du mail
                $mail->send(
                    'no-reply@safepaytracker.com',
                    $user->getEmail(),
                    'Réinitialisation de mot de passe',
                    'password_reset',
                    $context
                );

                $this->addFlash('success', 'Email envoyé avec succès');
                return $this->redirectToRoute('app_profile');
            }
            // $user est null
            $this->addFlash('danger', 'Un problème est survenu');
            return $this->redirectToRoute('app_profile_pass_change');
        }
        return $this->render('profile/change_pass.html.twig', [
            'requestPassForm' => $form
        ]);
    }

    #[Route('/modifier-profil', name: 'modifier_profil',methods: ['GET', 'POST'])]
    public function modifierProfil(
        Request $request,
        EntityManagerInterface $entityManager
        ): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur actuellement connecté
        $utilisateurConnecte = $this->getUser();
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        $form = $this->createForm(UserModiType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/modifier_profil.html.twig', [
            'user' => $user,
            'NomDeSociete'=> $NomDeSociete,
            'form' => $form,
        ]);
    }

    #[Route('/paiement', name: 'app_paiement')]
    public function paiement(
        Request $request,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        UserRepository $userRepository,
    ): Response {
        // Obtenez l'utilisateur actuellement connecté
        $utilisateurConnecte = $this->getUser();
        $NomDeSociete = $utilisateurConnecte->getNomDeSociete();
        $userId = $utilisateurConnecte->getId();
        // Récupérer les paiements de l'utilisateur connecté
        $paiementsByUser = $paymentRepository->findPaymentsByUser($userId);
        $findPaymentsByUserAll = $paymentRepository->findPaymentsByUserAll($userId);
        // Créer un nouveau paiement et le formulaire associé
        $payment = new Payment();
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Commence une transaction
            $entityManager->beginTransaction();
            try {
                // Récupérer les données du formulaire
                $montantAPayer = $form->getData()->getMontantAPayer();
                $datePaiement = $form->getData()->getDatePaiement();
                $moisActuel = $datePaiement->format('m');
                $mois = $datePaiement->format('F');
                $annee = $datePaiement->format('Y');
                $typePaiement = $form->getData()->getTypePaiement();
                // Récupérer les paiements de l'utilisateur pour le mois sélectionné
                $paiements = $paymentRepository->findPaymentsByUserAndMonth($userId, $annee, $moisActuel);
                $solde = empty($paiements) ? null : $paiements->getSolde();
                // Vérification et enregistrement des paiements
                if ($typePaiement == "Normal") {

                    $dateDuJour = date("F Y");
                    $dateDuPaiement = $datePaiement->format('F Y');

                    // Mapping des mois à leurs positions numériques dans l'année
                    $mois = [
                        'january' => 1,
                        'february' => 2,
                        'march' => 3,
                        'april' => 4,
                        'may' => 5,
                        'june' => 6,
                        'july' => 7,
                        'august' => 8,
                        'september' => 9,
                        'october' => 10,
                        'november' => 11,
                        'december' => 12
                    ];

                    // Extraire seulement le mois de chaque date
                    list($moisDuPaiement) = explode(" ", strtolower($dateDuPaiement));
                    list($moisDuJour) = explode(" ", strtolower($dateDuJour));

                    if ($mois[$moisDuPaiement] != $mois[$moisDuJour]) {
                        $this->addFlash('warning', "Vous ne pouvez pas fait un paiement Normal car vous n'avez pas selectionné ce mois de " .$dateDuJour .". Veuillez choisir autre type de paiement");
                        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                    }
                    $this->handleNormalPayment($entityManager, $paymentRepository, $userRepository, $paiements, $payment, $montantAPayer, $userId, $solde, $moisActuel, $annee, $mois);
                } elseif ($typePaiement == "Retard") {

                    $dateDuJour = date("F Y");
                    $dateDuPaiement = $datePaiement->format('F Y');

                    // Mapping des mois à leurs positions numériques dans l'année
                    $mois = [
                        'january' => 1,
                        'february' => 2,
                        'march' => 3,
                        'april' => 4,
                        'may' => 5,
                        'june' => 6,
                        'july' => 7,
                        'august' => 8,
                        'september' => 9,
                        'october' => 10,
                        'november' => 11,
                        'december' => 12
                    ];

                    // Extraire seulement le mois de chaque date
                    list($moisDuPaiement) = explode(" ", strtolower($dateDuPaiement));
                    list($moisDuJour) = explode(" ", strtolower($dateDuJour));

                    if ($mois[$moisDuPaiement] == $mois[$moisDuJour]) {
                        $this->addFlash('warning', "Vous ne pouvez pas payé un retard car nous somme déja dans le mois de " .$dateDuPaiement .". Veuillez selectionner autre mois");
                        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                    }elseif (($mois[$moisDuPaiement] > $mois[$moisDuJour])) {
                        $this->addFlash('warning', "Vous ne pouvez pas payé un retard pour ce mois de " .$dateDuPaiement ." car ce mois n'est pas encore arrivé. Veuillez selectionner autre type de paiement");
                        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                    }else {
                    $this->handleLatePayment($entityManager, $paymentRepository, $payment, $montantAPayer, $userId, $moisActuel, $annee, $mois);
                    }

                } elseif ($typePaiement == "Anticiper") {
                    $dateDuJour = date("F Y");
                    $dateDuPaiement = $datePaiement->format('F Y');

                    // Mapping des mois à leurs positions numériques dans l'année
                    $mois = [
                        'january' => 1,
                        'february' => 2,
                        'march' => 3,
                        'april' => 4,
                        'may' => 5,
                        'june' => 6,
                        'july' => 7,
                        'august' => 8,
                        'september' => 9,
                        'october' => 10,
                        'november' => 11,
                        'december' => 12
                    ];

                    // Extraire seulement le mois de chaque date
                    list($moisDuPaiement) = explode(" ", strtolower($dateDuPaiement));
                    list($moisDuJour) = explode(" ", strtolower($dateDuJour));

                    $lastNonNullSolde = null;

                    // Parcourir les objets Payment pour trouver le dernier solde non nul
                    foreach ($findPaymentsByUserAll as $payment) {
                        $solde = $payment->getSolde(); // Assurez-vous que la méthode getSolde() existe dans votre classe Payment
                        if ($solde !== null) {
                            $lastNonNullSolde = $solde;
                        }
                    }
                    if ($mois[$moisDuPaiement] == $mois[$moisDuJour]) {
                        $this->addFlash('warning', "Vous ne pouvez pas anticiper car nous somme déja dans le mois de " .$dateDuPaiement .". Veuillez selectionner autre mois");
                        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                    }elseif ($mois[$moisDuPaiement] < $mois[$moisDuJour]) {
                        $this->addFlash('warning', "Vous ne pouvez pas anticiper ce mois de " .$dateDuPaiement ." car ce mois est passé. Veuillez selectionner autre type de paiement");
                        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                    }else {
                        $this->handleAdvancePayment($entityManager, $paymentRepository, $payment, $montantAPayer, $userId,$moisActuel,$annee,$lastNonNullSolde);
                    }
                }
                // Valider la transaction
                $entityManager->commit();
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);

            } catch (\Exception $e) {
                // Annuler la transaction en cas d'erreur
                $entityManager->rollback();
                $this->addFlash('danger', "Une erreur est survenue : " . $e->getMessage());
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('profile/paiement.html.twig', [
            'payment' => $payment,
            'NomDeSociete' => $NomDeSociete,
            'paiementsByUser' => $paiementsByUser,
            'findPaymentsByUserAll' => $findPaymentsByUserAll,
            'form' => $form,
        ]);
    }

    #[Route('/paiement/{id}/edit', name: 'app_edit_paiement')]
    public function editPaiement(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment
        ): Response
    {
        $utilisateurConnecte = $this->getUser();
        $NomDeSociete = $utilisateurConnecte->getNomDeSociete();

        // Créer le formulaire en utilisant l'entité existante
        $form = $this->createForm(PaymentRetardEditType::class, $payment);
        $form->handleRequest($request);
        $montantRestant = $payment->getMontantRestant();

        // Début de la transaction
        $entityManager->beginTransaction();

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $montantAPayer = $form->getData()->getMontantAPayer();
                $totalMontantPayer = $payment->getTotalMontantPayer() + $montantAPayer;
                $montantPayerPrecedent = $payment->getMontantPrevu() - $montantRestant;
                $montantAPayerNouveau = $montantPayerPrecedent + $montantAPayer;

                // Vérifier si le montant est trop élevé
                if ($montantAPayer > $montantRestant) {
                    $this->addFlash('warning', 'Attention, le montant renseigné est trop élevé.');
                    return $this->redirectToRoute('app_paiement', [], Response::HTTP_SEE_OTHER);
                }

                // Mettre à jour les informations du paiement
                $payment->setTotalMontantPayer($totalMontantPayer);
                $payment->setMontantAPayer($montantAPayerNouveau);
                $payment->setMontantSaisir($montantAPayer);
                $payment->setVerifier(false);
                $payment->setTypePaiement("Retard");
                $entityManager->flush();

                // Valider la transaction
                $entityManager->commit();

                $this->addFlash('success', 'Votre paiement a été ajouté avec succès.');
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }

            // Définir le champ MontantAPayer à null avant de rendre le formulaire
            $form->get('montantAPayer')->setData(null);
        } catch (\Exception $e) {
            // En cas d'erreur, annuler la transaction et afficher un message d'erreur
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement du paiement : ' . $e->getMessage());
        }

        // Rendre le formulaire avec les données
        return $this->render('profile/edit_paiement.html.twig', [
            'payment' => $payment,
            'NomDeSociete' => $NomDeSociete,
            'form' => $form,
        ]);
    }

    #[Route('/paiement/{id}/retard', name: 'app_retard_paiement')]
    public function retardPaiement(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment
        ): Response
    {
        $utilisateurConnecte = $this->getUser();
        $NomDeSociete = $utilisateurConnecte->getNomDeSociete();

        // Créer le formulaire en utilisant l'entité existante
        // $payments = new Payment();
        $form = $this->createForm(PaymentRetardEditType::class, $payment);
        $form->handleRequest($request);
        $montantRestant = $payment->getMontantRestant();

        // dd($payment);

        // Début de la transaction
        $entityManager->beginTransaction();

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $montantAPayer = $form->getData()->getMontantAPayer();
                $totalMontantPayer = $payment->getTotalMontantPayer() + $montantAPayer;
                $montantPayerPrecedent = $payment->getMontantPrevu() - $montantRestant;
                $montantAPayerNouveau = $montantPayerPrecedent + $montantAPayer;
                // Vérifier si le montant est trop élevé
                if ($montantAPayer > $montantRestant) {
                    $this->addFlash('warning', 'Attention, le montant renseigné est trop élevé.');
                    return $this->redirectToRoute('app_paiement', [], Response::HTTP_SEE_OTHER);
                }
                // Mettre à jour les informations du paiement
                $payment->setTotalMontantPayer($totalMontantPayer);
                $payment->setMontantAPayer($montantAPayerNouveau);
                $payment->setMontantSaisir($montantAPayer);
                $payment->setVerifier(false);
                $payment->setVisibilite(true);
                $payment->setStatus("en attente");
                $payment->setTypePaiement("Retard");
                $entityManager->flush();

                // Valider la transaction
                $entityManager->commit();

                $this->addFlash('success', 'Votre paiement a été ajouté avec succès.');
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }

            // Définir le champ MontantAPayer à null avant de rendre le formulaire
            $form->get('montantAPayer')->setData(null);
        } catch (\Exception $e) {
            // En cas d'erreur, annuler la transaction et afficher un message d'erreur
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement du paiement : ' . $e->getMessage());
        } 
        return $this->render('profile/retard_paiement.html.twig', [
            'payment' => $payment,
            'NomDeSociete' => $NomDeSociete,
            'form' => $form,
        ]);
    }

    #[Route('/paiement/mes-retard/', name: 'app_mes_retard_paiement')]
    public function mesretardPaiement(PaymentRepository $paymentRepository, Request $request): Response
    {
        // Récupérer l'utilisateur connecté
        $utilisateurConnecte = $this->getUser();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        }
        // Récupérer le numéro de la page à afficher
        $page = $request->query->getInt('page', 1); // Par défaut, la première page est affichée
        // Définir le nombre d'éléments par page
        $limit = 4;
        // Récupérer tous les paiements de type "retard" de l'utilisateur connecté par ordre décroissant de la date de paiement
        $paiements = $paymentRepository->findPaymentUserRetardPaginated($utilisateurConnecte, $page, $limit);
        // Compter le nombre total de paiements de type "retard" de l'utilisateur connecté
        $totalPaiements = $paymentRepository->count([
            'users' => $utilisateurConnecte,
            'typePaiement' => 'retard',
            "status" => "Non payé"
        ]); 
        // Calculer le nombre total de pages
        $totalPages = ceil($totalPaiements / $limit);

        return $this->render('profile/mes_retard_paiement.html.twig', [
            'paiements' => $paiements,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    /**
     * Gérer le processus de paiement normal.
     */
    private function handleNormalPayment(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        UserRepository $userRepository,
        $paiements,
        Payment $payment,
        $montantAPayer,
        $userId,
        $solde,
        $moisActuel,
        $annee,
        $mois
    ) {
        $verifPaiements = $paymentRepository->findPaymentsByUserAndMonthTout($userId, $annee, $moisActuel);

        if (empty($verifPaiements)) {
            $etatTable = $paymentRepository->isPaymentTableEmpty();
            // on verifie si la table n'est pas vide
            if(!$etatTable){

                // Convertir $moisActuel en objet DateTime
                $dateMoisActuel = \DateTime::createFromFormat('m', $moisActuel);

                // Vérifier si la conversion a réussi
                if (!$dateMoisActuel) {
                    throw new \Exception("Format de date incorrect pour \$moisActuel");
                }
                // Modifier la date pour obtenir le mois précédent
                $dateMoisPrecedent = $dateMoisActuel->modify('first day of last month');

                // Récupérer tous les utilisateurs ayant le rôle ROLE_LOCATEUR
                $locateurs = $userRepository->findByRole('ROLE_LOCATEUR');

                foreach ($locateurs as $locateur) {
                    // Vérifier si l'utilisateur a effectué un paiement pour le mois précédent
                    $paiementLocateur = $paymentRepository->findPaymentByUserAndMonth($locateur, $dateMoisPrecedent->format('Y-m'));
                    if (!$paiementLocateur) {
                        // Créer un nouveau paiement avec une valeur par défaut
                        $nouveauPaiement = new Payment();
                        $nouveauPaiement->setUsers($locateur);
                        $nouveauPaiement->setMontantAPayer(0);
                        $nouveauPaiement->setTotalMontantPayer(0);
                        $nouveauPaiement->setMontantSaisir(0);
                        // Formater la date pour obtenir le 01 du mois précédent
                        $dateMoisPrecedent01 = \DateTime::createFromFormat('Y-m-d', $dateMoisPrecedent->format('Y-m-01'));
                        $nouveauPaiement->setDatePaiement($dateMoisPrecedent01);
                        $nouveauPaiement->setRecuDePaiement("default.png");
                        $nouveauPaiement->setStatus("Retard");
                        $nouveauPaiement->setTypePaiement("Non payé");
                        $nouveauPaiement->setVerifier(false);
                        // Persister le nouveau paiement dans la base de données
                        $entityManager->persist($nouveauPaiement);
                    }
                }

                // Exécuter les opérations de persistance
                $entityManager->flush();

            }
            $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser());
            $this->addFlash('success', "Votre paiement a été effectué avec succès !");
        } else {
            dd("ici 2");
            $sommeMontantsRestants = array_sum(array_map(fn($paiement) => $paiement->getMontantRestant(), $verifPaiements));
            if ($sommeMontantsRestants == 0) {
                $this->addFlash('info', "Vous ne pouvez pas car vous avez soldé pour le mois sélectionné !");
            } else {
                $differentMontant = $sommeMontantsRestants - $montantAPayer;
                if ($differentMontant < 0) {
                    $this->addFlash('warning', "Il vous reste à payer {$sommeMontantsRestants} Fcfa. Veuillez reprendre !");
                } else {
                    $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser());
                    $this->addFlash('success', "Votre paiement a été effectué avec succès !");
                }
            }
        }
    }

    /**
     * Gérer le processus de paiement en retard.
     */
    private function handleLatePayment(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository, 
        Payment $payment,
        $montantAPayer,
        $userId, 
        $moisActuel,
        $annee,
        $mois
    ) {
        $verifPaiements = $paymentRepository->findPaymentsByUserAndMonthTout($userId, $annee, $moisActuel);
        if (empty($verifPaiements)) {
            $this->addFlash('info', "Attention, vous ne pouvez pas car vous n'avez pas de retard pour le mois selectionné !");
        } else {
            $sommeMontantsRestants = array_sum(array_map(fn($paiement) => $paiement->getMontantRestant(), $verifPaiements));
            if ($sommeMontantsRestants == 0) {
                $this->addFlash('info', "Vous ne pouvez pas car vous avez soldé pour le mois sélectionné !");
            } else {
                $differentMontant = $sommeMontantsRestants - $montantAPayer;
                if ($differentMontant < 0) {
                    $this->addFlash('warning', "Il vous reste à payer {$sommeMontantsRestants} Fcfa. Veuillez reprendre !");
                } else {
                    $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser());
                    $this->addFlash('success', "Votre paiement a été effectué avec succès !");
                }
            }
        }

    }

    /**
     * Gérer le processus de paiement anticipé.
     */
    private function handleAdvancePayment(
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        Payment $payment,
        $montantAPayer,
        $userId,
        $moisActuel,
        $annee,
        $lastNonNullSolde
    ) {
        $verifPaiements = $paymentRepository->findPaymentsByUserAndMonthTout($userId, $annee, $moisActuel);
             // Afficher le dernier solde non nul 
        if (empty($verifPaiements)) {
            if ($lastNonNullSolde > 0 || $lastNonNullSolde === null) {
                $this->addFlash('warning', "Attention, vous ne pouvez pas car vous n'avez jamais fait de paiement ou votre dernier paiement n'a pas encore été validé !");
            } else {
                $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser());
                $this->addFlash('success', "Merci pour votre paiement anticipé !");
            }
        } else {
            $this->addFlash('warning', "Attention, vous ne pouvez pas car vous n'avez pas de retard !");
        }

    }

    /**
     * Persister le paiement dans la base de données.
     */
    private function persistPayment(EntityManagerInterface $entityManager, Payment $payment, $montantAPayer, $user) {
        $payment->setUsers($user);
        $payment->setTotalMontantPayer($montantAPayer);
        $payment->setMontantSaisir($montantAPayer);
        $payment->setVerifier(false);
        $entityManager->persist($payment);
        $entityManager->flush();
    }

}