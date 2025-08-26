<?php

namespace App\Config\DnsKey;

/**
 * @see https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml
 */
enum Algorithm: int
{
    case RSAMD5 = 1;
    case DH = 2;
    case DSA = 3;
    // 4 RESERVED
    case RSASHA1 = 5;
    case DSA_NSEC3_SHA1 = 6;
    case RSASHA1_NSEC3_SHA1 = 7;
    case RSASHA256 = 8;
    // 9 RESERVED
    case RSASHA512 = 10;
    // 11 RESERVED
    case ECC_GOST = 12;
    case ECDSAP256SHA256 = 13;
    case ECDSAP384SHA384 = 14;
    case ED25519 = 15;
    case ED448 = 16;
    case SM2SM3 = 17;
    // 18-22 RESERVED
    case ECC_GOST12 = 23;
    // 24-122 UNASSIGNED
    // 123-251 RESERVED
    case INDIRECT = 252;
    case PRIVATEDNS = 253;
    case PRIVATEOID = 254;
    case RESERVED_255 = 255;
    // 255 RESERVED
}
