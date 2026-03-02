<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {

    $json = function (Response $response, $data) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    };

    // Health check
    $app->get('/health', function (Request $request, Response $response) use ($json) {
        return $json($response, ["ok" => true]);
    });

    // Q1
    $app->get('/q1', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT p.pnome
            FROM Pezzi p
            JOIN Catalogo c ON c.pid = p.pid
        ");
        return $json($response, $stmt->fetchAll());
    });




 // Q2 corretta
$app->get('/q2', function ($request, $response) use ($json) {
    $pdo = $this->get(\PDO::class);

    $stmt = $pdo->query("
        SELECT f.fnome
        FROM Fornitori f
        JOIN Catalogo c ON c.fid = f.fid
        GROUP BY f.fid
        HAVING COUNT(DISTINCT c.pid) =
            (SELECT COUNT(DISTINCT pid) FROM Catalogo)
    ");

    return $json($response, $stmt->fetchAll());
});

    // Q3 (parametro colore)
    $app->get('/q3', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $colore = $request->getQueryParams()['colore'] ?? 'rosso';

        $stmt = $pdo->prepare("
            SELECT f.fnome
            FROM Fornitori f
            JOIN Catalogo c ON c.fid = f.fid
            JOIN Pezzi p ON p.pid = c.pid
            WHERE p.colore = :colore
            GROUP BY f.fid
            HAVING COUNT(DISTINCT p.pid) =
                (SELECT COUNT(*) FROM Pezzi WHERE colore = :colore)
        ");

        $stmt->execute(['colore' => $colore]);
        return $json($response, $stmt->fetchAll());
    });

    // Q4 (parametro nome fornitore)
    $app->get('/q4', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $nome = $request->getQueryParams()['nome'] ?? 'Acme';

        $stmt = $pdo->prepare("
            SELECT p.pnome
            FROM Pezzi p
            JOIN Catalogo c ON c.pid = p.pid
            JOIN Fornitori f ON f.fid = c.fid
            GROUP BY p.pid
            HAVING SUM(f.fnome = :nome) > 0
               AND COUNT(DISTINCT c.fid) = 1
        ");

        $stmt->execute(['nome' => $nome]);
        return $json($response, $stmt->fetchAll());
    });

    // Q5
    $app->get('/q5', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT fid
            FROM Catalogo c1
            WHERE costo > (
                SELECT AVG(costo)
                FROM Catalogo c2
                WHERE c2.pid = c1.pid
            )
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q6
    $app->get('/q6', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT p.pid, p.pnome, f.fnome, c.costo
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            JOIN Fornitori f ON f.fid = c.fid
            WHERE c.costo = (
                SELECT MAX(c2.costo)
                FROM Catalogo c2
                WHERE c2.pid = c.pid
            )
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q7
    $app->get('/q7', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            GROUP BY c.fid
            HAVING SUM(p.colore <> 'rosso') = 0
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q8
    $app->get('/q8', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            GROUP BY c.fid
            HAVING SUM(p.colore='rosso')>0
               AND SUM(p.colore='verde')>0
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q9
    $app->get('/q9', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT DISTINCT c.fid
            FROM Catalogo c
            JOIN Pezzi p ON p.pid = c.pid
            WHERE p.colore IN ('rosso','verde')
        ");
        return $json($response, $stmt->fetchAll());
    });

    // Q10
    $app->get('/q10', function ($request, $response) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->query("
            SELECT pid
            FROM Catalogo
            GROUP BY pid
            HAVING COUNT(DISTINCT fid) >= 2
        ");
        return $json($response, $stmt->fetchAll());
    });


    // Q11 - tutti i pezzi (anche senza fornitore)
$app->get('/q11', function ($request, $response) use ($json) {
    $pdo = $this->get(\PDO::class);

    $stmt = $pdo->query("
        SELECT pid, pnome, colore
        FROM Pezzi
        ORDER BY pnome
    ");

    return $json($response, $stmt->fetchAll());
});

    // generic pagination helper
    $paginate = function($pdo, $baseQuery, $params, $page, $perPage) {
        // count total rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $baseQuery . ")");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare($baseQuery . " LIMIT :lim OFFSET :off");
        // bind params by position or name
        foreach ($params as $k => $v) {
            if (is_int($k)) {
                $stmt->bindValue($k+1, $v);
            } else {
                $stmt->bindValue(":$k", $v);
            }
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return ['data' => $stmt->fetchAll(), 'page' => $page, 'per_page' => $perPage, 'total' => $total];
    };

    // authentication routes
    $app->post('/register', function($request,$response) use($json){
        $pdo = $this->get(\PDO::class);
        $body = $request->getParsedBody();
        $fnome = $body['fnome'] ?? '';
        $indirizzo = $body['indirizzo'] ?? '';
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';
        if (!$fnome || !$indirizzo || !$username || !$password) {
            return $response->withStatus(400)->write(json_encode(['error'=>'missing fields']));
        }
        $fid = 'F' . time();
        $passhash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO Fornitori(fid,fnome,indirizzo,username,password) VALUES(:fid,:fnome,:indirizzo,:username,:password)");
        $stmt->execute(['fid'=>$fid,'fnome'=>$fnome,'indirizzo'=>$indirizzo,'username'=>$username,'password'=>$passhash]);
        // auto login
        session_start();
        $_SESSION['fid'] = $fid;
        return $json($response, ['fid'=>$fid,'fnome'=>$fnome,'indirizzo'=>$indirizzo]);
    });
    $app->post('/login', function($request,$response) use($json){
        $pdo = $this->get(\PDO::class);
        $body = $request->getParsedBody();
        $user = $body['username'] ?? '';
        $pass = $body['password'] ?? '';
        $stmt = $pdo->prepare("SELECT fid,fnome,password FROM Fornitori WHERE username=:user");
        $stmt->execute(['user'=>$user]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($pass, $row['password'])) {
            return $response->withStatus(401)->write(json_encode(['error'=>'invalid']));
        }
        session_start();
        $_SESSION['fid'] = $row['fid'];
        return $json($response, ['fid'=>$row['fid'],'fnome'=>$row['fnome']]);
    });
    $app->post('/logout', function($request,$response) {
        session_start();
        session_destroy();
        return $response->withStatus(204);
    });
    $app->get('/me', function($request,$response) use($json){
        session_start();
        if(empty($_SESSION['fid'])){
            return $response->withStatus(401);
        }
        $fid = $_SESSION['fid'];
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->prepare("SELECT fid,fnome,indirizzo FROM Fornitori WHERE fid=:fid");
        $stmt->execute(['fid'=>$fid]);
        $row = $stmt->fetch();
        if (!$row) return $response->withStatus(401);
        return $json($response,$row);
    });

    // suppliers CRUD & pagination
    $app->get('/suppliers', function ($request, $response) use ($json, $paginate) {
        $pdo = $this->get(\PDO::class);
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        $per = max(1, min(50, (int)($request->getQueryParams()['per_page'] ?? 10)));
        $base = "SELECT fid, fnome, indirizzo FROM Fornitori ORDER BY fnome";
        $result = $paginate($pdo, $base, [], $page, $per);
        return $json($response, $result);
    });
    $app->get('/suppliers/{fid}', function ($request, $response, $args) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->prepare("SELECT fid,fnome,indirizzo FROM Fornitori WHERE fid=:fid");
        $stmt->execute(['fid'=>$args['fid']]);
        $row = $stmt->fetch();
        if (!$row) return $response->withStatus(404);
        return $json($response, $row);
    });
    $app->post('/suppliers', function ($request, $response) use ($json) {
        // admin endpoint for creating supplier (may include username/password)
        $pdo = $this->get(\PDO::class);
        $body = $request->getParsedBody();
        $fnome = $body['fnome'] ?? '';
        $indirizzo = $body['indirizzo'] ?? '';
        if ($fnome === '' || $indirizzo === '') {
            return $response->withStatus(400)->withHeader('Content-Type','application/json')
                ->write(json_encode(['error'=>'missing fields']));
        }
        $fid = 'F' . time();
        $stmt = $pdo->prepare("INSERT INTO Fornitori(fid,fnome,indirizzo,username,password) VALUES(:fid,:fnome,:indirizzo,:username,:password)");
        $passhash = null;
        if (!empty($body['password'])) {
            $passhash = password_hash($body['password'], PASSWORD_DEFAULT);
        }
        $stmt->execute([
            'fid'=>$fid,
            'fnome'=>$fnome,
            'indirizzo'=>$indirizzo,
            'username'=> $body['username'] ?? null,
            'password'=> $passhash,
        ]);
        return $json($response, ['fid'=>$fid,'fnome'=>$fnome,'indirizzo'=>$indirizzo]);
    });
    $app->put('/suppliers/{fid}', function ($request, $response, $args) use ($json) {
        session_start();
        if (empty($_SESSION['fid']) || $_SESSION['fid'] !== $args['fid']) {
            return $response->withStatus(403);
        }
        $pdo = $this->get(\PDO::class);
        $body = $request->getParsedBody();
        $stmt = $pdo->prepare("UPDATE Fornitori SET fnome=:fnome, indirizzo=:indirizzo WHERE fid=:fid");
        $stmt->execute(['fnome'=>$body['fnome'],'indirizzo'=>$body['indirizzo'],'fid'=>$args['fid']]);
        return $json($response, ['updated'=>true]);
    });
    $app->delete('/suppliers/{fid}', function ($request, $response, $args) use ($json) {
        session_start();
        if (empty($_SESSION['fid']) || $_SESSION['fid'] !== $args['fid']) {
            return $response->withStatus(403);
        }
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->prepare("DELETE FROM Fornitori WHERE fid=:fid");
        $stmt->execute(['fid'=>$args['fid']]);
        return $json($response, ['deleted'=>true]);
    });

    // supplier catalog management
    $app->get('/suppliers/{fid}/catalog', function ($request, $response, $args) use ($json, $paginate) {
        $pdo = $this->get(\PDO::class);
        $fid = $args['fid'];
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        $per = max(1, min(50, (int)($request->getQueryParams()['per_page'] ?? 10)));
        $base = "SELECT c.pid, p.pnome, p.colore, c.costo
                 FROM Catalogo c
                 JOIN Pezzi p ON p.pid = c.pid
                 WHERE c.fid = :fid
                 ORDER BY p.pnome";
        $result = $paginate($pdo, $base, ['fid'=>$fid], $page, $per);
        return $json($response, $result);
    });
    $app->get('/suppliers/{fid}/catalog/{pid}', function ($request, $response, $args) use ($json) {
        $pdo = $this->get(\PDO::class);
        $stmt = $pdo->prepare("SELECT c.*, p.pnome, p.colore FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid WHERE c.fid=:fid AND c.pid=:pid");
        $stmt->execute(['fid'=>$args['fid'],'pid'=>$args['pid']]);
        $row = $stmt->fetch();
        if (!$row) return $response->withStatus(404);
        return $json($response, $row);
    });
    $app->post('/suppliers/{fid}/catalog', function ($request, $response, $args) use ($json) {
        session_start();
        $fid = $args['fid'];
        if (empty($_SESSION['fid']) || $_SESSION['fid'] !== $fid) {
            return $response->withStatus(403);
        }
        $pdo = $this->get(\PDO::class);
        $body = $request->getParsedBody();
        $pid = $body['pid'] ?? null;
        $pnome = $body['pnome'] ?? null;
        $colore = $body['colore'] ?? null;
        $costo = isset($body['costo']) ? (float)$body['costo'] : null;
        if (!$pid || $costo === null) {
            return $response->withStatus(400)->write(json_encode(['error'=>'pid and costo required']));
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Pezzi WHERE pid=:pid");
        $stmt->execute(['pid'=>$pid]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO Pezzi(pid,pnome,colore) VALUES(:pid,:pnome,:colore)");
            $stmt->execute(['pid'=>$pid,'pnome'=>$pnome,'colore'=>$colore]);
        } elseif ($pnome || $colore) {
            $update = [];
            $params = ['pid'=>$pid];
            if ($pnome) { $update[] = "pnome=:pnome"; $params['pnome']=$pnome; }
            if ($colore) { $update[] = "colore=:colore"; $params['colore']=$colore; }
            if ($update) {
                $pdo->prepare("UPDATE Pezzi SET " . implode(',', $update) . " WHERE pid=:pid")->execute($params);
            }
        }
        $pdo->prepare("REPLACE INTO Catalogo(fid,pid,costo) VALUES(:fid,:pid,:costo)")
            ->execute(['fid'=>$fid,'pid'=>$pid,'costo'=>$costo]);
        return $json($response, ['ok'=>true]);
    });
    $app->put('/suppliers/{fid}/catalog/{pid}', function ($request, $response, $args) use ($json) {
        session_start();
        $fid = $args['fid'];
        if (empty($_SESSION['fid']) || $_SESSION['fid'] !== $fid) {
            return $response->withStatus(403);
        }
        $pdo = $this->get(\PDO::class);
        $pid = $args['pid'];
        $body = $request->getParsedBody();
        if (isset($body['costo'])) {
            $pdo->prepare("UPDATE Catalogo SET costo=:costo WHERE fid=:fid AND pid=:pid")
                ->execute(['costo'=>$body['costo'],'fid'=>$fid,'pid'=>$pid]);
        }
        if (isset($body['pnome']) || isset($body['colore'])) {
            $update=[];$params=['pid'=>$pid];
            if(isset($body['pnome'])){ $update[]='pnome=:pnome'; $params['pnome']=$body['pnome']; }
            if(isset($body['colore'])){ $update[]='colore=:colore'; $params['colore']=$body['colore']; }
            if($update){ $pdo->prepare("UPDATE Pezzi SET " . implode(',', $update) . " WHERE pid=:pid")->execute($params); }
        }
        return $json($response, ['ok'=>true]);
    });
    $app->delete('/suppliers/{fid}/catalog/{pid}', function ($request, $response, $args) use ($json) {
        session_start();
        $fid = $args['fid'];
        if (empty($_SESSION['fid']) || $_SESSION['fid'] !== $fid) {
            return $response->withStatus(403);
        }
        $pdo = $this->get(\PDO::class);
        $pdo->prepare("DELETE FROM Catalogo WHERE fid=:fid AND pid=:pid")
            ->execute(['fid'=>$args['fid'],'pid'=>$args['pid']]);
        return $json($response, ['ok'=>true]);
    });

    // pieces list for admin
    $app->get('/pieces', function ($request, $response) use ($json, $paginate) {
        $pdo = $this->get(\PDO::class);
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        $per = max(1, min(50, (int)($request->getQueryParams()['per_page'] ?? 10)));
        $base = "SELECT pid,pnome,colore FROM Pezzi ORDER BY pnome";
        $result = $paginate($pdo, $base, [], $page, $per);
        return $json($response, $result);
    });

};