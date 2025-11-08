<?php

namespace App\Entity\RdapResponse;

class EntityRdapResponse extends Entity
{
    use RdapResponseTrait;

    public function __construct(\App\Entity\Entity $e)
    {
        parent::__construct($e);
    }
}
