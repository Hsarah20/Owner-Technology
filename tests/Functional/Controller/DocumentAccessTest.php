<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Client;
use App\Entity\Document;
use App\Entity\Upload;
use App\Entity\User;
use App\Enum\DocumentStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class DocumentAccessTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        // On ne fait RIEN ici. Chaque test crée son propre client HTTP
        // via static::createClient(), ce qui boote le kernel une seule fois.
    }

    public function testClientCannotAccessOtherClientDocument(): void
    {
        $httpClient = static::createClient();
        $this->em = $httpClient->getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->createTestClient('ACME');
        $clientB = $this->createTestClient('BETA');
        $userB = $this->createTestUser('b@test.fr', $clientB);
        $documentA = $this->createTestDocument($clientA, 'doc-a.pdf');

        $httpClient->loginUser($userB);
        $httpClient->request('GET', '/app/documents/' . $documentA->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testClientCanAccessOwnDocument(): void
    {
        $httpClient = static::createClient();
        $this->em = $httpClient->getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->createTestClient('ACME');
        $userA = $this->createTestUser('a@test.fr', $clientA);
        $documentA = $this->createTestDocument($clientA, 'doc-a.pdf');

        $httpClient->loginUser($userA);
        $httpClient->request('GET', '/app/documents/' . $documentA->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testClientCannotDownloadOtherClientDocument(): void
    {
        $httpClient = static::createClient();
        $this->em = $httpClient->getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->createTestClient('ACME');
        $clientB = $this->createTestClient('BETA');
        $userB = $this->createTestUser('b@test.fr', $clientB);
        $documentA = $this->createTestDocument($clientA, 'doc-a.pdf');

        $httpClient->loginUser($userB);
        $httpClient->request('GET', '/app/documents/' . $documentA->getId() . '/download');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessAnyDocument(): void
    {
        $httpClient = static::createClient();
        $this->em = $httpClient->getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->createTestClient('ACME');
        $admin = $this->createTestUser('admin@test.fr', null, [User::ROLE_ADMIN]);
        $documentA = $this->createTestDocument($clientA, 'doc-a.pdf');

        $httpClient->loginUser($admin);
        $httpClient->request('GET', '/admin/documents/' . $documentA->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $httpClient = static::createClient();
        $this->em = $httpClient->getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->createTestClient('ACME');
        $documentA = $this->createTestDocument($clientA, 'doc-a.pdf');

        $httpClient->request('GET', '/app/documents/' . $documentA->getId());

        $this->assertResponseRedirects();
    }

    // ---------- Helpers ----------

    private function createTestClient(string $name): Client
    {
        $client = new Client();
        $client->setName($name);
        $this->em->persist($client);
        $this->em->flush();
        return $client;
    }

    private function createTestUser(string $email, ?Client $client, array $roles = [User::ROLE_CLIENT]): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password'));
        if ($client) {
            $user->setClient($client);
        }
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function createTestDocument(Client $client, string $name): Document
    {
        $upload = new Upload();
        $upload->setClient($client);
        $upload->setStatus(DocumentStatus::RECEIVED);
        $this->em->persist($upload);

        $document = new Document();
        $document->setClient($client);
        $document->setUpload($upload);
        $document->setOriginalName($name);
        $document->setStoredPath('test/' . Uuid::v4() . '.pdf');
        $document->setMimeType('application/pdf');
        $document->setSize(1000);
        $document->setStatus(DocumentStatus::RECEIVED);
        $this->em->persist($document);
        $this->em->flush();
        return $document;
    }
}