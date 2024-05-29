<?php

namespace App\Controller;

use DateTimeImmutable;
use IntlDateFormatter;
use App\Entity\Payment;
use App\Entity\PaymentVerification;
use App\Form\PaymentVerificationType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\PaymentVerificationEditType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\PaymentVerificationEditRetardType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_GARDIEN', statusCode: 403, exceptionCode: 10010)]
#[Route('/gardinage', name: 'app_gardinage_')]
class GardinageController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository, Request $request): Response
    {
        //On va chercher le numéro de page dans l'url

        $page = $request->query->getInt('page', 1);
        $payments = $paymentRepository->findPaymentPaginated($page, 4);
        if (empty($payments)) {
            $this->addFlash('info', "Aucun paiement n'est encour...");
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('gardinage/index.html.twig',  [
            'payments' => $payments,

        ]);
    }

    #[Route('/history', name: 'history', methods: ['GET'])]
    public function history(PaymentRepository $paymentRepository, Request $request): Response
    {
        //On va chercher le numéro de page dans l'url

        $page = $request->query->getInt('page', 1);
        $payments = $paymentRepository->findPaymentHistoryPaginated($page, 4);
        if (empty($payments)) {
            $this->addFlash('info', "Aucun paiement n'est encour...");
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('gardinage/history.html.twig',  [
            'payments' => $payments,

        ]);
    }

    #[Route('/payment/hide/{id}', name: 'payment_hide', methods: ['POST'])]
    public function hidePayment($id, EntityManagerInterface $em): JsonResponse
    {
        $payment = $em->getRepository(Payment::class)->find($id);

        if (!$payment) {
            return new JsonResponse(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        $payment->setVisibilite(false);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/payment/voir/{id}', name: 'payment_voir', methods: ['POST'])]
    public function voirPayment($id, EntityManagerInterface $em): JsonResponse
    {
        $payment = $em->getRepository(Payment::class)->find($id);

        if (!$payment) {
            return new JsonResponse(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        $payment->setVisibilite(true);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/verifier/{id}', name: 'verifier', methods: ['GET', 'POST'])]
    public function verifier(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        PaymentRepository $paymentRepository,
    ): Response {

        $paymentVerification = new PaymentVerification();
        $montantPrevuForm = $payment->getMontantPrevu();
        $jsfile = "";
        if (empty($montantPrevuForm)) {
            $form = $this->createForm(PaymentVerificationType::class, $paymentVerification);
            $jsfile = "verif";
        } else {
            $form = $this->createForm(PaymentVerificationEditType::class, $paymentVerification);
            $jsfile = "verifEdit";

        }

        $form->handleRequest($request);
        $user =  $payment->getUsers();
        $dernierPaiement = $paymentRepository->findSecondLatestPaymentByUser($user);
        $montantAPayer = $payment->getMontantAPayer();
        $typePaiement = $payment->getTypePaiement();
        $entrepise = $payment->getUsers()->getNomDeSociete();

        // Vérifier si le dernier paiement existe
        if ($dernierPaiement) {
            $montantRestant = $dernierPaiement->getMontantRestant();
            
        } else {
            // Aucun paiement précédent trouvé, initialiser le montant restant à 0
            $montantRestant = 0;
        }
        

        if ($dernierPaiement) {
            $solde = $dernierPaiement->getSolde();
        } else {
            $solde = null;
        }
        $datePayement = $payment->getDatePaiement();
        // Créer un objet DateTimeImmutable à partir de la date de paiement
        $date = DateTimeImmutable::createFromMutable($datePayement);
        // Définir la locale sur français
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        // Formater la date avec le nom du mois en français
        $dateFormatee = $formatter->format($date);


        // Afficher la date formatée


        if ($form->isSubmitted() && $form->isValid()) {
            $montantRecu = $form->getData()->getmontantRecu();
            if (empty($montantPrevuForm)) {
                $montantPrevu = $form->getData()->getmontantPrevu();
            } else {
                $montantPrevu = $montantPrevuForm;
            }
            $typePaiementG = $form->getData()->getTypePaiement();
            $verifMontantSaisirGardien = $montantPrevu - $montantRecu;
            // verifier le type de paiement
            if ($typePaiement == $typePaiementG) {

                // On verifie s'il n'apas saisir plus que se qu'il était prévu
                if (!($verifMontantSaisirGardien < 0)) {
                    if ($typePaiementG == "Normal") {

                        // Commencer une transaction
                        $entityManager->beginTransaction();

                        try {
                            if (($montantPrevu - $montantRecu) == 0) {
                                // Calcul du nouveau montant restant et du solde
                                $montantRestant = ($montantPrevu - $montantRecu);
                            } else {
                                // Calcul du nouveau montant restant et du solde
                                $montantRestant = ($montantPrevu - $montantRecu) + $montantRestant;
                            }

                            $solde = $montantRestant + $solde;
                            $nouveauSolde = $solde;

                            // Mettre à jour le solde de l'utilisateur
                            $paymentRepository->updateSoldeForUser($user, $nouveauSolde);

                            // Mettre à jour les informations de vérification du paiement
                            $payment->setMontantRestant($montantRestant);
                            $payment->setMontantAPayer($montantRecu);
                            $payment->setTotalMontantPayer($montantRecu);
                            $payment->setMontantSaisir($montantRecu);
                            $payment->setMontantPrevu($montantPrevu);
                            $payment->setVerifier(true);
                            $payment->setPaymentVerification($paymentVerification);

                            // Mettre à jour le statut du paiement
                            if ($montantAPayer == $montantRecu) {
                                $payment->setStatus(($montantPrevu - $montantRecu) == 0 ? "Payé" : "partiel");
                                $message = "La vérification a été effectuée avec succès !";
                            } else {
                                $payment->setStatus("partiel");
                                $message = "La vérification a été effectuée avec succès. Cependant, nous avons remarqué que le montant que l'entreprise a entré diffère de celui que vous avez entré. L'entreprise recevra une notification sur son espace.";
                            }

                            // Mettre à jour la vérification du paiement
                            $paymentVerification->setPayment($payment);
                            $paymentVerification->setMontantPrevu($montantPrevu);

                            // Enregistrer les modifications dans la base de données
                            $entityManager->persist($paymentVerification);
                            $entityManager->persist($payment);
                            $entityManager->flush();

                            // Confirmer la transaction
                            $entityManager->commit();

                            // Ajouter un message de succès et rediriger
                            $this->addFlash('success', $message);
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        } catch (\Exception $e) {
                            // Annuler la transaction en cas d'erreur
                            $entityManager->rollback();
                            $this->addFlash('error', "Une erreur est survenue lors de la vérification : " . $e->getMessage());
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }
                    } elseif ($typePaiementG == "Anticiper") {

                        if (($montantPrevu - $montantRecu) < 0) {
                            $this->addFlash('warning', "Veuillez verifier le montant réçu que vous avez mis ! ");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }

                        if ($solde == 0) {

                            $montantRestant = ($montantPrevu - $montantRecu) + $montantRestant;
                            $solde = $montantRestant + $solde;
                            if ($solde >= 0) {
                                $nouveauSolde = $solde;
                                $paymentRepository->updateSoldeForUser($user, $nouveauSolde);
                                $paymentVerification->setPayment($payment);
                                $payment->setMontantRestant($montantRestant);
                                $payment->setMontantAPayer($montantRecu);
                                $payment->setTotalMontantPayer($montantRecu);
                                $payment->setMontantPrevu($montantPrevu);
                                $payment->setVerifier(true);

                                if (($montantPrevu - $montantRecu) == 0) {
                                    $payment->setStatus("Payé");
                                } else {
                                    $payment->setStatus("Partiel");
                                }
                                $payment->setPaymentVerification($paymentVerification);

                                $paymentVerification->setMontantPrevu($montantPrevu);
                                $entityManager->persist($paymentVerification);
                                $entityManager->persist($payment);
                                $entityManager->flush();
                                $this->addFlash('success', "La verification a été effectuer avec success ! ");
                                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                            } else {
                                $this->addFlash('warning', "Veuillez verifier le montant réçu que vous avez mis ! ");
                                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                            }
                        } else {
                            $this->addFlash('warning', "Il a un solde regler il ne peut pas anticiper ce paiement ! ");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }
                    } elseif ($typePaiementG == "Retard") {

                        $dernierPaiement = $paymentRepository->findSecondLatestDEPaymentByUser($user);
                        $montantRestant = $payment->getMontantRestant();

                        if (($montantRestant - $montantRecu) < 0) {
                            $this->addFlash('warning', "Veuillez verifier le montant réçu que vous avez mis ! ");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }

                        $montantRestant = ($montantRestant - $montantRecu);
                        $solde = $solde - $montantRecu;
                        if ($solde >= 0) {
                            $nouveauSolde = $solde;
                            $paymentRepository->updateSoldeForUser($user, $nouveauSolde);
                            $paymentVerification->setPayment($payment);
                            $payment->setMontantRestant($montantRestant);
                            $payment->setMontantAPayer($montantRecu);
                            $payment->setTotalMontantPayer($montantRecu);
                            $payment->setMontantPrevu($montantPrevu);
                            $payment->setVerifier(true);

                            if ($montantRestant == 0) {
                                $payment->setStatus("Payé");
                            } else {
                                $payment->setStatus("Partiel");
                            }
                            $payment->setPaymentVerification($paymentVerification);

                            $paymentVerification->setMontantPrevu($montantPrevu);
                            $entityManager->persist($paymentVerification);
                            $entityManager->persist($payment);
                            $entityManager->flush();
                            $this->addFlash('success', "La verification a été effectuer avec success ! ");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        } else {
                            $this->addFlash('warning', "Vous ne pouvez pas car il n'a pas de retard dans ces paiement ! ");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }
                    }
                } else {
                    $this->addFlash('warning', "Attention le montant prévu est inferieur au montant que vous avez reçu veuillez reprendre !");
                    return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('warning', "Veuillez selectionner le type de paiement que {$entrepise} a indiqué !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('gardinage/verifier.html.twig', [
            'jsfile' => $jsfile,
            'payment' => $payment,
            'solde' => $solde,
            'mois' => $dateFormatee,
            'montantPrevuForm' => $montantPrevuForm,
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }

    #[Route('/verifier-nouveau/{id}', name: 'verifier_nouveau', methods: ['GET', 'POST'])]
    public function verifierNouveau(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        PaymentRepository $paymentRepository
    ): Response {
        $paymentVerification = new PaymentVerification();
        $montantPrevuForm = $payment->getMontantPrevu();
        $form = $this->createForm(PaymentVerificationEditType::class, $paymentVerification);
        $form->handleRequest($request);
        $user = $payment->getUsers();
        $dernierPaiement = $paymentRepository->findSecondLatestPaymentByUser($user);
        $montantAPayer = $payment->getMontantAPayer();
        $typePaiement = $payment->getTypePaiement();
        $entrepise = $user->getNomDeSociete();
        $montantRestant = $dernierPaiement ? $dernierPaiement->getMontantRestant() : 0;
        $solde = $dernierPaiement ? $dernierPaiement->getSolde() : 0;
        $datePayement = $payment->getDatePaiement();
        setlocale(LC_TIME, 'fr_FR.utf8');
        $dateFormatee = strftime('%B %Y', $datePayement->getTimestamp());
        setlocale(LC_TIME, null);
        if ($form->isSubmitted() && $form->isValid()) {
            $montantRecu = $form->getData()->getMontantRecu();
            $montantPrevu = $montantPrevuForm ?? $form->getData()->getMontantPrevu();
            $typePaiementG = $form->getData()->getTypePaiement();
            $verifMontantSaisirGardien = $montantPrevu - $montantRecu;
            if ($typePaiement !== $typePaiementG) {
                $this->addFlash('warning', "Veuillez selectionner le type de paiement que {$entrepise} a indiqué !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($verifMontantSaisirGardien < 0) {
                $this->addFlash('warning', "Attention le montant prévu est inferieur au montant que vous avez reçu, veuillez reprendre !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
            try {
                $entityManager->beginTransaction();
                $paymentVerification->setPayment($payment);
                $paymentVerification->setMontantPrevu($montantPrevu);
                $payment->setMontantAPayer($montantRecu);
                $payment->setMontantSaisir($montantRecu);
                $payment->setTotalMontantPayer($montantRecu);
                $payment->setMontantPrevu($montantPrevu);
                $payment->setVerifier(true);
                $payment->setPaymentVerification($paymentVerification);
                switch ($typePaiementG) {
                    case "Normal":
                        if ($montantAPayer === $montantRecu) {
                            $payment->setStatus("Payé");
                        } else {
                            $payment->setStatus("partiel");
                        }
                        $payment->setMontantRestant(($montantPrevu - $montantRecu) + $montantRestant);
                        break;
                    case "Anticiper":
                        $solde = $payment->getSolde();
                        if ($solde > 0) {
                           $this->addFlash('warning', "Il y a un solde à régler, il ne peut pas anticiper ce paiement !");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }
                        if (($montantPrevu - $montantRecu) < 0) {
                            $this->addFlash('warning', "Veuillez vérifier le montant reçu que vous avez mis !");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }
                        $payment->setStatus(($montantPrevu - $montantRecu) == 0 ? "Payé" : "partiel");
                        $payment->setMontantRestant(($montantPrevu - $montantRecu) + $montantRestant);
                        break;
                    case "Retard":
                        $solde = $payment->getSolde();
                        $montantRestant = $payment->getMontantRestant();
                        $montantAPayerNouveau = $payment->getMontantAPayer();
                        $montantRestantNouveau = $montantRestant - $montantRecu;
                        $newSolde = $solde - $montantRecu;
                        if ($newSolde < 0) {
                            $this->addFlash('warning', "Vous ne pouvez pas car il n'a pas de retard dans ses paiements !");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }

                        $payment->setMontantRestant($montantRestantNouveau);
                        $payment->setSolde($newSolde);
                        $payment->setMontantAPayer(($payment->getMontantPrevu() - $montantRecu) + $montantAPayerNouveau);
                        $payment->setTotalMontantPayer(($payment->getMontantPrevu() - $montantRecu) + $montantAPayerNouveau);
                        $payment->setStatus($montantRestantNouveau == 0 ? "Payé" : "Partiel");
                        break;
                }
                $entityManager->persist($paymentVerification);
                $entityManager->persist($payment);
                $paymentRepository->updateSoldeForUser($user, $payment->getSolde() );
                $entityManager->flush();
                $entityManager->commit();
                $this->addFlash('success', "La vérification a été effectuée avec succès !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('danger', "Une erreur est survenue lors de la vérification : " . $e->getMessage());
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->render('gardinage/verifier_nouveau.html.twig', [
            'payment' => $payment,
            'mois' => $dateFormatee,
            'montantPrevuForm' => $montantPrevuForm,
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }


    #[Route('/ajouter-montant-prevu/{id}', name: 'ajouterMontantPrevu', methods: ['GET', 'POST'])]
    public function ajouterMontantPrevu(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        PaymentRepository $paymentRepository
    ): Response
    {
        $paymentVerification = new PaymentVerification();
        $form = $this->createForm(PaymentVerificationEditRetardType::class, $paymentVerification);
        $form->handleRequest($request);
        $user = $payment->getUsers();
        $dernierPaiement = $paymentRepository->findSecondLatestPaymentByUser($user);
        $typePaiement = $payment->getTypePaiement();
        $montantSaisir = $payment->getMontantSaisir();
        $entrepise = $user->getNomDeSociete();

        $montantRestant = $dernierPaiement ? $dernierPaiement->getMontantRestant() : 0;
        $solde = $dernierPaiement ? $dernierPaiement->getSolde() : 0;
        $datePayement = $payment->getDatePaiement();
        setlocale(LC_TIME, 'fr_FR.utf8');
        $dateFormatee = strftime('%B %Y', $datePayement->getTimestamp());
        setlocale(LC_TIME, null);

        if ($form->isSubmitted() && $form->isValid()) {
            $montantRecu = 0;
            $montantPrevu = $form->getData()->getMontantPrevu();
            $typePaiementG = "Retard";
            $verifMontantSaisirGardien = $montantPrevu - $montantRecu;
            if ($typePaiement !== $typePaiementG) {
                $this->addFlash('warning', "Veuillez selectionner le type de paiement que {$entrepise} a indiqué !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($verifMontantSaisirGardien < 0) {
                $this->addFlash('warning', "Attention le montant prévu est inferieur au montant que vous avez reçu, veuillez reprendre !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
            try {
                $entityManager->beginTransaction();
                $paymentVerification->setPayment($payment);
                $paymentVerification->setMontantPrevu($montantPrevu);
                $paymentVerification->setMontantRecu($montantRecu);
                $paymentVerification->setTypePaiement("Retard");
                $payment->setMontantAPayer($montantRecu);
                $payment->setMontantSaisir($montantRecu);
                $payment->setTotalMontantPayer($montantRecu);
                $payment->setMontantPrevu($montantPrevu);
                $payment->setVerifier(true);
                $payment->setPaymentVerification($paymentVerification);
                switch ($typePaiementG) {
                    case "Retard":
                        $solde = $payment->getSolde();
                        $montantRestant = $payment->getMontantRestant();
                        if (empty($solde)) {
                            $solde = 0;
                        }
                        $montantRestantNouveau = ($montantRestant - $montantRecu) + $montantPrevu;
                        $newSolde = ($solde - $montantRecu) + $montantPrevu;
                        if ($newSolde < 0) {
                            $this->addFlash('warning', "Vous ne pouvez pas car il n'a pas de retard dans ses paiements !");
                            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
                        }

                        $payment->setMontantRestant($montantRestantNouveau);
                        $payment->setSolde($newSolde);
                        $payment->setMontantAPayer($montantRecu);
                        $payment->setTotalMontantPayer($montantRecu);
                        if ($montantRestantNouveau == 0 ) {
                            $payment->setStatus("Payé");
                        } else {
                            if ($montantSaisir == 0) {
                                $payment->setStatus("Non payé");
                            } else {
                                $payment->setStatus("Partiel");
                            }
                        }
                        break;
                }
                $entityManager->persist($paymentVerification);
                $entityManager->persist($payment);
                $paymentRepository->updateSoldeForUser($user, $payment->getSolde() );
                $entityManager->flush();
                $entityManager->commit();
                $this->addFlash('success', "L'ajout du montant prévu a été effectuée avec succès !");
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('danger', "Une erreur est survenue lors de la vérification : " . $e->getMessage());
                return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('gardinage/ajouterMontantPrevu.html.twig', [
            'payment' => $payment,
            'mois' => $dateFormatee,
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }
}
