<?php
require_once "../config/db.php";

/* ======================================================
   ➕ AJOUT CLIENT
====================================================== */
if(isset($_POST['add_client'])){

    $nom = $_POST['nom'];

    // optional fields
    $prenom = !empty($_POST['prenom']) ? $_POST['prenom'] : null;
    $telephone = !empty($_POST['telephone']) ? $_POST['telephone'] : null;

    // nom only obligatoire
    if(!empty($nom)){

        $bd->prepare("
            INSERT INTO client (nom, prenom, telephone)
            VALUES (?, ?, ?)
        ")->execute([$nom, $prenom, $telephone]);

        echo "<p style='color:green'>✅ Client ajouté</p>";

    } else {
        echo "<p style='color:red'>❌ Le nom est obligatoire</p>";
    }
}

/* ======================================================
   🗑️ DELETE CLIENT (AMÉLIORÉ)
====================================================== */
if(isset($_POST['delete'])){

    $id = $_POST['delete_id'];

    // 🔒 PROTECTION: check session
    $check = $bd->prepare("
        SELECT COUNT(*) FROM session WHERE id_client=?
    ");
    $check->execute([$id]);
    $count = $check->fetchColumn();

    if($count > 0){
        die("<p style='color:red'>❌ Client déjà utilisé dans une session</p>");
    }

    $bd->prepare("DELETE FROM client WHERE id_client=?")
       ->execute([$id]);

    echo "<p style='color:green'>🗑️ Client supprimé</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Clients - Cybercafé</title>
</head>
<body>

<h1>👤 Gestion Clients</h1>

<hr>

<!-- ======================================================
     ➕ AJOUT CLIENT
====================================================== -->

<h2>➕ Ajouter Client</h2>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" >
    <input type="text" name="telephone" placeholder="Téléphone">
    <button type="submit" name="add_client">Ajouter</button>
</form>

<hr>

<!-- ======================================================
     🔍 RECHERCHE CLIENT
====================================================== -->

<h2>🔍 Recherche Client</h2>

<form method="GET" style="display:inline;">
    <input type="text" name="search" placeholder="Nom, prénom ou téléphone">
    <button type="submit">Rechercher</button>
</form>

<!--  BOUTON TOUS -->
<form method="GET" style="display:inline;">
    <button type="submit">📋 Tous</button>
</form>

<hr>

<?php

/* ======================================================
   📊 LISTE CLIENT + STATS (AMÉLIORÉ)
====================================================== */

$where = "1=1";
$params = [];

if(!empty($_GET['search'])){
    $where .= " AND (nom LIKE ? OR prenom LIKE ? OR telephone LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

/* 🔥 QUERY PRO: sessions count */
$sql = "
SELECT c.*, 
       COUNT(s.id_session) AS nb_sessions
FROM client c
LEFT JOIN session s ON s.id_client = c.id_client
WHERE $where
GROUP BY c.id_client
ORDER BY c.id_client DESC
";

$req = $bd->prepare($sql);
$req->execute($params);
$clients = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ======================================================
     📋 LISTE CLIENTS
====================================================== -->

<h2>📋 Liste Clients</h2>

<table border="1" cellpadding="10">

<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Prénom</th>
    <th>Téléphone</th>

    <!-- 🔥 AJOUT -->
    <th>Sessions</th>

    <th>Action</th>
</tr>

<?php foreach($clients as $c){ ?>

<tr>

    <td><?= $c['id_client'] ?></td>
    <td><?= $c['nom'] ?></td>
    <td><?= $c['prenom'] ?? '-' ?></td>
    <td><?= $c['telephone'] ?? '-' ?></td>

    <!-- 🔥 NOUVEAU -->
    <td>
        <?= $c['nb_sessions'] ?>
    </td>

    <td>
        <form method="POST" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="delete_id" value="<?= $c['id_client'] ?>">
            <button type="submit" name="delete">🗑️</button>
        </form>
    </td>

</tr>

<?php } ?>

</table>

</body>
</html>