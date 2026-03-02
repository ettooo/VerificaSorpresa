<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use Psr\Http\Message\ResponseInterface as Response;

class DeletePieceAction extends PieceAction
{
    protected function action(): Response
    {
        $pieceId = $this->resolveArg('id');
        $this->pieceRepository->delete($pieceId);

        $this->logger->info("Piece `${pieceId}` was deleted.");

        return $this->respondWithData(null, 204);
    }
}
