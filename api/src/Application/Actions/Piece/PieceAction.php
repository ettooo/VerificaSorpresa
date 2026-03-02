<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use App\Application\Actions\Action;
use App\Domain\Piece\PieceRepository;
use Psr\Log\LoggerInterface;

abstract class PieceAction extends Action
{
    protected PieceRepository $pieceRepository;

    public function __construct(LoggerInterface $logger, PieceRepository $pieceRepository)
    {
        parent::__construct($logger);
        $this->pieceRepository = $pieceRepository;
    }
}
