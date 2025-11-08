<?php

namespace App\Entity\RdapResponse;

class Notice
{
    public function __construct(
        public string $title,
        /**
         * @var string[]
         */
        public array $description,
        /**
         * @var Link[]
         */
        public array $links,
    ) {
    }
}
