<?php

declare(strict_types=1);

namespace App\Domain\Supplier;

use JsonSerializable;

class Supplier implements JsonSerializable
{
    private ?string $fid;
    private string $fnome;
    private string $indirizzo;

    public function __construct(?string $fid, string $fnome, string $indirizzo)
    {
        $this->fid = $fid;
        $this->fnome = $fnome;
        $this->indirizzo = $indirizzo;
    }

    public function getFid(): ?string
    {
        return $this->fid;
    }

    public function getFnome(): string
    {
        return $this->fnome;
    }

    public function getIndirizzo(): string
    {
        return $this->indirizzo;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'fid' => $this->fid,
            'fnome' => $this->fnome,
            'indirizzo' => $this->indirizzo,
        ];
    }
}
