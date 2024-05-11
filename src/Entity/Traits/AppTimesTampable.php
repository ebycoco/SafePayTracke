<?php

namespace App\Entity\Traits;

trait AppTimesTampable
{
    /**  
     * @ORM\Column(type="datetime",options={"default": "CURRENT_TIMESTAMP"})
     */
    private $createdAt;
 
    /**  
     * @ORM\Column(type="datetime",options={"default": "CURRENT_TIMESTAMP"})
     */
    private  $updatedAt;

    /**
     * Get the value of createdAt
     * 
     */ 
    public function getcreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @param  \DateTimeInterface  $createdAt
     *
     * @return  self
     */ 
    public function setcreatedAt(\DateTimeInterface $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of updatedAt
     * 
     */ 
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the value of updatedAt
     *
     * @param  \DateTimeInterface  $updatedAt
     *
     * @return  self
     */ 
    public function setUpdatedAt(\DateTimeInterface $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     *
     * @return void
     */
    public function updateTimestamps()
    {
        if ($this->getcreatedAt() === null) {
            $this->setcreatedAt(new \DateTimeImmutable());
        }

        $this->setUpdatedAt(new \DateTimeImmutable());
    }
}

