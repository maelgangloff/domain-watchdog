<?php

namespace App\Dto\Connector;

final class EppClientProviderAuthSSLDto
{
    public ?string $peer_name = null;

    public bool $verify_peer = true;

    public bool $verify_peer_name = true;

    public bool $allow_self_signed = false;

    public ?int $verify_depth = null;

    public ?string $passphrase = null;

    public bool $disable_compression = false;
}
