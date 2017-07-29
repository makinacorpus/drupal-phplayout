<?php

namespace MakinaCorpus\Drupal\Layout\Security;

use Drupal\Core\Entity\EntityManager;
use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Drupal\Sf\Security\DrupalUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LayoutNodeVoter implements VoterInterface
{
    private $entityManager;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (in_array('edit', $attributes) &&
            $subject instanceof Layout &&
            ($nodeId = $subject->getNodeId()) &&
            ($user = $token->getUser()) &&
            $user instanceof DrupalUser &&
            ($node = $this->entityManager->getStorage('node')->load($nodeId)) &&
            $node->access('update', $user->getDrupalAccount())
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
