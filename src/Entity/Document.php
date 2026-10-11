<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\DocumentNature;
use App\Enum\DocumentStatus;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $storedPath = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\Column(length: 30, nullable: true, enumType: DocumentNature::class)]
    private ?DocumentNature $nature = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $period = null;

    #[ORM\Column(length: 20, enumType: DocumentStatus::class)]
    private DocumentStatus $status = DocumentStatus::RECEIVED;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Upload $upload = null;

    /**
     * @var Collection<int, AnalysisAttempt>
     */
    #[ORM\OneToMany(targetEntity: AnalysisAttempt::class, mappedBy: 'document')]
    private Collection $analysisAttempts;

    /**
     * @var Collection<int, TimelineEvent>
     */
    #[ORM\OneToMany(targetEntity: TimelineEvent::class, mappedBy: 'document')]
    private Collection $timelineEvents;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->analysisAttempts = new ArrayCollection();
        $this->timelineEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getStoredPath(): ?string
    {
        return $this->storedPath;
    }

    public function setStoredPath(string $storedPath): static
    {
        $this->storedPath = $storedPath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getNature(): ?DocumentNature
    {
        return $this->nature;
    }

    public function setNature(?DocumentNature $nature): static
    {
        $this->nature = $nature;

        return $this;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(?string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getStatus(): DocumentStatus
    {
        return $this->status;
    }

    public function setStatus(DocumentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getUpload(): ?Upload
    {
        return $this->upload;
    }

    public function setUpload(?Upload $upload): static
    {
        $this->upload = $upload;

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
            $analysisAttempt->setDocument($this);
        }

        return $this;
    }

    public function removeAnalysisAttempt(AnalysisAttempt $analysisAttempt): static
    {
        if ($this->analysisAttempts->removeElement($analysisAttempt)) {
            // set the owning side to null (unless already changed)
            if ($analysisAttempt->getDocument() === $this) {
                $analysisAttempt->setDocument(null);
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
            $timelineEvent->setDocument($this);
        }

        return $this;
    }

    public function removeTimelineEvent(TimelineEvent $timelineEvent): static
    {
        if ($this->timelineEvents->removeElement($timelineEvent)) {
            // set the owning side to null (unless already changed)
            if ($timelineEvent->getDocument() === $this) {
                $timelineEvent->setDocument(null);
            }
        }

        return $this;
    }
}
