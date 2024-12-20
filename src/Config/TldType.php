<?php

namespace App\Config;

enum TldType: string
{
    case iTLD = 'iTLD';
    case gTLD = 'gTLD';
    case sTLD = 'sTLD';
    case ccTLD = 'ccTLD';
    case tTLD = 'tTLD';
    case root = 'root';
}
