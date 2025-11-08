<?php

namespace App\Entity\RdapResponse;

use App\Entity\DnsKey;

class SecureDNS
{
    public bool $delegationSigned;
    /**
     * @var DsData[]
     */
    public ?array $dsData = null;

    /**
     * @throws \Exception
     */
    public static function fromDomain(\App\Entity\Domain $d): self
    {
        $s = new SecureDNS();
        $s->delegationSigned = $d->isDelegationSigned();
        if ($s->delegationSigned) {
            $s->dsData = array_map(fn (DnsKey $dnsKey) => DsData::fromDnsKey($dnsKey), $d->getDnsKey()->toArray());
        }

        return $s;
    }
}
