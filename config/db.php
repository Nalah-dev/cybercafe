<?php

try {

    $bd = new PDO(
        'mysql:host=localhost;dbname=gestioncyber;charset=utf8',
        'root',
        ''
    );

    $bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


} catch(PDOException $e) {

    die("Erreur : " . $e->getMessage());

}

?>