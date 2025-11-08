<?php

namespace App\Entity\RdapResponse;

trait RdapResponseTrait
{
    public array $rdapConformance = [
        'rdap_level_0',
        'icann_rdap_technical_implementation_guide_1',
        'icann_rdap_response_profile_1',
    ];
    /**
     * @var Link[]
     */
    public array $links;

    /**
     * @var Notice[]
     */
    public array $notices;
}
