<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');

/* ======================================================
   🟢 START SESSION
====================================================== */
if(isset($_POST['start'])){

    $id_client = $_POST['id_client'];
    $id_poste = $_POST['id_poste'];

    // 🔥 LIMIT HEURE / MINUTE
    $heures = (int)$_POST['heures'];
    $minutes = (int)$_POST['minutes'];

    if($heures > 23) $heures = 23;
    if($minutes > 59) $minutes = 59;

    $duree_prevue = ($heures * 60) + $minutes;

    if($duree_prevue <= 0){
        die("❌ Durée invalide");
    }

    // poste libre check
    $check = $bd->prepare("SELECT etat FROM poste WHERE id_poste=?");
    $check->execute([$id_poste]);
    $etat = $check->fetchColumn();

    if($etat != 'libre'){
        die("❌ Poste occupé");
    }

    // session check
    $check2 = $bd->prepare("
        SELECT COUNT(*) FROM session
        WHERE id_poste=? AND statut='en_cours'
    ");
    $check2->execute([$id_poste]);

    if($check2->fetchColumn() > 0){
        die("❌ Session déjà active");
    }

    $heure_fin = date('Y-m-d H:i:s', strtotime("+$duree_prevue minutes"));
    $prix = ($duree_prevue / 60) * 2000;

    $bd->prepare("
        INSERT INTO session
        (heure_debut, heure_fin, statut, id_client, id_poste, duree_prevue, prix_total)
        VALUES (NOW(), ?, 'en_cours', ?, ?, ?, ?)
    ")->execute([
        $heure_fin,
        $id_client,
        $id_poste,
        $duree_prevue,
        $prix
    ]);

    $bd->prepare("UPDATE poste SET etat='occupé' WHERE id_poste=?")
        ->execute([$id_poste]);

    echo "✅ Session démarrée";
}

/* ======================================================
   ➕ PROLONGATION SESSION (FIXED)
====================================================== */
if(isset($_POST['prolonger'])){

    $id_session = $_POST['id_session'];
    $heures = (int)$_POST['heures'];
    $minutes = (int)$_POST['minutes'];

    if($heures > 23) $heures = 23;
    if($minutes > 59) $minutes = 59;

    $ajout = ($heures * 60) + $minutes;

    if($ajout <= 0){
        die("❌ Valeur invalide");
    }

    $s = $bd->prepare("SELECT * FROM session WHERE id_session=?");
    $s->execute([$id_session]);
    $data = $s->fetch(PDO::FETCH_ASSOC);

    if(!$data || $data['statut'] != 'en_cours'){
        die("❌ Session introuvable");
    }

    $new_fin = date('Y-m-d H:i:s', strtotime($data['heure_fin']." +$ajout minutes"));
    $new_duree = $data['duree_prevue'] + $ajout;
    $new_prix = $data['prix_total'] + (($ajout/60)*2000);

    $bd->prepare("
        UPDATE session
        SET heure_fin=?,
            duree_prevue=?,
            prix_total=?,
            prolongation = IFNULL(prolongation,0) + ?
        WHERE id_session=?
    ")->execute([
        $new_fin,
        $new_duree,
        $new_prix,
        $ajout,
        $id_session
    ]);

    echo "✅ Prolongation réussie";
}

