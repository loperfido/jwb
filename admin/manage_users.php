<?php
// File: admin/manage_users.php
// Gestione utenti con funzionalità di modifica dei ruoli
session_start();

// Verifica se l'utente è loggato e ha i permessi di amministratore
// Nel tuo caso, dovresti decidere chi può accedere a questa pagina
// Per esempio, potresti creare un ruolo 'admin' appositamente per questo

// Per questo esempio, assumiamo che solo tu (con uno specifico ID) possa accedere
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["id"] != 1) {
    header("location: ../index.php");
    exit;
}

require_once "../config.php";

$error = '';
$success = '';

// Crea nuovo utente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create") {
    // Validazione input
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"])) || empty(trim($_POST["nome"])) || empty(trim($_POST["cognome"]))) {
        $error = "Compila tutti i campi obbligatori.";
    } else {
        // Verifica se lo username esiste già
        $sql = "SELECT id FROM utenti WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = "Questo username è già in uso.";
                } else {
                    $username = trim($_POST["username"]);
                    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
                    $nome = trim($_POST["nome"]);
                    $cognome = trim($_POST["cognome"]);
                    $email = trim($_POST["email"]);

                    // Prepara l'inserimento
                    $sql = "INSERT INTO utenti (username, password, nome, cognome, email) VALUES (?, ?, ?, ?, ?)";

                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("sssss", $username, $password, $nome, $cognome, $email);

                        if ($stmt->execute()) {
                            $user_id = $stmt->insert_id;

                            // Assegna ruoli
                            if (isset($_POST["ruoli"]) && is_array($_POST["ruoli"])) {
                                foreach ($_POST["ruoli"] as $ruolo_id) {
                                    $sql_role = "INSERT INTO utenti_ruoli (utente_id, ruolo_id) VALUES (?, ?)";
                                    $stmt_role = $conn->prepare($sql_role);
                                    $stmt_role->bind_param("ii", $user_id, $ruolo_id);
                                    $stmt_role->execute();
                                    $stmt_role->close();
                                }
                            }

                            $success = "Utente creato con successo.";
                        } else {
                            $error = "Errore durante la creazione dell'utente.";
                        }
                    }
                }
            } else {
                $error = "Errore nell'esecuzione della query.";
            }

            $stmt->close();
        }
    }
}

// Elimina utente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
    if (isset($_POST["user_id"]) && !empty($_POST["user_id"])) {
        $user_id = intval($_POST["user_id"]);

        // Elimina utente
        $sql = "DELETE FROM utenti WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $success = "Utente eliminato con successo.";
            } else {
                $error = "Errore durante l'eliminazione dell'utente.";
            }

            $stmt->close();
        }
    }
}

// Modifica ruoli utente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_roles") {
    if (isset($_POST["user_id"]) && !empty($_POST["user_id"])) {
        $user_id = intval($_POST["user_id"]);

        // Prima elimina tutti i ruoli esistenti dell'utente
        $sql_delete_roles = "DELETE FROM utenti_ruoli WHERE utente_id = ?";
        if ($stmt = $conn->prepare($sql_delete_roles)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Poi aggiungi i nuovi ruoli selezionati
            if (isset($_POST["ruoli_utente"]) && is_array($_POST["ruoli_utente"])) {
                foreach ($_POST["ruoli_utente"] as $ruolo_id) {
                    $sql_add_role = "INSERT INTO utenti_ruoli (utente_id, ruolo_id) VALUES (?, ?)";
                    $stmt_add = $conn->prepare($sql_add_role);
                    $stmt_add->bind_param("ii", $user_id, $ruolo_id);
                    $stmt_add->execute();
                    $stmt_add->close();
                }
                $success = "Ruoli dell'utente aggiornati con successo.";
            } else {
                // Nessun ruolo selezionato - l'utente non avrà ruoli
                $success = "Tutti i ruoli sono stati rimossi dall'utente.";
            }
        } else {
            $error = "Errore durante l'aggiornamento dei ruoli.";
        }
    }
}

// Recupera tutti gli utenti con i loro ruoli
$sql = "SELECT u.id, u.username, u.nome, u.cognome, u.email, u.data_creazione, 
               GROUP_CONCAT(r.nome) as ruoli_nome,
               GROUP_CONCAT(r.id) as ruoli_id
        FROM utenti u
        LEFT JOIN utenti_ruoli ur ON u.id = ur.utente_id
        LEFT JOIN ruoli r ON ur.ruolo_id = r.id
        GROUP BY u.id
        ORDER BY u.id";

