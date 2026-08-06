<?php
// Oturumu (Session) tüm sayfalarda kullanabilmek için burada başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = 'localhost'; 
$db       = 'elesexpo_trabzonkm';
$user     = 'elesexpo_trabzonkm';
$password = '***REMOVED***';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $db_baglanti = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
     die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
