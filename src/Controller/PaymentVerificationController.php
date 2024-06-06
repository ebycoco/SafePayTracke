<?php

namespace App\Controller;

use App\Entity\PaymentVerification;
use App\Form\PaymentVerificationType;
use App\Repository\PaymentVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GARDIEN', statusCode: 403, exceptionCode: 10010)]
#[Route('/payment/verification')]
class PaymentVerificationController extends AbstractController
{
    #[Route('/', name: 'app_payment_verification_index', methods: ['GET'])]
    public function index(PaymentVerificationRepository $paymentVerificationRepository): Response
    {
        return $this->render('payment_verification/index.html.twig', [
            'payment_verifications' => $paymentVerificationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_payment_verification_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentVerification = new PaymentVerification();
        $form = $this->createForm(PaymentVerificationType::class, $paymentVerification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paymentVerification);
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_verification_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment_verification/new.html.twig', [
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_verification_show', methods: ['GET'])]
    public function show(PaymentVerification $paymentVerification): Response
    {
        return $this->render('payment_verification/show.html.twig', [
            'payment_verification' => $paymentVerification,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payment_verification_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PaymentVerification $paymentVerification, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaymentVerificationType::class, $paymentVerification);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_verification_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment_verification/edit.html.twig', [
            'payment_verification' => $paymentVerification,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_verification_delete', methods: ['POST'])]
    public function delete(Request $request, PaymentVerification $paymentVerification, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$paymentVerification->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($paymentVerification);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_payment_verification_index', [], Response::HTTP_SEE_OTHER);
    }
}
