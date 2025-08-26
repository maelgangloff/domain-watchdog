<?php

namespace App\Config\DnsKey;

/**
 * @see https://www.iana.org/assignments/ds-rr-types/ds-rr-types.xhtml
 */
enum DigestType: int
{
    case RESERVED = 0;
    case SHA1 = 1;
    case SHA256 = 2;
    case GOST_R_34_11_94 = 3;
    case SHA384 = 4;
    case GOST_R_34_11_2012 = 5;
    case SM3 = 6;

    // 7-127 UNASSIGNED
    // 128-252 RESERVED
    // 253-254 RESERVED PRIVATE USE
    // 254 UNASSIGNED
}
