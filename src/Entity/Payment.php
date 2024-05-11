<?php

namespace App\Entity;

use App\Entity\Traits\AppTimesTampable; 
use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[Vich\Uploadable]
class Payment
{
    use AppTimesTampable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $montantAPayer = null;

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'recu', fileNameProperty: 'recuDePaiement')]
    private ?File $imageFile = null; 

    #[ORM\Column (length: 255)]
    private ?string $recuDePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?int $montantRestant = null;

    #[ORM\Column(length: 255)]
    private ?string $status = "en attente";

    #[ORM\ManyToOne(inversedBy: 'payments')]
    private ?User $users = null;

    #[ORM\Column]
    private ?bool $isVisibilite = true;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?PaymentVerification $PaymentVerification = null;

    /**
     * @var Collection<int, PaymentVerification>
     */
    #[ORM\OneToMany(targetEntity: PaymentVerification::class, mappedBy: 'Payment')]
    private Collection $paymentVerifications;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?int $solde = null;
    

    public function __construct()
    {
        $this->paymentVerifications = new ArrayCollection();
    }

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

    /** 
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
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

    public function setRecuDePaiement(string $recuDePaiement): static
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

    public function getPaymentVerification(): ?PaymentVerification
    {
        return $this->PaymentVerification;
    }

    public function setPaymentVerification(?PaymentVerification $PaymentVerification): static
    {
        $this->PaymentVerification = $PaymentVerification;

        return $this;
    }

    /**
     * @return Collection<int, PaymentVerification>
     */
    public function getPaymentVerifications(): Collection
    {
        return $this->paymentVerifications;
    }

    public function addPaymentVerification(PaymentVerification $paymentVerification): static
    {
        if (!$this->paymentVerifications->contains($paymentVerification)) {
            $this->paymentVerifications->add($paymentVerification);
            $paymentVerification->setPayment($this);
        }

        return $this;
    }

    public function removePaymentVerification(PaymentVerification $paymentVerification): static
    {
        if ($this->paymentVerifications->removeElement($paymentVerification)) {
            // set the owning side to null (unless already changed)
            if ($paymentVerification->getPayment() === $this) {
                $paymentVerification->setPayment(null);
            }
        }

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
}
