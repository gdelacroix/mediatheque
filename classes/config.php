<?php

function getConnexion(): PDO
{
    // $host     = '127.0.0.1';
    // $dbname   = 'mediatheque';
    // $user     = 'root';
    // $password = '';
    
     // getenv() lit les variables d'environnement injectées par Docker.
    // Si la variable n'existe pas (ex : en local sans Docker),
    // on utilise la valeur par défaut après le "??".
    $host     = getenv('DB_HOST')     ?: '127.0.0.1';
    $dbname   = getenv('DB_NAME')     ?: 'mediatheque';
    $user     = getenv('DB_USER')     ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;

    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}