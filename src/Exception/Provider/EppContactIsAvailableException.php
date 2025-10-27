<?php

namespace App\Exception\Provider;

class EppContactIsAvailableException extends AbstractProviderException
{
    public static function fromContact(string $handle): self
    {
        return new self("At least one of the entered contacts cannot be used because it is indicated as available ($handle)");
    }
}
