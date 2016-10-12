<?php

    include_once('info.php'); // Contient les informations de connexion à la bdd
    
    
    
    // CRÉATION ET CONNEXION À LA BASE
    
    // Connection serveur SQL
    if (!$connect = new mysqli($hostname, $username, $password)) {
        echo "Erreur connection : " . $connect -> error;
        exit();
    }
    $connect -> query("DROP DATABASE $database");
    // Création BDD
    if ($connect -> query("CREATE DATABASE $database"))
        echo "Base de donnée crée\n";
    // Connection BDD
    if (!$connect = new mysqli($hostname, $username, $password, $database)) {
        echo "Échec de la connexion à la BDD :  " . $connect -> error;
        exit();
    }
    // Vérif caractères utf-8
    if (!$connect -> set_charset("utf8")) {
        echo "Erreur lors du chargement du jeu de caractères utf8 :  " . $connect -> error;
        exit();
    }
    
    
    
    // CRÉATION DES TABLES
    
    /* Table user : 
     * Privilèges : 
     * 0: admin (gestion utilisateurs + commentaires + niveau 2)
     * 1: artistes (ajout/modif/suppression oeuvres + niveau 2)
     * 2: membres (note + commentaire oeuvres (peut modifier/supprimer ses propres commentaires) + visualisation historique commentaires + suivre un artiste)
     * Droit membre par défaut, l'admin peut attribuer des droits d'artiste à un membre ou rétrograder un artiste.
     * Chiffrement mot de passe en sha-256 */
    if ($connect -> query("CREATE TABLE user (login VARCHAR(20) PRIMARY KEY,
                                            pass VARCHAR(64) NOT NULL,
                                            privilege INT NOT NULL DEFAULT 2,
                                            nom VARCHAR(40),
                                            prenom VARCHAR(40),
                                            informations TEXT,
                                            mail VARCHAR(40) NOT NULL,
                                            date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP)")) {
        echo "Table user crée\n";
        if ($connect -> query("INSERT INTO user (login, pass, privilege, mail) VALUES ('admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 0, 'tomswank@gmail.com')"))
            echo "Compte admin crée\n"; // login: admin   pass: admin
    }
    
    /* Création table session (id généré aléatoirement (hash('sha256', openssl_random_pseudo_bytes(32))).
     * Lorsqu'un user se connecte, création d'une session. L'id généré aléatoirement est stocké dans $_SESSION['sid'].
     * A chaque changement de page: 1. Vérification couple id/user et date.  2. Régénération de l'id et de la date.
     * Si un utilisateur tente d'accéder à une page après date+3600 secondes (délai d'expiration de la session), il est déconnecté et
     * la session est supprimée de la table + cookie effacé + session effacée côté PHP. */
    if ($connect -> query("CREATE TABLE sessions    (id VARCHAR(64) PRIMARY KEY,
                                                    user VARCHAR(20) NOT NULL UNIQUE,
                                                    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                    CONSTRAINT session_fk_user FOREIGN KEY (user) REFERENCES user(login) ON DELETE CASCADE)"))
        echo "Table session crée \n";

    // Création table catégorie: 2 niveaux de catégories (parent>enfant)
    if ($connect -> query("CREATE TABLE categories  (id INT AUTO_INCREMENT PRIMARY KEY,
                                                    nom VARCHAR(40) NOT NULL,
                                                    parent INT,
                                                    CONSTRAINT categorie_fk_parent FOREIGN KEY (parent) REFERENCES categories(id) ON DELETE CASCADE)"))
        echo "Table catégories crée\n";
        
    // Création table oeuvres
    if ($connect -> query("CREATE TABLE oeuvres (id INT AUTO_INCREMENT PRIMARY KEY, 
                                                titre VARCHAR(40), 
                                                categorie INT, 
                                                date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                artiste VARCHAR(20) NOT NULL,
                                                description TEXT,
                                                CONSTRAINT oeuvre_fk_categorie FOREIGN KEY (categorie) REFERENCES categories(id) ON DELETE SET NULL,
                                                CONSTRAINT oeuvre_fk_artiste FOREIGN KEY (artiste) REFERENCES user(login) ON DELETE CASCADE)"))
        echo "Table oeuvres crée\n";
        
    // Création table images (jusqu'à 3 images par oeuvre)
    if ($connect -> query("CREATE TABLE images  (id INT AUTO_INCREMENT PRIMARY KEY,
                                                oeuvre INT NOT NULL,
                                                fichier VARCHAR(20) NOT NULL,
                                                miniature VARCHAR(20) NOT NULL,
                                                repertoire VARCHAR(20) NOT NULL,
                                                position INT NOT NULL,
                                                CONSTRAINT image_fk_oeuvre FOREIGN KEY (oeuvre) REFERENCES oeuvres(id) ON DELETE CASCADE)"))
        echo "Table images crée\n";
        
    // Table commentaires
    if ($connect -> query("CREATE TABLE commentaires (id INT AUTO_INCREMENT PRIMARY KEY,
                                                    auteur_id VARCHAR(20) NOT NULL,
                                                    oeuvre_id INT NOT NULL,
                                                    contenu TEXT NOT NULL,
                                                    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                    CONSTRAINT comment_fk_auteur FOREIGN KEY (auteur_id) REFERENCES user(login) ON DELETE CASCADE,
                                                    CONSTRAINT comment_fk_oeuvre FOREIGN KEY (oeuvre_id) REFERENCES oeuvres(id) ON DELETE CASCADE)"))
        echo "Table commentaires crée\n";
    else
        echo $connect->error."\n";
        
    // Création table notes
    if ($connect -> query("CREATE TABLE notes   (auteur_id VARCHAR(20) NOT NULL,
                                                oeuvre_id INT NOT NULL,
                                                valeur INT NOT NULL,
                                                CONSTRAINT notes_fk_auteur FOREIGN KEY (auteur_id) REFERENCES user(login) ON DELETE CASCADE,
                                                CONSTRAINT notes_fk_oeuvre FOREIGN KEY (oeuvre_id) REFERENCES oeuvres(id) ON DELETE CASCADE,
                                                CONSTRAINT notes_pk PRIMARY KEY (auteur_id, oeuvre_id))"))
        echo "Table notes crée\n";
        
    // Création table suivi d'artistes (Envoi de mail lorsque l'artiste ajoute une oeuvre)
    if ($connect -> query("CREATE TABLE suivi   (id INT AUTO_INCREMENT PRIMARY KEY,
												artiste_id VARCHAR(20) NOT NULL,
                                                membre_id VARCHAR(20) NOT NULL,
                                                CONSTRAINT suivi_uq UNIQUE (artiste_id, membre_id),
                                                CONSTRAINT suivi_fk_artiste FOREIGN KEY (artiste_id) REFERENCES user(login) ON DELETE CASCADE,
                                                CONSTRAINT suivi_fk_membre FOREIGN KEY (membre_id) REFERENCES user(login) ON DELETE CASCADE)"))
        echo "Table suivi d'artistes crée\n";    
    else
        echo $connect->error."\n";
        
        
    
    // CRÉATION DES UTLISATEURS ET ASSIGNATION DES DROITS POUR CHACUN
    
    // Utilisateur admin
    if ($connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.* TO $DBLogin[0]@$hostname IDENTIFIED BY '$DBPass[0]'")) {
        echo "Utilisateur admin crée\n";
    }
    // Utilisateur artiste
    if ($connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.user TO $DBLogin[1]@$hostname IDENTIFIED BY '$DBPass[1]'")) {
        echo "Utilisateur artiste crée\n";
        $connect->query("GRANT SELECT ON $database.categories TO $DBLogin[1]@$hostname");
        $connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.oeuvres TO $DBLogin[1]@$hostname");
        $connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.images TO $DBLogin[1]@$hostname");
        $connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.commentaires TO $DBLogin[1]@$hostname");
        $connect->query("GRANT SELECT, INSERT ON $database.notes TO $DBLogin[1]@$hostname");
        $connect->query("GRANT SELECT, INSERT, DELETE ON $database.suivi TO $DBLogin[1]@$hostname");
    }
    // Utilisateur membre
    if ($connect->query("GRANT SELECT, INSERT, UPDATE ON $database.user TO $DBLogin[2]@$hostname IDENTIFIED BY '$DBPass[2]'")) {
        echo "Utilisateur membre crée\n";
        $connect->query("GRANT SELECT ON $database.categories TO $DBLogin[2]@$hostname");
        $connect->query("GRANT SELECT ON $database.oeuvres TO $DBLogin[2]@$hostname");
        $connect->query("GRANT SELECT ON $database.images TO $DBLogin[2]@$hostname");
        $connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.commentaires TO $DBLogin[2]@$hostname");
        $connect->query("GRANT SELECT, INSERT ON $database.notes TO $DBLogin[2]@$hostname");
        $connect->query("GRANT SELECT, INSERT, DELETE ON $database.suivi TO $DBLogin[2]@$hostname");
    }
    // Utilisateur invité
    if ($connect->query("GRANT SELECT ON $database.user TO $DBLogin[3]@$hostname IDENTIFIED BY '$DBPass[3]'")) {
        echo "Utilisateur invité crée\n";
        $connect->query("GRANT SELECT ON $database.categories TO $DBLogin[3]@$hostname IDENTIFIED BY '$DBPass[3]'");
        $connect->query("GRANT SELECT ON $database.oeuvres TO $DBLogin[3]@$hostname");
        $connect->query("GRANT SELECT ON $database.images TO $DBLogin[3]@$hostname");
        $connect->query("GRANT SELECT ON $database.commentaires TO $DBLogin[3]@$hostname");
        $connect->query("GRANT SELECT ON $database.notes TO $DBLogin[3]@$hostname");
    }
    // Utilisateur check_user (gestion users et sessions)
    if ($connect->query("GRANT SELECT, INSERT ON $database.user TO $DBLogin[4]@$hostname IDENTIFIED BY '$DBPass[4]'")) {
        $connect->query("GRANT SELECT, INSERT, UPDATE, DELETE ON $database.sessions TO $DBLogin[4]@$hostname");
        echo "Utilisateur check_user crée\n";
    }
    
    
    
    // CRÉATION DES ENREGISTREMENTS PAR DÉFAULT DE LA BASE DE DONNÉE
    
    // Utilisateurs
    if ($connect -> query("INSERT INTO user(login,pass,privilege,nom,prenom,informations,mail) VALUES  
                                                ('JeanDub', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 2, 'Dubois', 'Jean', NULL, 'tomswank@gmail.com'),
                                                ('DupontP', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 2, 'Dupont', 'Paul', NULL, 'paul@dupont.com'),
                                                ('PP_officiel', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 1, 'Picasso', 'Pablo', 'Peintre à mes heures perdues...', 'ppicasso@gmail.com'),
                                                ('YAB_photo', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 1, 'Arthus-Bertrand', 'Yann', 'Photos de voyages', 'yab12@yahoo.fr'),
                                                ('Bilal24', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 1, 'Bilal', 'Enki', 'Passionné de bande-dessinée', 'eb.officiel@hotmail.fr')"))
        echo "utilisateurs ajoutés\n";
    else
        echo $connect -> error."\n";
    
    // Catégories
    if ($connect -> query("INSERT INTO categories VALUES  
                                                (1, 'Peinture', NULL),
                                                (2, 'Dessin', NULL),
                                                (3, 'Illustration', NULL),
                                                (4, 'Sculpture', NULL),
                                                (5, 'Gravure', NULL),
                                                (6, 'Photographie', NULL),
                                                (7, 'Mobiliers', NULL),
                                                (8, 'Peinture à l\'huile', 1),
                                                (9, 'Peinture acrylique', 1),
                                                (10, 'Bande-dessinée', 3),
                                                (11, 'Illustration indépendante', 3),
                                                (12, 'Sculpture sur bois', 4),
                                                (13, 'Sculpture sur pierre', 4),
                                                (14, 'Gravure sur bois', 5),
                                                (15, 'Gravure sur pierre', 5),
                                                (16, 'Gravure sur métal', 5),
                                                (17, 'Photographie numérique', 6),
                                                (18, 'Photographie argentique', 6),
                                                (19, 'Table', 7),
                                                (20, 'Chaise', 7),
                                                (21, 'Armoire', 7)"))
        echo "catégories ajoutées\n";
    else
        echo $connect -> error."\n";

    // Oeuvres
    if ($connect -> query("INSERT INTO oeuvres  (id, titre, categorie, artiste, description) VALUES  
                                                (1, 'Guernica', 8, 'PP_officiel', 'Une représentation de la bataille de Guernica'),
                                                (2, 'Soudan', 17, 'YAB_photo', 'Des photos prise lors d\'un voyage au Soudan'),
                                                (3, 'Femmes nues', 8, 'PP_officiel', 'Je tente de nouvelles formes... J\'espère que cela vous plaira'),
                                                (4, 'Saltimbanques', 8, 'PP_officiel', 'Jeunesse paresseuse'),
                                                (5, 'Des planches en vrac', 10, 'Bilal24', 'Des planches de plusieurs projets'),
                                                (6, 'Illustration', 11, 'Bilal24', 'Essai d\'illustration')"))
        echo "oeuvres ajoutées\n";
    else
        echo $connect -> error."\n";
    
    // Images
    $connect -> query("DELETE FROM images");
    if ($connect -> query("INSERT INTO images   (oeuvre, fichier, miniature, repertoire, position) VALUES
                                                (1, 'img1.jpeg', 'min1.jpeg', 'img1', 0),
                                                (2, 'img2.jpeg', 'min2.jpeg', 'img1', 0),
                                                (2, 'img3.jpeg', 'min3.jpeg', 'img1', 1),
                                                (2, 'img4.jpeg', 'min4.jpeg', 'img1', 2),
                                                (3, 'img5.jpeg', 'min5.jpeg', 'img1', 0),
                                                (4, 'img6.jpeg', 'min6.jpeg', 'img1', 0),
                                                (5, 'img7.jpeg', 'min7.jpeg', 'img1', 0),
                                                (5, 'img8.jpeg', 'min8.jpeg', 'img1', 1),
                                                (5, 'img9.jpeg', 'min9.jpeg', 'img1', 2),
                                                (6, 'img10.jpeg', 'min10.jpeg', 'img1', 0)"))
        echo "images ajoutées\n";
    else
        echo $connect -> error."\n";

    // Commentaires
    if ($connect -> query("INSERT INTO commentaires   (auteur_id, oeuvre_id, contenu) VALUES
                                                ('DupontP', 1, 'On dirait un dessin de mon fils (il a 10 ans). Ne le prenez pas mal hein?'),
                                                ('PP_officiel', 1, '...'),
                                                ('DupontP', 1, 'Combien de temps ça vous a pris?'),
                                                ('PP_officiel', 1, '6 mois à peu près'),
                                                ('DupontP', 1, 'Mon fils a fait ça en 1 heure !'),
                                                ('PP_officiel', 1, '...'),
                                                ('JeanDub', 1, 'Non mais c\'est n\'importe quoi! Ne l\'écoutez pas PP, votre oeuvre est impressionante!'),
                                                ('PP_officiel', 1, 'Merci JeanDub, vous me rassurez... Etant donné le travail que ça représente'),
                                                ('JeanDub', 2, 'Ça fait voyager !'),
                                                ('PP_officiel', 2, 'Très joli paysage, merci YAB !'),
                                                ('YAB_photo', 2, 'Content que ça vous plaise !'),
                                                ('JeanDub', 3, 'Très joli'),
                                                ('Bilal24', 3, 'Superbe, j\'aimerai m\'en inspirer pour une planche si cela ne vous dérange pas'),
                                                ('PP_officiel', 3, 'Merci, et sans problème Bilal24'),
                                                ('DupontP', 3, 'Pas trop mal celle-ci'),
                                                ('JeanDub', 4, 'Sympa, vous avez changé un peu de style visiblement?'),
                                                ('PP_officiel', 4, 'Oui je m\'entraine à de nouvelles choses'),
                                                ('DupontP', 4, 'Je préférai votre peinture précédente'),
                                                ('PP_officiel', 5, 'Une date de parution prévue?'),
                                                ('Bilal24', 5, 'Rien pour le moment, je cherche toujours un éditeur...'),
                                                ('PP_officiel', 5, 'Dommage, ça ferai un cadeau parfait pour mon neveu. Tenez-moi au courant SVP'),
                                                ('DupontP', 6, 'C\'est glauque !!! Vous avez des soucis en ce moment ?'),
                                                ('Bilal24', 6, '...'),
                                                ('PP_officiel', 6, 'Ne faites pas attention Bilal24, DupontP est un troll.'),
                                                ('DupontP', 6, 'On n\'a même plus le droit d\'exprimer une opinion? Et la liberté d\'expression alors?'),
                                                ('PP_officiel', 6, 'Mais que fait le modérateur?!')"))
        echo "commentaires ajoutés\n";
    else
        echo $connect -> error."\n";

    // Notes
    if ($connect -> query("INSERT INTO notes VALUES
                                                ('DupontP', 1, 1),
                                                ('JeanDub', 1, 9),
                                                ('Bilal24', 1, 8),
                                                ('JeanDub', 2, 7),
                                                ('PP_officiel', 2, 8),
                                                ('DupontP', 2, 3),
                                                ('JeanDub', 3, 8),
                                                ('Bilal24', 3, 10),
                                                ('DupontP', 3, 5),
                                                ('JeanDub', 4, 6),
                                                ('DupontP', 4, 3),
                                                ('PP_officiel', 5, 9),
                                                ('DupontP', 6, 2),
                                                ('PP_officiel', 6, 9)"))
        echo "notes ajoutées\n";
    else
        echo $connect -> error."\n";
    
    // Suivi
    if ($connect -> query("INSERT INTO suivi VALUES
                                                (1, 'PP_officiel', 'JeanDub'),
                                                (2, 'YAB_photo', 'JeanDub')"))
        echo "suivis ajoutés\n";
    else
        echo $connect -> error."\n";

    
    
    $connect->close();
    
    
    
