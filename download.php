<?php
// File: download.php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Verifica se l'ID del documento è specificato
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: index.php");
    exit;
}

$doc_id = intval($_GET["id"]);

// Recupera informazioni sul documento
$sql = "SELECT * FROM documenti WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $doc_id;

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $documento = $result->fetch_assoc();

            // Verifica che il file esista
            if (file_exists($documento['percorso_file'])) {
                // Imposta gli header per il download
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($documento['percorso_file']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($documento['percorso_file']));

                // Leggi il file e invialo al client
                readfile($documento['percorso_file']);
                exit;
            } else {
                // File non trovato
                echo "Il file richiesto non esiste più sul server.";
                exit;
            }
        } else {
            // Documento non trovato
            header("location: index.php");
            exit;
        }
    } else {
        echo "Errore durante il recupero del documento.";
        exit;
    }

    $stmt->close();
} else {
    echo "Errore nella preparazione della query.";
    exit;
}
