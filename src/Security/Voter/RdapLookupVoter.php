<?php

namespace App\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RdapLookupVoter extends Voter
{
    public const string ATTRIBUTE = 'CAN_RDAP_LOOKUP';

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ATTRIBUTE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return true;
        }

        return $this->parameterBag->get('public_rdap_lookup_enabled');
    }
}
