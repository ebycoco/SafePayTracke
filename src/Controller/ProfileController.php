<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\User;
use App\Form\PaymentType;
use App\Form\UserType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(PaymentRepository $paymentRepository): Response
    { 
          // Récupérer l'utilisateur connecté
        $utilisateurConnecte = $this->getUser(); 
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        // Si aucun utilisateur n'est connecté, rediriger vers la page de connexion
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        } 
        

        // Récupérer tous les paiements de l'utilisateur connecté
        $paiements = $paymentRepository->findBy(
            ['users' => $utilisateurConnecte],
            ['datePaiement' => 'DESC']
        ); 
        return $this->render('profile/profile.html.twig', [ 
            'paiements' => $paiements, 
            'NomDeSociete'=> $NomDeSociete,
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
        $form = $this->createForm(UserType::class, $user);
        
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
    public function paiement(Request $request, EntityManagerInterface $entityManager): Response
    { 
        $utilisateurConnecte = $this->getUser(); 
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        $payment = new Payment();
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);
        // Obtenez l'utilisateur actuellement connecté
        $user = $this->getUser(); 

        if ($form->isSubmitted() && $form->isValid()) { 
            $payment->setUsers($this->getUser());

            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('profile/paiement.html.twig', [
            'payment' => $payment,
            'NomDeSociete'=> $NomDeSociete,
            'form' => $form,
        ]);
    }
    
}
