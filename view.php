<?php
// File: view.php
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
$sql = "SELECT d.*, u.nome, u.cognome 
        FROM documenti d
        JOIN utenti u ON d.utente_id = u.id
        WHERE d.id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $doc_id;

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $documento = $result->fetch_assoc();
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

// Funzione per formattare la dimensione del file
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza: <?php echo htmlspecialchars($documento['titolo']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .pdf-container {
            height: 800px;
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
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
                            <a class="nav-link" href="upload.php">Carica Documento</a>
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

    <div class="container my-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><?php echo htmlspecialchars($documento['titolo']); ?></h3>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fas fa-arrow-left"></i> Indietro
                    </a>
                    <a href="download.php?id=<?php echo $documento['id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download"></i> Scarica
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Caricato da:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($documento['nome'] . ' ' . $documento['cognome']); ?></dd>

                            <dt class="col-sm-4">Data:</dt>
                            <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($documento['data_caricamento'])); ?></dd>

                            <dt class="col-sm-4">Dimensione:</dt>
                            <dd class="col-sm-8"><?php echo formatBytes($documento['dimensione']); ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($documento['descrizione'])): ?>
                            <h5>Descrizione:</h5>
                            <p><?php echo nl2br(htmlspecialchars($documento['descrizione'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visualizzatore PDF -->
                <div class="pdf-container">
                    <object data="<?php echo htmlspecialchars($documento['percorso_file']); ?>" type="application/pdf" width="100%" height="100%">
                        <p>
                            Il tuo browser non supporta la visualizzazione dei PDF.
                            <a href="download.php?id=<?php echo $documento['id']; ?>">Scarica il PDF</a> invece.
                        </p>
                    </object>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>