$utenti = [];
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Converti le stringhe di ID in array per facilitare i controlli sui ruoli
        $row['ruoli_id_array'] = $row['ruoli_id'] ? explode(',', $row['ruoli_id']) : [];
        $utenti[] = $row;
    }
}

// Recupera tutti i ruoli
$sql_ruoli = "SELECT * FROM ruoli ORDER BY nome";
$ruoli = [];
$result_ruoli = $conn->query($sql_ruoli);

if ($result_ruoli->num_rows > 0) {
    while ($row = $result_ruoli->fetch_assoc()) {
        $ruoli[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Sistema PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .modal-body .form-check {
            margin-bottom: 0.5rem;
        }

        .role-badge {
            font-size: 0.8rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            display: inline-block;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Sistema Gestione PDF</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_users.php">Gestione Utenti</a>
                    </li>
                </ul>
                <div class="navbar-text text-light me-3">
                    Benvenuto, <?php echo htmlspecialchars($_SESSION["nome"] . " " . $_SESSION["cognome"]); ?>
                    <span class="badge bg-light text-primary ms-2">
                        Amministratore
                    </span>
                </div>
                <a href="../logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Contenuto principale -->
    <div class="container my-4">
        <!-- Messaggi di errore/successo -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Crea nuovo utente -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title">Crea Nuovo Utente</h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="action" value="create">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="cognome" class="form-label">Cognome *</label>
                                <input type="text" name="cognome" id="cognome" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ruoli *</label>
                                <div class="border rounded p-3">
                                    <?php foreach ($ruoli as $ruolo): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="ruoli[]" value="<?php echo $ruolo['id']; ?>" id="ruolo<?php echo $ruolo['id']; ?>">
                                            <label class="form-check-label" for="ruolo<?php echo $ruolo['id']; ?>">
                                                <?php echo htmlspecialchars($ruolo['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Crea Utente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista utenti -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h3 class="card-title">Gestione Utenti</h3>
            </div>
            <div class="card-body">
                <?php if (empty($utenti)): ?>
                    <div class="alert alert-info">Nessun utente presente.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Nome Completo</th>
                                    <th>Email</th>
                                    <th>Ruoli</th>
                                    <th>Data Creazione</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti as $utente): ?>
                                    <tr>
                                        <td><?php echo $utente['id']; ?></td>
                                        <td><?php echo htmlspecialchars($utente['username']); ?></td>
                                        <td><?php echo htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']); ?></td>
                                        <td><?php echo htmlspecialchars($utente['email']); ?></td>
                                        <td>
                                            <?php
                                            if (!empty($utente['ruoli_nome'])) {
                                                $ruoli_list = explode(',', $utente['ruoli_nome']);
                                                foreach ($ruoli_list as $ruolo) {
                                                    echo '<span class="badge bg-info role-badge">' . htmlspecialchars($ruolo) . '</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Nessun ruolo</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($utente['data_creazione'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalEditRoles<?php echo $utente['id']; ?>">
                                                <i class="fas fa-user-tag"></i>
                                            </button>

                                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline"
                                                onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $utente['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal per modifica ruoli -->
                                    <div class="modal fade" id="modalEditRoles<?php echo $utente['id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $utente['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="modalLabel<?php echo $utente['id']; ?>">
                                                        Modifica Ruoli: <?php echo htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                                </div>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                    <input type="hidden" name="action" value="update_roles">
                                                    <input type="hidden" name="user_id" value="<?php echo $utente['id']; ?>">

                                                    <div class="modal-body">
                                                        <p>Seleziona i ruoli per questo utente:</p>

                                                        <?php foreach ($ruoli as $ruolo): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="ruoli_utente[]"
                                                                    value="<?php echo $ruolo['id']; ?>"
                                                                    id="role<?php echo $utente['id'] . '_' . $ruolo['id']; ?>"
                                                                    <?php echo in_array($ruolo['id'], $utente['ruoli_id_array']) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="role<?php echo $utente['id'] . '_' . $ruolo['id']; ?>">
                                                                    <?php echo htmlspecialchars($ruolo['nome']); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                        <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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