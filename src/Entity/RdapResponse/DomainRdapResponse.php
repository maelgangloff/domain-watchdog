<?php

namespace App\Entity\RdapResponse;

class DomainRdapResponse extends Domain
{
    use RdapResponseTrait;

    public function __construct(\App\Entity\Domain $d)
    {
        parent::__construct($d);
    }
}
