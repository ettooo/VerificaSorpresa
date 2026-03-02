<?php

declare(strict_types=1);

namespace App\Domain\Piece;

interface PieceRepository
{
    /**
     * @param int $limit
     * @param int $offset
     * @return Piece[]
     */
    public function findAll(int $limit = 10, int $offset = 0): array;

    /**
     * @param string $id
     * @return Piece
     */
    public function findPieceOfId(string $id): Piece;

    /**
     * @param Piece $piece
     * @return void
     */
    public function save(Piece $piece): void;

    /**
     * @param Piece $piece
     * @return void
     */
    public function update(Piece $piece): void;

    /**
     * @param string $id
     * @return void
     */
    public function delete(string $id): void;

    /**
     * @return int
     */
    public function countAll(): int;
}
