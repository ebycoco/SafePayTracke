<?php

namespace App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;

trait AppTimesTampable
{
   
    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $createdAt = null;
 
    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable  $updatedAt =null;

    /**
     * Undocumented function
     *
     * @return \DateTimeImmutable|null
     */
    public function getcreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Undocumented function
     *
     * @param \DateTimeInterface $createdAt
     * @return static
     */
    public function setcreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

   /**
    * Undocumented function
    *
    * @return \DateTimeImmutable|null
    */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Undocumented function
     *
     * @param \DateTimeInterface $updatedAt
     * @return static
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps()
    {
        if ($this->getcreatedAt() === null) {
            $this->setcreatedAt(new \DateTimeImmutable());
        }

        $this->setUpdatedAt(new \DateTimeImmutable());
    }
}

