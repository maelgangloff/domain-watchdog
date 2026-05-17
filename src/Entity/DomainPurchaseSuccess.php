<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DomainPurchaseSuccess extends DomainPurchase
{
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $domainOrderedAt = null;

    public function setDomainOrderedAt(?\DateTimeImmutable $domainOrderedAt): self
    {
        $this->domainOrderedAt = $domainOrderedAt;

        return $this;
    }

    public function getDomainOrderedAt(): ?\DateTimeImmutable
    {
        return $this->domainOrderedAt;
    }
}
