<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Document;
use App\Form\SignaleType;
use App\Form\DocumentType;
use App\Form\UserEditRoleType;
use App\Repository\UserRepository;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN', statusCode: 403, exceptionCode: 10010)]
#[Route('/admin', name: 'app_admin_')]
class UserController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository,UserRepository $userRepository): Response
    {
        $utilisateurConnecte = $this->getUser();
        $NomDeSociete= $utilisateurConnecte->getNomDeSociete();
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        return $this->render('admin/index.html.twig', [
            'NomDeSociete'=> $NomDeSociete,
            'users' => $userRepository->findAll(),
            'paymentsNombre' => $paymentsNombre
        ]);
    }

    #[Route('/document', name: 'lis_doc', methods: ['GET'])]
    public function lisDoc(PaymentRepository $paymentRepository,DocumentRepository $documentRepository): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        return $this->render('admin/document.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'documents' => $documentRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-document', name: 'add_doc', methods: ['GET', 'POST'])]
    public function addDoc(PaymentRepository $paymentRepository,Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($document);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_lis_doc', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/add_document.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier-document', name: 'edit_doc', methods: ['GET', 'POST'])]
    public function editDoc(PaymentRepository $paymentRepository,Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_lis_doc', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/edit_document.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete_doc', methods: ['POST'])]
    public function deleteDoc(PaymentRepository $paymentRepository,Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($document);
            $entityManager->flush();
        }
 
        return $this->redirectToRoute('app_admin_lis_doc', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(PaymentRepository $paymentRepository,Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/new.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PaymentRepository $paymentRepository,User $user): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        return $this->render('admin/show.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(PaymentRepository $paymentRepository,Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        $form = $this->createForm(UserEditRoleType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/edit.html.twig', [
            'paymentsNombre' => $paymentsNombre,
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(PaymentRepository $paymentRepository,Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $paymentsNombre = $paymentRepository->findPaymentNombre();
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}
