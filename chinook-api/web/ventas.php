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
$pais = null;
if (isset($_GET['pais'])) {
    if (!is_string($_GET['pais']) || trim($_GET['pais']) === '') {
        http_response_code(400);
        echo json_encode([
            'error' => 'El parámetro pais debe ser una cadena no vacía.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pais = trim($_GET['pais']);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass );
    $query = "SELECT
              BillingCountry AS Pais,
              COUNT(InvoiceId) AS Facturas,
              SUM(Total) AS Total
              FROM Invoice";
    if ($pais) {
        $query .= " WHERE BillingCountry = :pais";
    }
    $query .= " GROUP BY BillingCountry ORDER BY SUM(Total) DESC";

    // CONSULTA PARA OBTENER EL TOTAL DE PAÍSES.
    $cuenta = $pdo->prepare("SELECT COUNT(*) FROM ($query) AS ventas");
    if ($pais) {
        $cuenta->bindValue(':pais', $pais, PDO::PARAM_STR);
    }
    $cuenta->execute();
    $total = (int) $cuenta->fetchColumn();


    // CONSULTA PARA OBTENER LOS DATOS AGREGADOS.
    $consulta = $pdo->prepare($query);
    if ($pais) $consulta->bindValue(':pais', $pais, PDO::PARAM_STR);
    $consulta->execute();

    echo json_encode([
        'total'     => $total,
        'datos'     => $consulta->fetchAll(PDO::FETCH_ASSOC),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK); // Utilizamos JSON_NUMERIC_CHECK y comprobamos aquellos valores numéricos de la consulta.
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}