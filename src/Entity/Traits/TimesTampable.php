<?php

namespace App\Entity\Traits;

trait TimesTampable
{
    /**  
     * @ORM\Column(type="datetime",options={"default": "CURRENT_TIMESTAMP"})
     */
    private $publishedAt;
 
    /**  
     * @ORM\Column(type="datetime",options={"default": "CURRENT_TIMESTAMP"})
     */
    private  $updatedAt;

    /**
     * Get the value of publishedAt
     * 
     */ 
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Set the value of publishedAt
     *
     * @param  \DateTimeInterface  $publishedAt
     *
     * @return  self
     */ 
    public function setPublishedAt(\DateTimeInterface $publishedAt)
    {
        $this->publishedAt = $publishedAt;

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
        if ($this->getPublishedAt() === null) {
            $this->setPublishedAt(new \DateTimeImmutable());
        }

        $this->setUpdatedAt(new \DateTimeImmutable());
    }
}

