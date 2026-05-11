<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cybercafé - Session PRO</title>
</head>
<body>

<h1>💻 Gestion Cybercafé (Ticket System)</h1>

<hr>

<!-- ======================================================
     ➕ AJOUT CLIENT
====================================================== -->
<h2>➕ Ajouter Client</h2>

<?php
if(isset($_POST['add_client'])){

    $bd->prepare("
        INSERT INTO client (nom, prenom, telephone)
        VALUES (?, ?, ?)
    ")->execute([
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['telephone']
    ]);

    echo "✅ Client ajouté<br>";
}
?>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" required>
    <input type="text" name="telephone" placeholder="Téléphone" required>
    <button type="submit" name="add_client">Ajouter</button>
</form>

<hr>

<!-- ======================================================
     🟢 START SESSION (HEURE + MINUTE)
====================================================== -->
<h2>🟢 Démarrer Session</h2>

<?php
if(isset($_POST['start'])){

    $id_client = $_POST['id_client'];
    $id_poste = $_POST['id_poste'];

    $heures = $_POST['heures'];
    $minutes = $_POST['minutes'];

    // conversion en minutes
    $duree_prevue = ($heures * 60) + $minutes;

    if($duree_prevue <= 0){
        die("❌ Durée invalide");
    }

    // check poste libre
    $check = $bd->prepare("SELECT etat FROM poste WHERE id_poste=?");
    $check->execute([$id_poste]);
    $etat = $check->fetchColumn();

    if($etat != 'libre'){
        die("❌ Poste occupé");
    }

    // heure fin automatique
    $heure_fin = date('Y-m-d H:i:s', strtotime("+$duree_prevue minutes"));

    // insert session
    $bd->prepare("
        INSERT INTO session
        (heure_debut, heure_fin, statut, id_client, id_poste, duree_prevue)
        VALUES (NOW(), ?, 'en_cours', ?, ?, ?)
    ")->execute([
        $heure_fin,
        $id_client,
        $id_poste,
        $duree_prevue
    ]);

    // update poste
    $bd->prepare("
        UPDATE poste SET etat='occupé'
        WHERE id_poste=?
    ")->execute([$id_poste]);

    echo "✅ Session démarrée ($heures h $minutes min)";
}
?>

<form method="POST">

    <!-- CLIENT -->
    <select name="id_client" required>
        <option value="">Client</option>
        <?php
        $clients = $bd->query("SELECT * FROM client");
        foreach($clients as $c){
            echo "<option value='{$c['id_client']}'>
                    {$c['nom']} {$c['prenom']}
                  </option>";
        }
        ?>
    </select>

    <!-- POSTE -->
    <select name="id_poste" required>
        <option value="">Poste libre</option>
        <?php
        $postes = $bd->query("SELECT * FROM poste WHERE etat='libre'");
        foreach($postes as $p){
            echo "<option value='{$p['id_poste']}'>
                    Poste {$p['num_poste']}
                  </option>";
        }
        ?>
    </select>

    <!-- HEURE -->
    <input type="number" name="heures" placeholder="Heures" min="0" required>

    <!-- MINUTE -->
    <input type="number" name="minutes" placeholder="Minutes" min="0" max="59" required>

    <button type="submit" name="start">Démarrer</button>
</form>

<hr>

<!-- ======================================================
     🔴 AUTO STOP
====================================================== -->
<?php
$bd->query("
    UPDATE session
    SET statut='terminée',
        heure_fin=NOW()
    WHERE statut='en_cours'
    AND heure_fin <= NOW()
");
?>

<hr>

<!-- ======================================================
     🔍 RECHERCHE
====================================================== -->
<h2>🔍 Recherche / Filtre</h2>

<form method="GET">
    <input type="text" name="search" placeholder="Client ou ID">

    <select name="filtre">
        <option value="">Tous</option>
        <option value="en_cours">En cours</option>
        <option value="terminée">Terminée</option>
    </select>

    <button type="submit">OK</button>
</form>

<hr>

<?php
$where = "1=1";
$params = [];

if(!empty($_GET['search'])){
    $where .= " AND (c.nom LIKE ? OR s.id_session LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

if(!empty($_GET['filtre'])){
    $where .= " AND s.statut = ?";
    $params[] = $_GET['filtre'];
}

$sql = "
SELECT s.*,
       c.nom AS client_nom,
       c.prenom AS client_prenom,
       p.num_poste
FROM session s
LEFT JOIN client c ON s.id_client = c.id_client
LEFT JOIN poste p ON s.id_poste = p.id_poste
WHERE $where
ORDER BY s.id_session DESC
";

$req = $bd->prepare($sql);
$req->execute($params);
$resultat = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ======================================================
     📊 STATS
====================================================== -->
<?php
$active = $bd->query("SELECT COUNT(*) FROM session WHERE statut='en_cours'")->fetchColumn();
$terminee = $bd->query("SELECT COUNT(*) FROM session WHERE statut='terminée'")->fetchColumn();
$revenu = $bd->query("SELECT SUM(prix_total) FROM session")->fetchColumn();
?>

<h2>📊 Statistiques</h2>

<p>🟢 Actives : <?= $active ?></p>
<p>🔴 Terminées : <?= $terminee ?></p>
<p>💰 Revenu : <?= $revenu ?> Ar</p>

<hr>

<!-- ======================================================
     📋 TABLE SESSION
====================================================== -->
<h2>📋 Sessions</h2>

<table border="1" cellpadding="10">

<tr>
    <th>ID</th>
    <th>Client</th>
    <th>Poste</th>
    <th>Début</th>
    <th>Fin</th>
    <th>Durée</th>
    <th>Prix</th>
    <th>Statut</th>
</tr>

<?php foreach($resultat as $s){

    $duree = $s['duree_prevue'];
    $prix = ($duree / 60) * 2000;

?>

<tr>

    <td><?= $s['id_session'] ?></td>
    <td><?= $s['client_nom'].' '.$s['client_prenom'] ?></td>
    <td><?= $s['num_poste'] ?></td>
    <td><?= $s['heure_debut'] ?></td>
    <td><?= $s['heure_fin'] ?></td>

    <td>
        <?= floor($duree/60) ?>h <?= $duree%60 ?>min
    </td>

    <td><?= $prix ?> Ar</td>

    <td>
        <?= ($s['statut']=='en_cours') ? "🟢 En cours" : "🔴 Terminée" ?>
    </td>

</tr>

<?php } ?>

</table>

<hr>


</table>

</body>
</html>