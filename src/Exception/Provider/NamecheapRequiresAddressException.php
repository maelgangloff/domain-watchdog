<?php

namespace App\Exception\Provider;

class NamecheapRequiresAddressException extends AbstractProviderException
{
    public function __construct()
    {
        parent::__construct('Namecheap account requires at least one address to purchase a domain');
    }
}
