<?php
// File: delete.php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Verifica se l'utente ha i permessi di eliminazione
if (!in_array('delete', $_SESSION["permessi"])) {
    header("location: index.php");
    exit;
}

require_once "config.php";

// Verifica se l'ID del documento è specificato
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: index.php");
    exit;
}

$doc_id = intval($_GET["id"]);
$message = '';
$type = '';

// Recupera informazioni sul documento
$sql = "SELECT * FROM documenti WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $doc_id;

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $documento = $result->fetch_assoc();

            // Elimina il documento dal database
            $sql_delete = "DELETE FROM documenti WHERE id = ?";

            if ($stmt_delete = $conn->prepare($sql_delete)) {
                $stmt_delete->bind_param("i", $param_id);

                if ($stmt_delete->execute()) {
                    // Elimina il file fisico
                    if (file_exists($documento['percorso_file'])) {
                        if (unlink($documento['percorso_file'])) {
                            $message = "Documento eliminato con successo.";
                            $type = "success";
                        } else {
                            $message = "Il documento è stato rimosso dal database, ma non è stato possibile eliminare il file fisico.";
                            $type = "warning";
                        }
                    } else {
                        $message = "Documento eliminato dal database, ma il file fisico non è stato trovato.";
                        $type = "warning";
                    }
                } else {
                    $message = "Errore durante l'eliminazione del documento.";
                    $type = "danger";
                }

                $stmt_delete->close();
            } else {
                $message = "Errore nella preparazione della query di eliminazione.";
                $type = "danger";
            }
        } else {
            $message = "Documento non trovato.";
            $type = "danger";
        }
    } else {
        $message = "Errore durante il recupero del documento.";
        $type = "danger";
    }

    $stmt->close();
} else {
    $message = "Errore nella preparazione della query.";
    $type = "danger";
}

// Reindirizza con messaggio
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $type;
header("location: index.php");
exit;
