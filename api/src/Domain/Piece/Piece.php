<?php

declare(strict_types=1);

namespace App\Domain\Piece;

use JsonSerializable;

class Piece implements JsonSerializable
{
    private ?string $pid;
    private string $pnome;
    private string $colore;

    public function __construct(?string $pid, string $pnome, string $colore)
    {
        $this->pid = $pid;
        $this->pnome = $pnome;
        $this->colore = $colore;
    }

    public function getPid(): ?string
    {
        return $this->pid;
    }

    public function getPnome(): string
    {
        return $this->pnome;
    }

    public function getColore(): string
    {
        return $this->colore;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'pid' => $this->pid,
            'pnome' => $this->pnome,
            'colore' => $this->colore,
        ];
    }
}
