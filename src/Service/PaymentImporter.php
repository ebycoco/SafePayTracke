<?php
// src/Service/PaymentImporter.php
namespace App\Service;

use App\Entity\Payment;
use App\Repository\UserRepository;
use App\Repository\PaymentVerificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PaymentImporter
{
    private $entityManager;
    private $userRepository;
    private $paymentVerificationRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, PaymentVerificationRepository $paymentVerificationRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->paymentVerificationRepository = $paymentVerificationRepository;
    }

    public function importFromFile(string $filePath): void
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue; // Skip header row
                }

                $payment = new Payment();

                // Récupérer l'utilisateur à partir de l'ID
                $user = $this->userRepository->find($row[1]);
                if ($user) {
                    $payment->setUsers($user);
                }

                // Récupérer la vérification de paiement à partir de l'ID
                $paymentVerification = $this->paymentVerificationRepository->find($row[2]);
                if ($paymentVerification) {
                    $payment->setPaymentVerification($paymentVerification);
                }

                $payment->setMontantAPayer((int)$row[3]);
                $payment->setMontantSaisir((int)$row[4]);
                $payment->setTotalMontantPayer((int)$row[5]);
                $payment->setRecuDePaiement($row[6]);
                $payment->setMontantRestant((int)$row[7]);
                $payment->setStatus($row[8]);
                $payment->setVisibilite((bool)$row[9]);
                $payment->setVerifier((bool)$row[11]);
                $payment->setDatePaiement(new \DateTimeImmutable($row[12]));
                $payment->setSolde((int)$row[13]);
                $payment->setMontantPrevu((int)$row[14]);
                $payment->setTypePaiement($row[15]);
                $payment->setAvancePaiement($row[16]);

                // Set created_at and updated_at with DateTimeImmutable
                $payment->setCreatedAt(new \DateTimeImmutable($row[17]));
                $payment->setUpdatedAt(new \DateTimeImmutable($row[18]));

                $this->entityManager->persist($payment);
            }

            $this->entityManager->flush();
        } catch (FileException $e) {
            // Handle file exception
            throw new \Exception("File could not be opened. Error: " . $e->getMessage());
        }
    }
}
