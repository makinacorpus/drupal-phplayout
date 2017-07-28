<?php

namespace MakinaCorpus\Drupal\Layout\Security;

use MakinaCorpus\Drupal\Sf\Security\DrupalUser;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LayoutPermissionVoter implements VoterInterface
{
    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (in_array('edit', $attributes) &&
            $subject instanceof LayoutInterface &&
            ($user = $token->getUser()) &&
            $user instanceof DrupalUser &&
            $user->getDrupalAccount()->hasPermission(PHP_LAYOUT_PERMISSION_EDIT_ALL)
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
