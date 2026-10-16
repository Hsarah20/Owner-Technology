<?php

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentVoter extends Voter
{
    public const VIEW = 'DOCUMENT_VIEW';
    public const DOWNLOAD = 'DOCUMENT_DOWNLOAD';
    public const RETRY = 'DOCUMENT_RETRY';
    public const EDIT = 'DOCUMENT_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Document) {
            return false;
        }

        return in_array($attribute, [self::VIEW, self::DOWNLOAD, self::RETRY, self::EDIT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Un admin OWNER peut tout voir et tout faire
        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return true;
        }

        // Un client ne peut agir que sur les documents de son propre client
        /** @var Document $document */
        $document = $subject;
        $client = $user->getClient();

        if ($client === null) {
            return false;
        }

        if ($document->getClient() !== $client) {
            return false;
        }

        // Seul le client propriétaire peut éditer ses métadonnées
        if ($attribute === self::EDIT) {
            return true;
        }

        // Seul un admin peut relancer une analyse
        if ($attribute === self::RETRY) {
            return false;
        }

        return true;
    }
}