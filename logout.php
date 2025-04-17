<?php
// File: logout.php
session_start();

// Elimina tutte le variabili di sessione
$_SESSION = array();

// Distruggi la sessione
session_destroy();

// Reindirizza alla pagina di login
header("location: login.php");
exit;
