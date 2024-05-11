<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\PaymentVerification;
use App\Entity\User;
use App\Form\PaymentVerificationType;
use App\Form\UserType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GARDIEN', statusCode: 403, exceptionCode: 10010)]
#[Route('/gardinage', name: 'app_gardinage_')]
class GardinageController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository): Response
    {
        $utilisateurConnecte = $this->getUser(); 
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        return $this->render('gardinage/index.html.twig',  [
            'NomDeSociete'=> $NomDeSociete,
            'payments' => $paymentRepository->findAll(),
        ]);
    }

    #[Route('/verifier/{id}', name: 'verifier', methods: ['GET', 'POST'])]
    public function verifier(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment
        ): Response
    { 
        $paymentVerification = new PaymentVerification();
        $form = $this->createForm(PaymentVerificationType::class, $paymentVerification);
        $form->handleRequest($request);
        if ($payment->getMontantRestant() != null) {
            $montantRestant = $payment->getMontantRestant();
        }else {
            $montantRestant = 0;
        }

        if ($payment->getSolde() != null) {
            $solde = $payment->getSolde();
        }else {
            $solde = 0;
        }
        $NomDeSociete=$payment->getUsers()->getNomDeSociete();
        $datePayement = $payment->getDatePaiement();
        $payment = $payment; 
        // Définir la locale sur français
        setlocale(LC_TIME, 'fr_FR.utf8');
            // Formater la date avec le nom du mois en français
            $dateFormatee = strftime('%B %Y', $datePayement->getTimestamp());
            // Rétablir la locale par défaut
            setlocale(LC_TIME, null);

            // Afficher la date formatée 

        if ($form->isSubmitted() && $form->isValid()) {
            $montantRecu = $form->getData()->getmontantPrevu();
            $montantPrevu = $form->getData()->getmontantPrevu();
            $montantRestant = ($montantPrevu - $montantRecu)+$montantRestant;
            $solde = $montantRestant + $solde;
            $paymentVerification->setPayment($payment);
            $payment->setMontantRestant($montantRestant);
            $payment->setSolde($solde);
            $entityManager->persist($paymentVerification); 
            if (($montantPrevu - $montantRecu)==0) {
                $payment->setStatus("Payé");
                $payment->setPaymentVerification($paymentVerification);
            }else {
                $payment->setStatus("Partiellement payé");
            } 
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_gardinage_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('gardinage/verifier.html.twig', [
            'payment'=> $payment,
            'NomDeSociete'=> $NomDeSociete,
            'mois'=> $dateFormatee,
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }

    #[Route('/valider', name: 'valider')]
    public function valider(): Response
    {
        $utilisateurConnecte = $this->getUser(); 
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        return $this->render('gardinage/valider.html.twig', [
            'NomDeSociete'=> $NomDeSociete,
        ]);
    }
}
