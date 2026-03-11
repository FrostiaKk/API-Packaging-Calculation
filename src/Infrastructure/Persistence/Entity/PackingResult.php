<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class PackingResult
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', length: 16)]
    private UuidInterface $id;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private string $inputHash;

    #[ORM\Column(type: Types::TEXT)]
    private string $requestPayload;

    #[ORM\Column(type: Types::FLOAT)]
    private float $totalWeight;

    #[ORM\ManyToOne(targetEntity: Packaging::class)]
    #[ORM\JoinColumn(name: 'box_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Packaging $box;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        UuidInterface $id,
        string $inputHash,
        string $requestPayload,
        float $totalWeight,
        ?Packaging $box,
    ) {
        $this->id = $id;
        $this->inputHash = $inputHash;
        $this->requestPayload = $requestPayload;
        $this->totalWeight = $totalWeight;
        $this->box = $box;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getInputHash(): string
    {
        return $this->inputHash;
    }

    public function getRequestPayload(): string
    {
        return $this->requestPayload;
    }

    public function getTotalWeight(): float
    {
        return $this->totalWeight;
    }

    public function getBox(): ?Packaging
    {
        return $this->box;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
