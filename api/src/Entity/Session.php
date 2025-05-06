<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private readonly License $license;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private readonly Location $location;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private readonly \DateTimeImmutable $date;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $lastUpdated;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    private bool $active = true;

    public function __construct(License $license, Location $location)
    {
        $tz = new \DateTimeZone('UTC');
        $this->date = new \DateTimeImmutable('now', $tz);
        $this->lastUpdated = new \DateTime('now', $tz);

        $this->license = $license;
        $license->addSession($this);

        $this->location = $location;
        $location->addSession($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicense(): License
    {
        return $this->license;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function update(): static
    {
        $this->lastUpdated = new \DateTime('now', new \DateTimeZone('UTC'));

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function terminate(): static
    {
        if (!$this->active) {
            return $this;
        }

        $this->active = false;
        $tz = new \DateTimeZone('UTC');
        $this->lastUpdated = new \DateTime('now', $tz);
        $this->endDate = new \DateTimeImmutable('now', $tz);

        return $this;
    }
}
