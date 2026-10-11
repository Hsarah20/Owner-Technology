<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Client;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public const ROLE_CLIENT = 'ROLE_CLIENT';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Client $client = null;

    /**
     * @var Collection<int, AnalysisAttempt>
     */
    #[ORM\OneToMany(targetEntity: AnalysisAttempt::class, mappedBy: 'triggeredBy')]
    private Collection $analysisAttempts;

    /**
     * @var Collection<int, TimelineEvent>
     */
    #[ORM\OneToMany(targetEntity: TimelineEvent::class, mappedBy: 'actor')]
    private Collection $timelineEvents;

    public function __construct()
    {
        $this->analysisAttempts = new ArrayCollection();
        $this->timelineEvents = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, AnalysisAttempt>
     */
    public function getAnalysisAttempts(): Collection
    {
        return $this->analysisAttempts;
    }

    public function addAnalysisAttempt(AnalysisAttempt $analysisAttempt): static
    {
        if (!$this->analysisAttempts->contains($analysisAttempt)) {
            $this->analysisAttempts->add($analysisAttempt);
            $analysisAttempt->setTriggeredBy($this);
        }

        return $this;
    }

    public function removeAnalysisAttempt(AnalysisAttempt $analysisAttempt): static
    {
        if ($this->analysisAttempts->removeElement($analysisAttempt)) {
            // set the owning side to null (unless already changed)
            if ($analysisAttempt->getTriggeredBy() === $this) {
                $analysisAttempt->setTriggeredBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TimelineEvent>
     */
    public function getTimelineEvents(): Collection
    {
        return $this->timelineEvents;
    }

    public function addTimelineEvent(TimelineEvent $timelineEvent): static
    {
        if (!$this->timelineEvents->contains($timelineEvent)) {
            $this->timelineEvents->add($timelineEvent);
            $timelineEvent->setActor($this);
        }

        return $this;
    }

    public function removeTimelineEvent(TimelineEvent $timelineEvent): static
    {
        if ($this->timelineEvents->removeElement($timelineEvent)) {
            // set the owning side to null (unless already changed)
            if ($timelineEvent->getActor() === $this) {
                $timelineEvent->setActor(null);
            }
        }

        return $this;
    }
}
