<?php

namespace App\Tests\Unit\Security;

use App\Entity\Client;
use App\Entity\Document;
use App\Entity\User;
use App\Security\Voter\DocumentVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

class DocumentVoterTest extends TestCase
{
    private DocumentVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new DocumentVoter();
    }

    public function testAdminCanViewAnyDocument(): void
    {
        $admin = $this->createUser('admin@test.fr', [User::ROLE_ADMIN], null);
        $otherClient = $this->createClient('BETA');
        $document = $this->createDocument($otherClient);

        $token = new UsernamePasswordToken($admin, 'main', $admin->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DocumentVoter::VIEW])
        );
    }

    public function testClientCanViewOwnDocument(): void
    {
        $client = $this->createClient('ACME');
        $user = $this->createUser('client@test.fr', [User::ROLE_CLIENT], $client);
        $document = $this->createDocument($client);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DocumentVoter::VIEW])
        );
    }

    public function testClientCannotViewOtherClientDocument(): void
    {
        $ownClient = $this->createClient('ACME');
        $otherClient = $this->createClient('BETA');
        $user = $this->createUser('client@test.fr', [User::ROLE_CLIENT], $ownClient);
        $document = $this->createDocument($otherClient);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DocumentVoter::VIEW])
        );
    }

    public function testClientWithoutClientCannotViewAnything(): void
    {
        $user = $this->createUser('orphan@test.fr', [User::ROLE_CLIENT], null);
        $document = $this->createDocument($this->createClient('ACME'));

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DocumentVoter::VIEW])
        );
    }

    public function testClientCannotRetryEvenOnOwnDocument(): void
    {
        $client = $this->createClient('ACME');
        $user = $this->createUser('client@test.fr', [User::ROLE_CLIENT], $client);
        $document = $this->createDocument($client);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DocumentVoter::RETRY])
        );
    }

    public function testClientCanEditOwnDocument(): void
    {
        $client = $this->createClient('ACME');
        $user = $this->createUser('client@test.fr', [User::ROLE_CLIENT], $client);
        $document = $this->createDocument($client);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DocumentVoter::EDIT])
        );
    }

    public function testAnonymousCannotViewAnyDocument(): void
    {
        $document = $this->createDocument($this->createClient('ACME'));

        $token = new NullToken();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DocumentVoter::VIEW])
        );
    }

    // ---------- Helpers ----------

    private function createUser(string $email, array $roles, ?Client $client): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword('hashed');
        if ($client) {
            $user->setClient($client);
        }
        return $user;
    }

    private function createClient(string $name): Client
    {
        $client = new Client();
        $client->setName($name);
        return $client;
    }

    private function createDocument(Client $client): Document
    {
        $document = new Document();
        $document->setClient($client);
        $document->setOriginalName('test.pdf');
        $document->setStoredPath('test/test.pdf');
        $document->setMimeType('application/pdf');
        $document->setSize(1000);
        return $document;
    }
}