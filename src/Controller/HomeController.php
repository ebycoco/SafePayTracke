<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        UserRepository $userRepository
        ): Response
    { 
        // Récupérer le nombre total d'utilisateurs
        $nombreUtilisateurs = $userRepository->count([]); 
        return $this->render('home/index.html.twig', [ 
            'nombreUtilisateurs' => $nombreUtilisateurs,
            'payments' => $paymentRepository->findAll(),
        ]);
    }
}
