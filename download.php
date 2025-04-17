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

// Recupera informazioni sul documento e verifica se l'utente ha accesso
$sql = "SELECT d.* 
        FROM documenti d
        LEFT JOIN documenti_ruoli dr ON d.id = dr.documento_id
        WHERE d.id = ? AND (
            dr.ruolo_id IS NULL OR 
            dr.ruolo_id IN (
                SELECT ur.ruolo_id 
                FROM utenti_ruoli ur 
                WHERE ur.utente_id = ?
            )
            OR NOT EXISTS (
                SELECT 1 FROM documenti_ruoli dr2 WHERE dr2.documento_id = d.id
            )
        )";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $param_id, $param_user_id);
    $param_id = $doc_id;
    $param_user_id = $_SESSION["id"];

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $documento = $result->fetch_assoc();

            // Verifica che il file esista
            if (file_exists($documento['percorso_file'])) {
                // Registra il download nel log (opzionale)
                $sql_log = "INSERT INTO log_downloads (documento_id, utente_id, data_download) VALUES (?, ?, NOW())";

                // Commenta o rimuovi questo blocco se non hai la tabella log_downloads
                /*
                if ($stmt_log = $conn->prepare($sql_log)) {
                    $stmt_log->bind_param("ii", $doc_id, $_SESSION["id"]);
                    $stmt_log->execute();
                    $stmt_log->close();
                }
                */

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
                $_SESSION['message'] = "Il file richiesto non esiste più sul server.";
                $_SESSION['message_type'] = "danger";
                header("location: index.php");
                exit;
            }
        } else {
            // Documento non trovato o utente non autorizzato
            $_SESSION['message'] = "Non hai l'autorizzazione per scaricare questo documento.";
            $_SESSION['message_type'] = "danger";
            header("location: index.php");
            exit;
        }
    } else {
        $_SESSION['message'] = "Errore durante il recupero del documento.";
        $_SESSION['message_type'] = "danger";
        header("location: index.php");
        exit;
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "Errore nella preparazione della query.";
    $_SESSION['message_type'] = "danger";
    header("location: index.php");
    exit;
}
