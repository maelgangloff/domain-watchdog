<?php

namespace App\Entity\RdapResponse;

use App\Entity\DnsKey;

class DsData
{
    public int $keyTag;
    public int $algorithm;
    public string $digest;
    public int $digestType;

    public static function fromDnsKey(DnsKey $dnsKey): self
    {
        $d = new DsData();
        $d->algorithm = $dnsKey->getAlgorithm()->value;
        $d->keyTag = (int) $dnsKey->getKeyTag();
        $d->digest = $dnsKey->getDigest();
        $d->digestType = $dnsKey->getDigestType()->value;

        return $d;
    }
}
