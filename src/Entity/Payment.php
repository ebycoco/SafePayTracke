<?php

namespace App\Entity;

use App\Entity\Traits\AppTimesTampable;
use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: '`Payment`')]
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[Vich\Uploadable]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    use AppTimesTampable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $montantAPayer = null;

    #[ORM\Column]
    private ?int $montantSaisir = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalMontantPayer = null;

    #[Vich\UploadableField(mapping: 'recu', fileNameProperty: 'recuDePaiement')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true, length: 255)]
    private ?string $recuDePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?int $montantRestant = null;

    #[ORM\Column(length: 255)]
    private ?string $status = "en attente";

    #[ORM\ManyToOne(inversedBy: 'payments')]
    private ?User $users = null;

    #[ORM\Column]
    private ?bool $isVisibilite = true;

    #[ORM\Column]
    private ?bool $isVerifier = false;

    #[ORM\OneToOne(mappedBy: 'payment', cascade: ['persist', 'remove'])]
    private ?PaymentVerification $paymentVerification = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?int $solde = null;

    #[ORM\Column(nullable: true)]
    private ?int $montantPrevu = null;

    #[ORM\Column(length: 255)]
    private ?string $typePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?int $avancePaiement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantAPayer(): ?int
    {
        return $this->montantAPayer;
    }

    public function setMontantAPayer(int $montantAPayer): static
    {
        $this->montantAPayer = $montantAPayer;

        return $this;
    }

    public function getMontantSaisir(): ?int
    {
        return $this->montantSaisir;
    }

    public function setMontantSaisir(int $montantSaisir): static
    {
        $this->montantSaisir = $montantSaisir;

        return $this;
    }

    public function getTotalMontantPayer(): ?int
    {
        return $this->totalMontantPayer;
    }

    public function setTotalMontantPayer(?int $totalMontantPayer): static
    {
        $this->totalMontantPayer = $totalMontantPayer;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getRecuDePaiement(): ?string
    {
        return $this->recuDePaiement;
    }

    public function setRecuDePaiement(?string $recuDePaiement): static
    {
        $this->recuDePaiement = $recuDePaiement;

        return $this;
    }

    public function getMontantRestant(): ?int
    {
        return $this->montantRestant;
    }

    public function setMontantRestant(?int $montantRestant): static
    {
        $this->montantRestant = $montantRestant;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): static
    {
        $this->users = $users;

        return $this;
    }

    public function isVisibilite(): ?bool
    {
        return $this->isVisibilite;
    }

    public function setVisibilite(bool $isVisibilite): static
    {
        $this->isVisibilite = $isVisibilite;

        return $this;
    }

    public function isVerifier(): ?bool
    {
        return $this->isVerifier;
    }

    public function setVerifier(bool $isVerifier): static
    {
        $this->isVerifier = $isVerifier;

        return $this;
    }

    public function getPaymentVerification(): ?PaymentVerification
    {
        return $this->paymentVerification;
    }

    public function setPaymentVerification(?PaymentVerification $paymentVerification): static
    {
        if ($paymentVerification === null && $this->paymentVerification !== null) {
            $this->paymentVerification->setPayment(null);
        }

        if ($paymentVerification !== null && $paymentVerification->getPayment() !== $this) {
            $paymentVerification->setPayment($this);
        }

        $this->paymentVerification = $paymentVerification;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getSolde(): ?int
    {
        return $this->solde;
    }

    public function setSolde(?int $solde): static
    {
        $this->solde = $solde;

        return $this;
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

    public function getTypePaiement(): ?string
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(string $typePaiement): static
    {
        $this->typePaiement = $typePaiement;

        return $this;
    }

    public function getAvancePaiement(): ?int
    {
        return $this->avancePaiement;
    }

    public function setAvancePaiement(?int $avancePaiement): static
    {
        $this->avancePaiement = $avancePaiement;

        return $this;
    }
}
