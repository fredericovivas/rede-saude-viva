<?php
// Copia este ficheiro para db.php e ajusta os valores à tua máquina.
$host       = 'localhost';
$porta      = 3306;        // porta por defeito do XAMPP
$bd         = 'saude_viva';
$utilizador = 'root';
$password   = '';          // em ambientes reais, define uma password forte

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$bd;charset=utf8mb4",
        $utilizador,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro BD: " . $e->getMessage());
    die("Erro na ligação à base de dados.");
}
?>