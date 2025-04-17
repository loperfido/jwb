<?php
// File: upload.php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Verifica se l'utente ha i permessi di caricamento
if (!in_array('upload', $_SESSION["permessi"])) {
    header("location: index.php");
    exit;
}

require_once "config.php";

$error = '';
$success = '';

// Recupera tutti i ruoli
$sql_ruoli = "SELECT * FROM ruoli ORDER BY nome";
$ruoli = [];
$result_ruoli = $conn->query($sql_ruoli);

if ($result_ruoli->num_rows > 0) {
    while ($row = $result_ruoli->fetch_assoc()) {
        $ruoli[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validazione input
    if (empty(trim($_POST["titolo"]))) {
        $error = "Inserisci un titolo per il documento.";
    } else {
        $titolo = trim($_POST["titolo"]);
        $descrizione = trim($_POST["descrizione"]);

        // Controllo file
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
            $allowed = array("pdf" => "application/pdf");
            $filename = $_FILES["file"]["name"];
            $filetype = $_FILES["file"]["type"];
            $filesize = $_FILES["file"]["size"];

            // Verifica estensione
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!array_key_exists($ext, $allowed)) {
                $error = "Errore: Carica solo file PDF.";
            }

            // Verifica formato
            if (in_array($filetype, $allowed)) {
                // Limite dimensione file (5MB)
                if ($filesize > 5242880) {
                    $error = "Il file è troppo grande. Limite massimo: 5MB.";
                } else {
                    // Se tutto è ok, prepara per il caricamento
                    // Crea directory di upload se non esiste
                    if (!file_exists(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0777, true);
                    }

                    // Genera nome file unico
                    $new_filename = uniqid() . '_' . $filename;
                    $destination = UPLOAD_DIR . $new_filename;

                    // Tenta di caricare il file
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $destination)) {
                        // File caricato con successo, ora inserisci nel database
                        $sql = "INSERT INTO documenti (titolo, descrizione, percorso_file, tipo_file, dimensione, utente_id) 
                                VALUES (?, ?, ?, ?, ?, ?)";

                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("ssssii", $param_titolo, $param_descrizione, $param_percorso, $param_tipo, $param_dimensione, $param_utente_id);

                            $param_titolo = $titolo;
                            $param_descrizione = $descrizione;
                            $param_percorso = $destination;
                            $param_tipo = $filetype;
                            $param_dimensione = $filesize;
                            $param_utente_id = $_SESSION["id"];

                            if ($stmt->execute()) {
                                $documento_id = $stmt->insert_id;

                                // Assegna i ruoli con accesso al documento
                                if (isset($_POST["ruoli_accesso"]) && is_array($_POST["ruoli_accesso"])) {
                                    foreach ($_POST["ruoli_accesso"] as $ruolo_id) {
                                        $sql_role = "INSERT INTO documenti_ruoli (documento_id, ruolo_id) VALUES (?, ?)";
                                        $stmt_role = $conn->prepare($sql_role);
                                        $stmt_role->bind_param("ii", $documento_id, $ruolo_id);
                                        $stmt_role->execute();
                                        $stmt_role->close();
                                    }
                                    $success = "Documento caricato con successo e permessi assegnati.";
                                } else {
                                    // Se nessun ruolo è selezionato, il documento sarà visibile a tutti
                                    $success = "Documento caricato con successo. Il documento sarà visibile a tutti gli utenti.";
                                }
                            } else {
                                $error = "Errore durante l'inserimento del documento nel database.";
                            }

                            $stmt->close();
                        }
                    } else {
                        $error = "Errore durante il caricamento del file.";
                    }
                }
            } else {
                $error = "Errore: Il formato del file non è valido. Carica solo file PDF.";
            }
        } else {
            $error = "Seleziona un file da caricare.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carica Documento PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sistema Gestione PDF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php if (in_array('upload', $_SESSION["permessi"])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="upload.php">Carica Documento</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-text text-light me-3">
                    Benvenuto, <?php echo htmlspecialchars($_SESSION["nome"] . " " . $_SESSION["cognome"]); ?>
                    <span class="badge bg-light text-primary ms-2">
                        <?php echo implode(', ', $_SESSION["ruoli"]); ?>
                    </span>
                </div>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Contenuto principale -->
    <div class="container my-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h3 class="card-title">Carica Nuovo Documento</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <a href="index.php" class="alert-link">Torna alla lista dei documenti</a>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="titolo" class="form-label">Titolo *</label>
                                <input type="text" name="titolo" id="titolo" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="descrizione" class="form-label">Descrizione</label>
                                <textarea name="descrizione" id="descrizione" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="file" class="form-label">File PDF *</label>
                                <input class="form-control" type="file" id="file" name="file" accept="application/pdf" required>
                                <div class="form-text">Carica solo file PDF. Dimensione massima: 5MB.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Accesso per ruoli</label>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Seleziona i ruoli che possono accedere a questo documento. Se non selezioni nessun ruolo, il documento sarà visibile a tutti.
                                </div>
                                <div class="border rounded p-3">
                                    <?php foreach ($ruoli as $ruolo): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="ruoli_accesso[]" value="<?php echo $ruolo['id']; ?>" id="ruolo_<?php echo $ruolo['id']; ?>">
                                            <label class="form-check-label" for="ruolo_<?php echo $ruolo['id']; ?>">
                                                <?php echo htmlspecialchars($ruolo['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Indietro
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Carica Documento
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>