<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Model\Box;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Packaging
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $externalId;

    #[ORM\Column(type: Types::FLOAT)]
    private float $width;

    #[ORM\Column(type: Types::FLOAT)]
    private float $height;

    #[ORM\Column(type: Types::FLOAT)]
    private float $length;

    #[ORM\Column(type: Types::FLOAT)]
    private float $maxWeight;

    public function __construct(string $externalId, float $width, float $height, float $length, float $maxWeight)
    {
        $this->externalId = $externalId;
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->maxWeight = $maxWeight;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }

    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function setWidth(float $width): void
    {
        $this->width = $width;
    }

    public function setHeight(float $height): void
    {
        $this->height = $height;
    }

    public function setLength(float $length): void
    {
        $this->length = $length;
    }

    public function setMaxWeight(float $maxWeight): void
    {
        $this->maxWeight = $maxWeight;
    }

    public function toModel(): Box
    {
        return new Box(
            id: $this->id,
            externalId: $this->externalId,
            width: $this->width,
            height: $this->height,
            length: $this->length,
            maxWeight: $this->maxWeight,
        );
    }
}
