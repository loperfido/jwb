<?php
// File: login.php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validazione input
    if (empty($username) || empty($password)) {
        $error = "Inserisci username e password.";
    } else {
        // Preparazione query
        $sql = "SELECT u.id, u.username, u.password, u.nome, u.cognome, GROUP_CONCAT(r.nome) as ruoli 
                FROM utenti u 
                LEFT JOIN utenti_ruoli ur ON u.id = ur.utente_id 
                LEFT JOIN ruoli r ON ur.ruolo_id = r.id 
                WHERE u.username = ?
                GROUP BY u.id";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $nome, $cognome, $ruoli);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password corretta, inizio sessione
                            session_start();

                            // Archiviazione dati di sessione
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["nome"] = $nome;
                            $_SESSION["cognome"] = $cognome;
                            $_SESSION["ruoli"] = explode(',', $ruoli);

                            // Recupero dei permessi specifici
                            $permessi = [];
                            $ruoli_array = explode(',', $ruoli);

                            $sql_permessi = "SELECT r.nome as ruolo, pr.permesso
                                            FROM permessi_ruoli pr
                                            JOIN ruoli r ON pr.ruolo_id = r.id
                                            WHERE r.nome IN ('" . implode("','", $ruoli_array) . "')";

                            $result_permessi = $conn->query($sql_permessi);
                            while ($row = $result_permessi->fetch_assoc()) {
                                $permessi[] = $row['permesso'];
                            }

                            $_SESSION["permessi"] = array_unique($permessi);

                            // Reindirizzamento alla pagina principale
                            header("location: index.php");
                        } else {
                            $error = "Password non valida.";
                        }
                    }
                } else {
                    $error = "Nessun account trovato con questo username.";
                }
            } else {
                $error = "Errore nell'esecuzione della query.";
            }

            $stmt->close();
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Gestione PDF</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Accesso al Sistema</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Accedi</button>
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