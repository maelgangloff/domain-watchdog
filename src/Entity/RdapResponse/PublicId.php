<?php

namespace App\Entity\RdapResponse;

class PublicId
{
    public function __construct(
        public string $type,
        public string $identifier)
    {
    }
}
