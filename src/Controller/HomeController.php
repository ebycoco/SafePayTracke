<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        UserRepository $userRepository,
        DocumentRepository $documentRepository,
        Request $request
        ): Response
    {
        //On va chercher le numéro de page dans l'url
        $page = $request->query->getInt('page', 1);
        // Récupérer le nombre total d'utilisateurs qui ont pour role ROLE_LOCATEUR
        $role = 'ROLE_LOCATEUR';
        $nombreUtilisateurs = $userRepository->countUsersByRole($role);
        $paymentsData  = $paymentRepository->findPaymentPaginated($page,8);
        $documentData  = $documentRepository->findByDocument();
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        return $this->render('home/index.html.twig', [
            'nombreUtilisateurs' => $nombreUtilisateurs,
            'paymentsNombre' => $paymentsNombre,
            'payments' => $paymentsData,
            'documents' => $documentData,
            'userRoles' => $this->getUser()->getRoles(),
        ]);
    }

    #[Route('/document/download/{id}', name: 'app_document_download', methods: ['GET'])]
    public function downloadDocument(DocumentRepository $documentRepository, int $id): Response
    {
        $document = $documentRepository->find($id);

        if (!$document) {
            throw new NotFoundHttpException('Document not found.');
        }

        // Getting the filename and extension
        $fileName = $document->getImageDocument();
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        $filePath = $this->getParameter('kernel.project_dir') . '/public/images/document/' . $fileName;
       
        
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found.');
        }

        

        return $this->file($filePath, $document->getName() . '.' . $fileExtension, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
