<?php

namespace App\Entity;

use App\Entity\Traits\AppTimesTampable;
use App\Repository\PaymentVerificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: '`payment_verification`')]
#[ORM\Entity(repositoryClass: PaymentVerificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PaymentVerification
{
    use AppTimesTampable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $montantPrevu = null;

    #[ORM\Column]
    private ?int $montantRecu = null;

    #[ORM\Column(length: 255)]
    private ?string $typePaiement = null;

    #[ORM\OneToOne(inversedBy: 'paymentVerification', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Payment $payment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantPrevu(): ?int
    {
        return $this->montantPrevu;
    }

    public function setMontantPrevu(int $montantPrevu): static
    {
        $this->montantPrevu = $montantPrevu;

        return $this;
    }

    public function getMontantRecu(): ?int
    {
        return $this->montantRecu;
    }

    public function setMontantRecu(int $montantRecu): static
    {
        $this->montantRecu = $montantRecu;

        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(string $typePaiement): static
    {
        $this->typePaiement = $typePaiement;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('PaymentVerification #%d', $this->id);
    }
}
