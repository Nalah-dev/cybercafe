<?php
session_start();
require_once "../config/db.php";

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM employe WHERE username = ?";
$req = $bd->prepare($sql);
$req->execute([$username]);

$user = $req->fetch(PDO::FETCH_ASSOC);

if (!$user) {

    echo "Utilisateur introuvable";

} else if ($user['mdp'] != $password) {

    echo "Mot de passe incorrect";

} else {

    $_SESSION['id_employe'] = $user['id_employe'];
    $_SESSION['username'] = $user['username'];

    echo "Connexion réussie  ";
}
?>