<?php

namespace App\Entity\RdapResponse;

class Nameserver
{
    public string $objectClassName = 'nameserver';

    public function __construct(
        public string $ldhName,
    ) {
    }

    public static function fromNameserver(\App\Entity\Nameserver $ns): self
    {
        return new Nameserver($ns->getLdhName());
    }
}
