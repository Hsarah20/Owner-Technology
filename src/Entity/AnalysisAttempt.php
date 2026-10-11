<?php

namespace App\Entity;

use App\Repository\AnalysisAttemptRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\DocumentStatus;
use App\Enum\FailureReason;

#[ORM\Entity(repositoryClass: AnalysisAttemptRepository::class)]
class AnalysisAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $attemptNumber = null;

    #[ORM\Column(length: 255,enumType: DocumentStatus::class)]
    private DocumentStatus $status = DocumentStatus::PENDING;

    #[ORM\Column(length: 255, nullable: true, enumType: FailureReason::class)]
    private ?FailureReason $failureReason = null;

    #[ORM\Column(nullable: true)]
    private ?array $result = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\ManyToOne(inversedBy: 'analysisAttempts')]
    private ?User $triggeredBy = null;

    #[ORM\ManyToOne(inversedBy: 'analysisAttempts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Document $document = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttemptNumber(): ?int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): static
    {
        $this->attemptNumber = $attemptNumber;

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

    public function getFailureReason(): ?FailureReason
    {
        return $this->failureReason;
    }

    public function setFailureReason(?FailureReason $failureReason): static
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(?array $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getTriggeredBy(): ?User
    {
        return $this->triggeredBy;
    }

    public function setTriggeredBy(?User $triggeredBy): static
    {
        $this->triggeredBy = $triggeredBy;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }
}
