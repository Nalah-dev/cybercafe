<?php
require_once "../config/db.php";
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

<?php
if(isset($_POST['add_client'])){

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];

    if(!empty($nom) && !empty($prenom)){

        $bd->prepare("
            INSERT INTO client (nom, prenom, telephone)
            VALUES (?, ?, ?)
        ")->execute([$nom,$prenom,$telephone]);

        echo "✅ Client ajouté";
    } else {
        echo "❌ Champ vide";
    }
}
?>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" required>
    <input type="text" name="telephone" placeholder="Téléphone">
    <button type="submit" name="add_client">Ajouter</button>
</form>

<hr>

<!-- ======================================================
     🔍 RECHERCHE CLIENT
====================================================== -->
<h2>🔍 Recherche Client</h2>

<form method="GET">
    <input type="text" name="search" placeholder="Nom ou prénom">
    <button type="submit">Rechercher</button>
</form>

<hr>

<?php
$where = "1=1";
$params = [];

if(!empty($_GET['search'])){
    $where .= " AND (nom LIKE ? OR prenom LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

$sql = "SELECT * FROM client WHERE $where ORDER BY id_client DESC";

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
    <th>Action</th>
</tr>

<?php foreach($clients as $c){ ?>

<tr>

    <td><?= $c['id_client'] ?></td>
    <td><?= $c['nom'] ?></td>
    <td><?= $c['prenom'] ?></td>
    <td><?= $c['telephone'] ?></td>

    <td>
        <!-- delete simple -->
        <form method="POST" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="delete_id" value="<?= $c['id_client'] ?>">
            <button type="submit" name="delete">🗑️</button>
        </form>
    </td>

</tr>

<?php } ?>

</table>

<hr>

<!-- ======================================================
     🗑️ DELETE CLIENT
====================================================== -->
<?php
if(isset($_POST['delete'])){

    $id = $_POST['delete_id'];

    $bd->prepare("DELETE FROM client WHERE id_client=?")
       ->execute([$id]);

    echo "🗑️ Client supprimé";
}
?>

</body>
</html>