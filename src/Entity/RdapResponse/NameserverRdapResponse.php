<?php

namespace App\Entity\RdapResponse;

class NameserverRdapResponse extends Nameserver
{
    use RdapResponseTrait;

    public function __construct(\App\Entity\Nameserver $ns)
    {
        parent::__construct($ns->getLdhName());
    }
}