/* ======================================================
   🔴 AUTO STOP
====================================================== */
$bd->query("
    UPDATE session
    SET statut='terminée'
    WHERE statut='en_cours'
    AND heure_fin <= NOW()
");

/* ======================================================
   🟢 AUTO LIBERATION POSTE
====================================================== */
$bd->query("
    UPDATE poste
    SET etat='libre'
    WHERE id_poste IN (
        SELECT id_poste FROM session WHERE statut='terminée'
    )
");

/* ======================================================
   📋 LISTE
====================================================== */
$resultat = $bd->query("
SELECT s.*,
       c.nom AS client_nom,
       c.prenom AS client_prenom,
       p.nom_poste
FROM session s
LEFT JOIN client c ON s.id_client=c.id_client
LEFT JOIN poste p ON s.id_poste=p.id_poste
ORDER BY s.id_session DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   📊 STATS
====================================================== */
$active = $bd->query("SELECT COUNT(*) FROM session WHERE statut='en_cours'")->fetchColumn();
$terminee = $bd->query("SELECT COUNT(*) FROM session WHERE statut='terminée'")->fetchColumn();
$revenu = $bd->query("SELECT IFNULL(SUM(prix_total),0) FROM session")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sessions Cybercafé</title>
</head>
<body>

<h1>💻 Sessions Cybercafé</h1>

<!-- START -->
<h2>🟢 Démarrer Session</h2>

<form method="POST">

<!-- CLIENT -->
<label>👤 Client :</label>
<select name="id_client" required>
    <option value="">-- Choisir un client --</option>

    <?php
    $clients = $bd->query("
        SELECT c.*
        FROM client c
        LEFT JOIN session s 
            ON c.id_client = s.id_client 
            AND s.statut = 'en_cours'
        WHERE s.id_client IS NULL
    ");

    foreach($clients as $c){
        echo "<option value='{$c['id_client']}'>
                {$c['nom']} {$c['prenom']}
              </option>";
    }
    ?>
</select>

<br><br>

<!-- POSTE -->
<label>💻 Poste :</label>
<select name="id_poste" required>
    <option value="">-- Choisir un poste --</option>

    <?php
    $postes = $bd->query("
        SELECT p.*
        FROM poste p
        LEFT JOIN session s 
            ON p.id_poste = s.id_poste 
            AND s.statut = 'en_cours'
        WHERE s.id_poste IS NULL
    ");

    foreach($postes as $p){
        echo "<option value='{$p['id_poste']}'>
                {$p['nom_poste']}
              </option>";
    }
    ?>
</select>


<input type="number" name="heures" placeholder="Heures (max 23)" min="0" max="23" >
<input type="number" name="minutes" placeholder="Minutes (max 59)" min="0" max="59">

<button name="start">Start</button>
</form>

<hr>

<!-- PROLONGATION -->
<h2>➕ Prolongation</h2>

<form method="POST">
<input type="number" name="id_session" placeholder="ID session" required>
<input type="number" name="heures" placeholder="Heures" min="0" max="23" required>
<input type="number" name="minutes" placeholder="Minutes" min="0" max="59" required>
<button name="prolonger">Prolonger</button>
</form>

<hr>

<!-- STATS -->
<h2>📊 Statistiques</h2>

<table border="1" cellpadding="10" style="border-collapse:collapse; width:300px;">

    <tr>
        <th>Indicateur</th>
        <th>Valeur</th>
    </tr>

    <tr>
        <td>🟢 Sessions Actives</td>
        <td><?= $active ?></td>
    </tr>

    <tr>
        <td>🔴 Sessions Terminées</td>
        <td><?= $terminee ?></td>
    </tr>

    <tr>
        <td>💰 Revenu Total</td>
        <td><?= $revenu ?> Ar</td>
    </tr>

</table>
<hr>

<!-- TABLE -->
<table border="1">
<tr>
<th>ID</th><th>Client</th><th>Poste</th><th>Début</th><th>Fin</th><th>Durée</th><th>Prix</th><th>Statut</th>
</tr>

<?php foreach($resultat as $s){ ?>
<tr>
<td><?= $s['id_session'] ?></td>
<td><?= $s['client_nom']." ".$s['client_prenom'] ?></td>
<td><?= $s['nom_poste'] ?></td>
<td><?= $s['heure_debut'] ?></td>
<td><?= $s['heure_fin'] ?></td>
<td><?= floor($s['duree_prevue']/60) ?>h <?= $s['duree_prevue']%60 ?>m</td>
<td><?= $s['prix_total'] ?> Ar</td>
<td><?= $s['statut'] ?></td>
</tr>
<?php } ?>

</table>

</body>
</html>