<?php
// File: index.php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Recupero documenti dal database considerando i ruoli dell'utente
$sql = "SELECT DISTINCT d.id, d.titolo, d.descrizione, d.percorso_file, d.tipo_file, d.dimensione, 
               d.data_caricamento, u.nome, u.cognome
        FROM documenti d
        JOIN utenti u ON d.utente_id = u.id
        LEFT JOIN documenti_ruoli dr ON d.id = dr.documento_id
        WHERE dr.ruolo_id IS NULL OR dr.ruolo_id IN (
            SELECT ur.ruolo_id 
            FROM utenti_ruoli ur 
            WHERE ur.utente_id = ?
        )
        ORDER BY d.data_caricamento DESC";

$documenti = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_user_id);
    $param_user_id = $_SESSION["id"];

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $documenti[] = $row;
        }
    }
    $stmt->close();
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

// Gestione messaggi di sessione
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Documenti PDF</title>
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
                        <a class="nav-link active" href="index.php">Home</a>
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

    <!-- Contenuto principale -->
    <div class="container my-4">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-white">
                <h3 class="card-title">Documenti Disponibili</h3>
            </div>
            <div class="card-body">
                <?php if (empty($documenti)): ?>
                    <div class="alert alert-info">
                        Nessun documento disponibile al momento.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Titolo</th>
                                    <th>Descrizione</th>
                                    <th>Caricato da</th>
                                    <th>Data</th>
                                    <th>Dimensione</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documenti as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['titolo']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['descrizione']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['nome'] . ' ' . $doc['cognome']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($doc['data_caricamento'])); ?></td>
                                        <td><?php echo formatBytes($doc['dimensione']); ?></td>
                                        <td>
                                            <a href="view.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-info" title="Visualizza">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-success" title="Scarica">
                                                <i class="fas fa-download"></i>
                                            </a>

                                            <?php if (in_array('delete', $_SESSION["permessi"])): ?>
                                                <a href="delete.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Sei sicuro di voler eliminare questo documento?');" title="Elimina">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>