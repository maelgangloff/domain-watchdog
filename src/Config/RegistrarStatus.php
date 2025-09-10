<?php

namespace App\Config;

enum RegistrarStatus: string
{
    case Reserved = 'Reserved';
    case Accredited = 'Accredited';
    case Terminated = 'Terminated';
}
