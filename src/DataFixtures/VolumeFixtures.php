<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Document;
use App\Entity\TimelineEvent;
use App\Entity\Upload;
use App\Entity\User;
use App\Enum\DocumentNature;
use App\Enum\DocumentStatus;
use App\Enum\TimelineEventType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VolumeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $beta = $manager->getRepository(Client::class)->findOneBy(['name' => 'BETA SAS']);
        $betaUser = $manager->getRepository(User::class)->findOneBy(['email' => 'client-beta@owner.fr']);

        if (!$beta || !$betaUser) {
            return;
        }

        $natures = DocumentNature::cases();
        $statuses = [DocumentStatus::COMPLETED, DocumentStatus::COMPLETED, DocumentStatus::FAILED, DocumentStatus::PROCESSING];

        // 3 uploads de 40 documents = 120 docs
        for ($batch = 0; $batch < 3; $batch++) {
            $upload = new Upload();
            $upload->setClient($beta);
            $upload->setStatus(DocumentStatus::RECEIVED);
            $upload->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', $batch * 15)));
            $manager->persist($upload);

            for ($i = 0; $i < 40; $i++) {
                $doc = new Document();
                $doc->setClient($beta);
                $doc->setUpload($upload);
                $doc->setOriginalName(sprintf('document-%d-%02d.pdf', $batch, $i));
                $doc->setStoredPath(sprintf('client-%d/fake-%d-%d.pdf', $beta->getId(), $batch, $i));
                $doc->setMimeType('application/pdf');
                $doc->setSize(random_int(50000, 5000000));
                $doc->setNature($natures[array_rand($natures)]);
                $doc->setPeriodYear(2024);
                $doc->setPeriodMonth(random_int(1, 12));
                $doc->setStatus($statuses[array_rand($statuses)]);
                $doc->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days -%d hours', $batch * 15, $i)));
                $manager->persist($doc);

                $event = new TimelineEvent();
                $event->setDocument($doc);
                $event->setType(TimelineEventType::UPLOADED);
                $event->setMessage(sprintf('Fichier « %s » déposé', $doc->getOriginalName()));
                $event->setActor($betaUser);
                $event->setCreatedAt($doc->getCreatedAt());
                $manager->persist($event);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }
}