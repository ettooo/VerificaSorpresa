<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use App\Domain\Piece\Piece;
use Psr\Http\Message\ResponseInterface as Response;

class UpdatePieceAction extends PieceAction
{
    protected function action(): Response
    {
        $pieceId = $this->resolveArg('id');
        $data = $this->getFormData();
        
        $piece = new Piece($pieceId, $data['pnome'], $data['colore']);
        $this->pieceRepository->update($piece);

        $this->logger->info("Piece `${pieceId}` was updated.");

        return $this->respondWithData($piece);
    }
}
