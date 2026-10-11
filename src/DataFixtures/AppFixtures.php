<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // --- Admin OWNER (pas de client) ---
        $admin = new User();
        $admin->setEmail('admin@owner.fr');
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        // --- Client 1 : ACME SARL ---
        $acme = new Client();
        $acme->setName('ACME SARL');
        $acme->setSiren('123456789');
        $manager->persist($acme);

        $acmeUser = new User();
        $acmeUser->setEmail('client-acme@owner.fr');
        $acmeUser->setRoles([User::ROLE_CLIENT]);
        $acmeUser->setPassword($this->hasher->hashPassword($acmeUser, 'client1234'));
        $acmeUser->setClient($acme);
        $manager->persist($acmeUser);

        // --- Client 2 : BETA SAS ---
        $beta = new Client();
        $beta->setName('BETA SAS');
        $beta->setSiren('987654321');
        $manager->persist($beta);

        $betaUser = new User();
        $betaUser->setEmail('client-beta@owner.fr');
        $betaUser->setRoles([User::ROLE_CLIENT]);
        $betaUser->setPassword($this->hasher->hashPassword($betaUser, 'client1234'));
        $betaUser->setClient($beta);
        $manager->persist($betaUser);

        $manager->flush();
    }
}
