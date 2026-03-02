<?php

declare(strict_types=1);

namespace App\Application\Actions\Piece;

use Psr\Http\Message\ResponseInterface as Response;

class ViewPieceAction extends PieceAction
{
    protected function action(): Response
    {
        $pieceId = $this->resolveArg('id');
        $piece = $this->pieceRepository->findPieceOfId($pieceId);

        $this->logger->info("Piece of id `${pieceId}` was viewed.");

        return $this->respondWithData($piece);
    }
}
