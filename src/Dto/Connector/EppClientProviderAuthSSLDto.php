<?php

namespace App\Dto\Connector;

final class EppClientProviderAuthSSLDto
{
    public ?string $peer_name = null;

    public ?bool $verify_peer = null;

    public ?bool $verify_peer_name = null;

    public ?bool $allow_self_signed = null;

    public ?int $verify_depth = null;

    public ?string $passphrase = null;

    public ?bool $disable_compression = null;
}
