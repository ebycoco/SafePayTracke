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
use Symfony\Component\Security\Http\Attribute\IsGranted;


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
        $paymentsNombre = $paymentRepository->findPaymentNombre();

        $totalPaiements = $paymentRepository->count(['users' => $utilisateurConnecte]);
        // Calculer le nombre total de pages
        $totalPages = ceil($totalPaiements / $limit);
        // dd($page, $totalPages);
        return $this->render('profile/profile.html.twig', [
            'paiements' => $paiements,
            'totalPages' => $totalPages,
            'paymentsNombre' => $paymentsNombre,
            'currentPage' => $page
        ]);
    }

    #[Route('/profile/change-pass', name: 'app_profile_pass_change')]
    public function changePasse(
        Request $request,
        UserRepository $usersRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        SendMailService $mail
    ):Response
    {
         // Récupérer l'utilisateur connecté
         $utilisateurConnecte = $this->getUser();
         $paymentsNombre = $paymentRepository->findPaymentNombre();
         // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
         if (!$utilisateurConnecte) {
             return $this->redirectToRoute('app_login');
         }
 
         
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
                    'no-reply@aroapartners.net',
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
            'paymentsNombre' => $paymentsNombre,
            'requestPassForm' => $form
        ]);
    }

    #[Route('/modifier-profil', name: 'modifier_profil', methods: ['GET', 'POST'])]
