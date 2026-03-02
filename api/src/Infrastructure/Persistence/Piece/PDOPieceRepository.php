<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Piece;

use App\Domain\Piece\Piece;
use App\Domain\Piece\PieceRepository;
use PDO;
use RuntimeException;

class PDOPieceRepository implements PieceRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findAll(int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->connection->prepare("SELECT pid, pnome, colore FROM Pezzi LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $pieces = [];
        while ($row = $stmt->fetch()) {
            $pieces[] = new Piece($row['pid'], $row['pnome'], $row['colore']);
        }

        return $pieces;
    }

    public function findPieceOfId(string $id): Piece
    {
        $stmt = $this->connection->prepare("SELECT pid, pnome, colore FROM Pezzi WHERE pid = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException("Piece not found");
        }

        return new Piece($row['pid'], $row['pnome'], $row['colore']);
    }

    public function save(Piece $piece): void
    {
        $stmt = $this->connection->prepare("INSERT INTO Pezzi (pid, pnome, colore) VALUES (?, ?, ?)");
        $stmt->execute([$piece->getPid(), $piece->getPnome(), $piece->getColore()]);
    }

    public function update(Piece $piece): void
    {
        $stmt = $this->connection->prepare("UPDATE Pezzi SET pnome = ?, colore = ? WHERE pid = ?");
        $stmt->execute([$piece->getPnome(), $piece->getColore(), $piece->getPid()]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM Pezzi WHERE pid = ?");
        $stmt->execute([$id]);
        
        // Also remove from catalog
        $stmt = $this->connection->prepare("DELETE FROM Catalogo WHERE pid = ?");
        $stmt->execute([$id]);
    }

    public function countAll(): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM Pezzi")->fetchColumn();
    }
}
