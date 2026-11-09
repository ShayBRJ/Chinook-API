<?php
header('Content-Type: application/json; charset=utf-8');

// OBTENCIÓN Y COMPROBACIÓN DE LAS VARIABLES DE ENTORNO.
$host = getenv('DB_HOST') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: '';

if ($host === '' || $user === '' || $pass === '' || $dbname === '') {
    http_response_code(500);
    echo json_encode([
        'error' => 'Faltan variables de entorno para la conexión.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// OBTENCIÓN Y COMPROBACIÓN DE LOS PARÁMETROS GET.
if (!isset($_GET['id']) ||
    !is_string($_GET['id']) ||
    !ctype_digit($_GET['id']) ||
    filter_var($_GET['id'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]) === false) {

    http_response_code(400);
    echo json_encode([
        'error' => 'Falta el parámetro id o no es un número'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) $_GET['id'];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

    // CONSULTA PARA OBTENER LOS DATOS DEL ARTISTA.
    $query = "SELECT
                ArtistId AS id,
                Name AS nombre
              FROM Artist
              WHERE ArtistId = :id";

    $consulta = $pdo->prepare($query);
    $consulta->bindValue(':id', $id, PDO::PARAM_INT);
    $consulta->execute();

    $artista = $consulta->fetch(PDO::FETCH_ASSOC);

    // COMPROBACIÓN DE LA EXISTENCIA DEL ARTISTA.
    if (!$artista) {
        http_response_code(404);
        echo json_encode([
            'error' => "No existe el artista $id"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CONSULTA PARA OBTENER LOS ÁLBUMES Y SUS CANCIONES.
    $query = "SELECT
                a.AlbumId AS id,
                a.Title AS titulo,
                COUNT(t.TrackId) AS canciones
              FROM Album a
              LEFT JOIN Track t
                ON a.AlbumId = t.AlbumId
              WHERE a.ArtistId = :id
              GROUP BY a.AlbumId, a.Title
              ORDER BY a.Title ASC";

    $consulta = $pdo->prepare($query);
    $consulta->bindValue(':id', $id, PDO::PARAM_INT);
    $consulta->execute();

    // OBTENCIÓN DE LOS DATOS AGREGADOS.
    $albumes = $consulta->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'id' => (int) $artista['id'],
        'nombre' => $artista['nombre'],
        'albumes' => $albumes
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
       JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al consultar la base de datos.'
    ], JSON_UNESCAPED_UNICODE);
}
