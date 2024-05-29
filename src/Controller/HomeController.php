<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        UserRepository $userRepository,
        Request $request
        ): Response
    {
        //On va chercher le numéro de page dans l'url
        $page = $request->query->getInt('page', 1);
        // Récupérer le nombre total d'utilisateurs qui ont pour role ROLE_LOCATEUR
        $role = 'ROLE_LOCATEUR';
        $nombreUtilisateurs = $userRepository->countUsersByRole($role);
        $payments = $paymentRepository->findPaymentPaginated($page,4);
        return $this->render('home/index.html.twig', [
            'nombreUtilisateurs' => $nombreUtilisateurs,
            'payments' => $payments,
        ]);
    }
}
