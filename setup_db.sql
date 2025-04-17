-- Creazione delle tabelle per il sistema
CREATE TABLE ruoli (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE
);

-- Inserimento dei ruoli predefiniti
INSERT INTO ruoli (nome) VALUES 
('proclamatore'), 
('battezzato'), 
('servitore'), 
('anziano'), 
('acustica'), 
('microfonista'), 
('caricatore');

CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE utenti_ruoli (
    utente_id INT,
    ruolo_id INT,
    PRIMARY KEY (utente_id, ruolo_id),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (ruolo_id) REFERENCES ruoli(id)
);

CREATE TABLE documenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(255) NOT NULL,
    descrizione TEXT,
    percorso_file VARCHAR(255) NOT NULL,
    tipo_file VARCHAR(50) NOT NULL,
    dimensione INT NOT NULL,
    utente_id INT NOT NULL,
    data_caricamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id)
);

CREATE TABLE permessi_ruoli (
    ruolo_id INT,
    permesso VARCHAR(50) NOT NULL,
    PRIMARY KEY (ruolo_id, permesso),
    FOREIGN KEY (ruolo_id) REFERENCES ruoli(id)
);

-- Definizione dei permessi per ruolo
INSERT INTO permessi_ruoli (ruolo_id, permesso) VALUES
-- Il ruolo 'caricatore' può aggiungere ed eliminare documenti
(7, 'upload'),
(7, 'delete');

-- Tabella per gestire i permessi di accesso ai documenti per ruolo
CREATE TABLE documenti_ruoli (
    documento_id INT,
    ruolo_id INT,
    PRIMARY KEY (documento_id, ruolo_id),
    FOREIGN KEY (documento_id) REFERENCES documenti(id) ON DELETE CASCADE,
    FOREIGN KEY (ruolo_id) REFERENCES ruoli(id)
);