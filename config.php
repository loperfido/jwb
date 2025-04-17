<?php
// File: config.php
// Configurazione del database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'cry');
define('DB_NAME', 'pdf_management');

// Percorso per i file caricati
define('UPLOAD_DIR', 'uploads/');

// Connessione al database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