public function modifierProfil(
    Request $request,
    PaymentRepository $paymentRepository,
    EntityManagerInterface $entityManager
): Response {
    // Récupérer l'utilisateur connecté
    $user = $this->getUser();
    
    // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Récupérer les informations supplémentaires
    $NomDeSociete = $user->getNomDeSociete();
    $paymentsNombre = $paymentRepository->findPaymentNombre();

    // Créer et gérer le formulaire
    $form = $this->createForm(UserModiType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur s\'est produite lors de la mise à jour de votre profil.');
            // Log the error message for debugging
            error_log($e->getMessage());
        }
    }

    return $this->render('profile/modifier_profil.html.twig', [
        'user' => $user,
        'NomDeSociete' => $NomDeSociete,
        'paymentsNombre' => $paymentsNombre,
        'form' => $form->createView(),
    ]);
}



    #[Route('/paiement', name: 'app_paiement')]
    public function paiement(
        Request $request,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        UserRepository $userRepository
    ):Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
    // Obtenez l'utilisateur actuellement connecté
    $utilisateurConnecte = $this->getUser();
    // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
    if (!$utilisateurConnecte) {
        return $this->redirectToRoute('app_login');
    }
    // Vérifier si l'utilisateur a le rôle 'ROLE_LOCATEUR'
    if (!$this->isGranted('ROLE_LOCATEUR')) {
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
    }

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
            $dateDuRetatPaiement = $form->getData()->getDatePaiement();

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
                    $this->addFlash('warning', "Vous ne pouvez pas fait un paiement Normal car vous n'avez pas selectionné ce mois de " . $dateDuJour . ". Veuillez choisir autre type de paiement");
                    return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                }
                $this->handleNormalPayment($entityManager, $paymentRepository, $userRepository, $paiements, $payment, $montantAPayer, $userId, $solde, $moisActuel, $annee, $mois,$dateDuRetatPaiement);
            } elseif ($typePaiement == "Retard") {
                $dateDuJour = date("F Y");
                $dateDuPaiement = $datePaiement->format('F Y');
                $dateDuRetatPaiement = $form->getData()->getDatePaiement();

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
                    $this->addFlash('warning', "Vous ne pouvez pas payé un retard car nous somme déjà dans le mois de " . $dateDuPaiement . ". Veuillez sélectionner un autre mois");
                    return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                } elseif (($mois[$moisDuPaiement] > $mois[$moisDuJour])) {
                    $this->addFlash('warning', "Vous ne pouvez pas payé un retard pour ce mois de " . $dateDuPaiement . " car ce mois n'est pas encore arrivé. Veuillez sélectionner un autre type de paiement");
                    return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                } else {
                    $retardMois = $paymentRepository->findPayemntMoisSelectionne($userId, $annee, $moisActuel);
                    if (!empty($retardMois)) {
                        $payment = $retardMois[0]; // On prend le premier élément du tableau
                        $paymentId = $payment->getId(); // On accède à l'id de l'objet Payment
                        $moisPr = $payment->getDatePaiement()->format('F');
                        // Tableau associatif pour mapper les mois en anglais aux mois en français
                        $moisMapping = [
                            "January" => "de Janvier",
                            "February" => "de Février",
                            "March" => "de Mars",
                            "April" => "d'Avril",
                            "May" => "de Mai",
                            "June" => "de Juin",
                            "July" => "de Juillet",
                            "August" => "d'Août",
                            "September" => "de Septembre",
                            "October" => "d'Octobre",
                            "November" => "de Novembre",
                            "December" => "de Décembre"
                        ];
                        // Utilisation du tableau pour obtenir le mois en français
                        if (array_key_exists($moisPr, $moisMapping)) {
                            $moisFlash = $moisMapping[$moisPr];
                            $this->addFlash('warning', "Veuillez effectuer votre retard " . $moisFlash . " ici.");
                            return $this->redirectToRoute('app_retard_paiement', ["id"=>$paymentId], Response::HTTP_SEE_OTHER);
                        }
                    }
                    $this->handleLatePayment($entityManager, $paymentRepository,$userRepository, $payment, $montantAPayer, $userId, $moisActuel, $annee, $mois,$dateDuPaiement,$dateDuRetatPaiement);
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
                 foreach ($findPaymentsByUserAll as $paiement) {
                    $solde = $paiement->getSolde(); // Assurez-vous que la méthode getSolde() existe dans votre classe Payment
                    if ($solde !== null) {
                        $lastNonNullSolde = $solde;
                    }
                }

                if ($mois[$moisDuPaiement] == $mois[$moisDuJour]) {
                    $this->addFlash('warning', "Vous ne pouvez pas anticiper car nous somme déjà dans le mois de " . $dateDuPaiement . ". Veuillez sélectionner un autre mois");
                    return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                } elseif ($mois[$moisDuPaiement] < $mois[$moisDuJour]) {
                    $this->addFlash('warning', "Vous ne pouvez pas anticiper ce mois de " . $dateDuPaiement . " car ce mois est passé. Veuillez sélectionner un autre type de paiement");
                    return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
                } else {
                    $datePaiement = $form->getData()->getDatePaiement();
                    $this->handleAdvancePayment($entityManager, $paymentRepository, $payment, $montantAPayer, $userId, $moisActuel, $annee, $lastNonNullSolde,$datePaiement);
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
        'paymentsNombre' => $paymentsNombre,
        'NomDeSociete' => $NomDeSociete,
        'paiementsByUser' => $paiementsByUser,
        'findPaymentsByUserAll' => $findPaymentsByUserAll,
        'form' => $form,
    ]); 

    }

    #[Route('/paiement/{id}/edit', name: 'app_edit_paiement')]
    public function editPaiement(
        PaymentRepository $paymentRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment
        ): Response
    {
        $utilisateurConnecte = $this->getUser();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        }

         // Vérifier si l'utilisateur a le rôle 'ROLE_LOCATEUR'
        if (!$this->isGranted('ROLE_LOCATEUR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        $NomDeSociete = $utilisateurConnecte->getNomDeSociete();
        $paymentsNombre = $paymentRepository->findPaymentNombre();

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

        // Rendre le formulaire avec les données
        return $this->render('profile/edit_paiement.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'payment' => $payment,
            'NomDeSociete' => $NomDeSociete,
            'form' => $form,
        ]);
    }

    #[Route('/paiement/{id}/retard', name: 'app_retard_paiement')]
    public function retardPaiement(
        PaymentRepository $paymentRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment
    ): Response {
        $utilisateurConnecte = $this->getUser();
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si l'utilisateur a le rôle 'ROLE_LOCATEUR'
        if (!$this->isGranted('ROLE_LOCATEUR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
        }

        $NomDeSociete = $utilisateurConnecte->getNomDeSociete();

        // Créer le formulaire en utilisant l'entité existante
        $form = $this->createForm(PaymentRetardEditType::class, $payment);
        $form->handleRequest($request);
        $montantRestant = $payment->getMontantRestant();

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $montantAPayer = $form->get('montantAPayer')->getData();
                $totalMontantPayer = $payment->getTotalMontantPayer() + $montantAPayer;
                $montantPayerPrecedent = $payment->getMontantPrevu() - $montantRestant;
                $montantAPayerNouveau = $montantPayerPrecedent + $montantAPayer;

                // Vérifier si le montant est trop élevé
                if ($montantAPayer > $montantRestant) {
                    $this->addFlash('warning', 'Attention, le montant renseigné est trop élevé.');
                    return $this->redirectToRoute('app_paiement', [], Response::HTTP_SEE_OTHER);
                }
                $avancePaiement= $payment->getAvancePaiement() + $montantAPayer;

                // Mettre à jour les informations du paiement
                $payment->setTotalMontantPayer($totalMontantPayer);
                $payment->setMontantAPayer($montantAPayerNouveau);
                $payment->setMontantSaisir($montantAPayer);
                $payment->setAvancePaiement($avancePaiement);
                $payment->setVerifier(false);
                $payment->setVisibilite(true);
                $payment->setStatus("en attente");
                $payment->setTypePaiement("Retard");

                $entityManager->flush();

                $this->addFlash('success', 'Votre paiement a été ajouté avec succès.');
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // En cas d'erreur, annuler la transaction et afficher un message d'erreur
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement du paiement : ' . $e->getMessage());
            }
        }

        return $this->render('profile/retard_paiement.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'payment' => $payment,
            'NomDeSociete' => $NomDeSociete,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/paiement/mes-retard/', name: 'app_mes_retard_paiement')]
    public function mesretardPaiement(PaymentRepository $paymentRepository, Request $request): Response
    {
        // Récupérer l'utilisateur connecté
        $utilisateurConnecte = $this->getUser();
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        }
        // Vérifier si l'utilisateur a le rôle 'ROLE_LOCATEUR'
        if (!$this->isGranted('ROLE_LOCATEUR')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
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
            'paymentsNombre' => $paymentsNombre,
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
        $mois,
        $dateDuRetatPaiement
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
                    $paiementLocateurCeMoisRe = $paymentRepository->findSecondLatestDEPaymentByUserCi($locateur);

                    if (!$paiementLocateur) {
                        if ($locateur != $this->getUser()) {
                            $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement();
                            $solde = $paiementLocateurCeMoisRe->getSolde();
                            $montantPrevu = $paiementLocateurCeMoisRe->getMontantPrevu();
                            if ($avancePaiement == 0) {
                                $avancePaiement = 0;
                            } else {
                                $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement() + $montantPrevu;
                            }
                        } else {
                            if ($paiementLocateurCeMoisRe->getAvancePaiement()==0) {
                                $avancePaiement = 0;
                            } else {
                                $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement() - $montantAPayer;
                            }
                        }
                        // Créer un nouveau paiement avec une valeur par défaut
                        $nouveauPaiement = new Payment();
                        $nouveauPaiement->setUsers($locateur);
                        $nouveauPaiement->setMontantAPayer(0);
                        $nouveauPaiement->setTotalMontantPayer(0);
                        $nouveauPaiement->setMontantSaisir(0);
                        $nouveauPaiement->setSolde($solde);
                        $nouveauPaiement->setMontantRestant($montantPrevu);
                        $nouveauPaiement->setMontantPrevu($montantPrevu);
                        $nouveauPaiement->setAvancePaiement($avancePaiement);
                        // Formater la date pour obtenir le 01 du mois précédent
                        $dateMoisPrecedent01 = \DateTime::createFromFormat('Y-m-d', $dateMoisPrecedent->format('Y-m-01'));
                        $nouveauPaiement->setDatePaiement($dateMoisPrecedent01);
                        $nouveauPaiement->setRecuDePaiement("default.png");
                        $nouveauPaiement->setStatus("Retard");
                        $nouveauPaiement->setTypePaiement("Non payé");
                        $nouveauPaiement->setVerifier(false);
                        // Persister le nouveau paiement dans la base de données
                        $entityManager->persist($nouveauPaiement);
                    }else {
                        // Vérifier si les utilisateurs a effectué un paiement pour le mois selectionné
                        $paiementLocateurCeMois = $paymentRepository->findPaymentByUserAndMonthnow($locateur, $dateMoisActuel->format('Y-m'));
                        $paiementLocateurCeMoisRe = $paymentRepository->findSecondLatestDEPaymentByUserCi($locateur);

                        if (!$paiementLocateurCeMois) {
                            if ($locateur != $this->getUser()) {
                                $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement();
                                $solde = $paiementLocateurCeMoisRe->getSolde();
                                $montantPrevu = $paiementLocateurCeMoisRe->getMontantPrevu();
                                if ($avancePaiement == 0) {
                                    $avancePaiement = 0;
                                } else {
                                    if ($paiementLocateurCeMoisRe->getAvancePaiement()<0) {
                                        $avancePaiement = - (($montantPrevu)) - (- $paiementLocateurCeMoisRe->getAvancePaiement());
                                        $solde = $montantPrevu + $solde;
                                    } else {
                                        $avancePaiement =  $paiementLocateurCeMoisRe->getAvancePaiement() - $montantPrevu;
                                        $solde = $montantPrevu + $solde;
                                    }
                                }
                                // Formater la date pour obtenir le 01 du mois précédent
                                $dateDuRetatPaiement01 = \DateTime::createFromFormat('Y-m-d', $dateDuRetatPaiement->format('Y-m-01'));
                                // Créer un nouveau paiement avec une valeur par défaut
                                $nouveauPaiement = new Payment();
                                $nouveauPaiement->setUsers($locateur);
                                $nouveauPaiement->setMontantAPayer(0);
                                $nouveauPaiement->setTotalMontantPayer(0);
                                $nouveauPaiement->setMontantSaisir(0);
                                $nouveauPaiement->setSolde($solde);
                                $nouveauPaiement->setMontantPrevu($montantPrevu);
                                $nouveauPaiement->setMontantRestant($montantPrevu);
                                $nouveauPaiement->setAvancePaiement($avancePaiement);
                                $nouveauPaiement->setDatePaiement($dateDuRetatPaiement01);
                                $nouveauPaiement->setRecuDePaiement("default.png");
                                $nouveauPaiement->setStatus("Non payé");
                                $nouveauPaiement->setTypePaiement("Retard");
                                $nouveauPaiement->setVerifier(true);
                                // Persister le nouveau paiement dans la base de données
                                $entityManager->persist($nouveauPaiement);
                            }
                            if ($locateur == $this->getUser()) {
                                $soldeUSER = $paiementLocateurCeMoisRe->getSolde();
                                $avancePaiementUSER = $paiementLocateurCeMoisRe->getAvancePaiement();
                                $soldeUSER = $soldeUSER - $montantAPayer;

                                if ($soldeUSER >= $montantAPayer) {
                                    $soldeUSER = $soldeUSER - $montantAPayer;
                                } else {
                                    $soldeUSER = 0;
                                }

                            }

                        }
                    }

                }

                // Exécuter les opérations de persistance
                $entityManager->flush();

            }
            $avancePaiement = $avancePaiementUSER;
            $solde = $soldeUSER;
            $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser(),$avancePaiement,$solde);
            $this->addFlash('success', "Votre paiement a été effectué avec succès !");
        } else {
            $sommeMontantsRestants = array_sum(array_map(fn($paiement) => $paiement->getMontantRestant(), $verifPaiements));
            // Convertir $moisActuel en objet DateTime
            $dateMoisActuel = \DateTime::createFromFormat('m', $moisActuel);

            // Vérifier si la conversion a réussi
            if (!$dateMoisActuel) {
                throw new \Exception("Format de date incorrect pour \$moisActuel");
            }
            // Modifier la date pour obtenir le mois précédent
            $dateMoisPrecedent = $dateMoisActuel->modify('first day of last month');
            $moisP = $dateMoisPrecedent->format("m"); 
            $anneeP = $dateMoisPrecedent->format('Y');
            // Afficher le dernier solde non nul
            $paiementLocateurCeMoisRe = $paymentRepository->findPaymentsByUserPrecedent($userId,$anneeP,$moisP);
            if ($sommeMontantsRestants == 0) {
                $this->addFlash('info', "Vous ne pouvez pas car vous avez soldé pour le mois sélectionné !");
            } else {
                $differentMontant = $sommeMontantsRestants - $montantAPayer;
                if ($paiementLocateurCeMoisRe->getAvancePaiement()==0) {
                    $avancePaiement = 0;
                } else {
                    $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement();
                }
                if ($differentMontant < 0) {
                    $this->addFlash('warning', "Il vous reste à payer {$sommeMontantsRestants} Fcfa. Veuillez reprendre !");
                } else {
                    $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser(),$avancePaiement,$solde);
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
        UserRepository $userRepository,
        Payment $payment,
        $montantAPayer,
        $userId,
        $moisActuel,
        $annee,
        $mois,
        $dateDuPaiement,
        $dateDuRetatPaiement
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
                    $paiementLocateurCeMoisRe = $paymentRepository->findSecondLatestDEPaymentByUserCi($locateur);
                    if (!$paiementLocateur) {
                        if ($locateur != $this->getUser()) {
                            $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement();
                            $solde = $paiementLocateurCeMoisRe->getSolde();
                            $montantPrevu = $paiementLocateurCeMoisRe->getMontantPrevu();
                            if ($avancePaiement == 0) {
                                $avancePaiement = 0;
                            } else {
                                if ($paiementLocateurCeMoisRe->getAvancePaiement()<0) {
                                    $avancePaiement = - (($montantPrevu)) - (- $paiementLocateurCeMoisRe->getAvancePaiement());
                                    $solde = $montantPrevu + $solde;
                                } else {
                                    $avancePaiement =  $paiementLocateurCeMoisRe->getAvancePaiement() - $montantPrevu;
                                    $solde = $montantPrevu + $solde;
                                }
                            }
                            // Créer un nouveau paiement avec une valeur par défaut
                            $nouveauPaiement = new Payment();
                            $nouveauPaiement->setUsers($locateur);
                            $nouveauPaiement->setMontantAPayer(0);
                            $nouveauPaiement->setTotalMontantPayer(0);
                            $nouveauPaiement->setMontantSaisir(0);
                            $nouveauPaiement->setSolde($solde);
                            $nouveauPaiement->setMontantPrevu($montantPrevu);
                            $nouveauPaiement->setMontantRestant($montantPrevu);
                            $nouveauPaiement->setAvancePaiement($avancePaiement);
                            // Formater la date pour obtenir le 01 du mois précédent
                            $dateDuRetatPaiement01 = \DateTime::createFromFormat('Y-m-d', $dateDuRetatPaiement->format('Y-m-01'));
                            $nouveauPaiement->setDatePaiement($dateDuRetatPaiement01); 
                            $nouveauPaiement->setRecuDePaiement("default.png");
                            $nouveauPaiement->setStatus(" Non payé");
                            $nouveauPaiement->setTypePaiement("Retard");
                            $nouveauPaiement->setVerifier(true);
                            // Persister le nouveau paiement dans la base de données
                            $entityManager->persist($nouveauPaiement);
                        } 
                        if ($locateur == $this->getUser()) {
                            $soldeUSER = $paiementLocateurCeMoisRe->getSolde();
                            if ($paiementLocateurCeMoisRe->getAvancePaiement()==0) {
                                $avancePaiementUSER = 0;
                            }elseif ($paiementLocateurCeMoisRe->getAvancePaiement()<0) {
                                $avancePaiementUSER = $paiementLocateurCeMoisRe->getAvancePaiement() + $montantAPayer;
                            }
                             else {
                                $avancePaiementUSER = $paiementLocateurCeMoisRe->getAvancePaiement() - $montantAPayer;
                            }
                            $soldeUSER = $soldeUSER - $montantAPayer;

                            if ($soldeUSER >= $montantAPayer) {
                                $soldeUSER = $soldeUSER - $montantAPayer;
                            } else {
                                $soldeUSER = 0;
                            }

                        }

                    }else {
                        // Vérifier si les utilisateurs a effectué un paiement pour le mois selectionné
                        $paiementLocateurCeMois = $paymentRepository->findPaymentByUserAndMonthnow($locateur, $dateDuRetatPaiement->format('Y-m'));
                        $paiementLocateurCeMoisRe = $paymentRepository->findSecondLatestDEPaymentByUserCi($locateur);

                        if (!$paiementLocateurCeMois) {
                            if ($locateur != $this->getUser()) {
                                $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement();
                                $solde = $paiementLocateurCeMoisRe->getSolde();
                                $montantPrevu = $paiementLocateurCeMoisRe->getMontantPrevu();
                                if ($avancePaiement == 0) {
                                    $avancePaiement = 0;
                                } else {
                                    if ($paiementLocateurCeMoisRe->getAvancePaiement()<0) {
                                        $avancePaiement = - (($montantPrevu)) - (- $paiementLocateurCeMoisRe->getAvancePaiement());
                                        $solde = $montantPrevu + $solde;
                                    } else {
                                        $avancePaiement =  $paiementLocateurCeMoisRe->getAvancePaiement() - $montantPrevu;
                                        $solde = $montantPrevu + $solde;
                                    }
                                }
                                // Formater la date pour obtenir le 01 du mois précédent
                                $dateDuRetatPaiement01 = \DateTime::createFromFormat('Y-m-d', $dateDuRetatPaiement->format('Y-m-01'));
                                // Créer un nouveau paiement avec une valeur par défaut
                                $nouveauPaiement = new Payment();
                                $nouveauPaiement->setUsers($locateur);
                                $nouveauPaiement->setMontantAPayer(0);
                                $nouveauPaiement->setTotalMontantPayer(0);
                                $nouveauPaiement->setMontantSaisir(0);
                                $nouveauPaiement->setSolde($solde);
                                $nouveauPaiement->setMontantPrevu($montantPrevu);
                                $nouveauPaiement->setMontantRestant($montantPrevu);
                                $nouveauPaiement->setAvancePaiement($avancePaiement);
                                $nouveauPaiement->setDatePaiement($dateDuRetatPaiement01);
                                $nouveauPaiement->setRecuDePaiement("default.png");
                                $nouveauPaiement->setStatus("Non payé");
                                $nouveauPaiement->setTypePaiement("Retard");
                                $nouveauPaiement->setVerifier(true);
                                // Persister le nouveau paiement dans la base de données
                                $entityManager->persist($nouveauPaiement);
                            }
                            if ($locateur == $this->getUser()) {
                                $soldeUSER = $paiementLocateurCeMoisRe->getSolde();
                                if ($paiementLocateurCeMoisRe->getAvancePaiement()==0) {
                                    $avancePaiementUSER = 0;
                                }elseif ($paiementLocateurCeMoisRe->getAvancePaiement()<0) {
                                    $avancePaiementUSER = $paiementLocateurCeMoisRe->getAvancePaiement() + $montantAPayer;
                                }
                                 else {
                                    $avancePaiementUSER = $paiementLocateurCeMoisRe->getAvancePaiement() - $montantAPayer;
                                }
                                $soldeUSER = $soldeUSER - $montantAPayer;

                                if ($soldeUSER >= $montantAPayer) {
                                    $soldeUSER = $soldeUSER - $montantAPayer;
                                } else {
                                    $soldeUSER = 0;
                                }

                            }

                        }
                    }
                }
                // Exécuter les opérations de persistance
                $entityManager->flush();

            }
            $avancePaiement = $avancePaiementUSER;
            $solde = $soldeUSER;

            $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser(),$avancePaiement,$solde);
            $this->addFlash('success', "Votre paiement a été effectué avec succès !");
        } else {
            $sommeMontantsRestants = array_sum(array_map(fn($paiement) => $paiement->getMontantRestant(), $verifPaiements));
            // Convertir $moisActuel en objet DateTime
            $dateMoisActuel = \DateTime::createFromFormat('m', $moisActuel);

            // Vérifier si la conversion a réussi
            if (!$dateMoisActuel) {
                throw new \Exception("Format de date incorrect pour \$moisActuel");
            }
            // Modifier la date pour obtenir le mois précédent
            $dateMoisPrecedent = $dateMoisActuel->modify('first day of last month');
            $moisP = $dateMoisPrecedent->format("m"); 
            $anneeP = $dateMoisPrecedent->format('Y');
            // Afficher le dernier solde non nul
            $paiementLocateurCeMoisRe = $paymentRepository->findPaymentsByUserPrecedent($userId,$anneeP,$moisP);
            
            if ($sommeMontantsRestants == 0) {
                $this->addFlash('info', "Vous ne pouvez pas car vous avez soldé pour le mois sélectionné !");
            } else {
                $differentMontant = $sommeMontantsRestants - $montantAPayer;
                if ($paiementLocateurCeMoisRe->getAvancePaiement()==0) {
                    $avancePaiement = 0;
                } else {
                    $avancePaiement = $paiementLocateurCeMoisRe->getAvancePaiement() - $montantAPayer;
                    $solde = $paiementLocateurCeMoisRe->getSolde();
                }

                if ($differentMontant < 0) {
                    $this->addFlash('warning', "Il vous reste à payer {$sommeMontantsRestants} Fcfa. Veuillez reprendre !");
                } else {
                    $this->persistPayment($entityManager, $payment, $montantAPayer, $this->getUser(),$avancePaiement,$solde);
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
        $lastNonNullSolde,
        $datePaiement
    ) {
        $verifPaiements = $paymentRepository->findPaymentsByUserAndMonthTout($userId, $annee, $moisActuel);
        // Convertir $moisActuel en objet DateTime
        $dateMoisActuel = \DateTime::createFromFormat('m', $moisActuel);

        // Vérifier si la conversion a réussi
        if (!$dateMoisActuel) {
            throw new \Exception("Format de date incorrect pour \$moisActuel");
        }
        // Modifier la date pour obtenir le mois précédent
        $dateMoisPrecedent = $dateMoisActuel->modify('first day of last month');
        $moisP = $dateMoisPrecedent->format("m");
        $anneeP = $dateMoisPrecedent->format('Y');
        // Afficher le dernier solde non nul
        $paiementLocateurCeMoisRe = $paymentRepository->findPaymentsByUserPrecedent($userId,$anneeP,$moisP);
        $avancePaiement = $montantAPayer + $paiementLocateurCeMoisRe->getAvancePaiement() ;
        $solde=0;

        if (empty($verifPaiements)) {
            if ($lastNonNullSolde > 0 || $lastNonNullSolde === null) {
                $this->addFlash('warning', "Attention, vous ne pouvez pas car vous n'avez jamais fait de paiement ou votre dernier paiement n'a pas encore été validé !");
            } else { 
                $payment->setMontantAPayer($montantAPayer); 
                $payment->setDatePaiement($datePaiement); 
                $this->persistPayment($entityManager,$payment,$montantAPayer,$this->getUser(),$avancePaiement,$solde);
                $this->addFlash('success', "Merci pour votre paiement anticipé !");
            }
        } else {
            $this->addFlash('warning', "Attention, vous ne pouvez pas car vous n'avez pas de retard !");
        }

    }

    /**
     * Persister le paiement dans la base de données.
     */
    private function persistPayment(EntityManagerInterface $entityManager, Payment $payment, $montantAPayer, $user,$avancePaiement,$solde) {
        
        $avance = $avancePaiement;
        $payment->setUsers($user);
        $payment->setTotalMontantPayer($montantAPayer);
        $payment->setMontantSaisir($montantAPayer);
        $payment->setVerifier(false);
        $payment->setAvancePaiement($avance);
        $payment->setSolde($solde);
        $payment->setVisibilite(true);
        $payment->setVerifier(false);
        $entityManager->persist($payment);
        $entityManager->flush();
    }

}