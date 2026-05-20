<?php
require_once "../config/db.php";
date_default_timezone_set('Indian/Antananarivo');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement Poste</title>
</head>
<body>

<h1>💰 Paiement Cybercafé (Poste)</h1>

<hr>

<?php
/* ======================================================
   💰 TRAITEMENT PAIEMENT
====================================================== */

if(isset($_POST['pay'])){

    $id_session = $_POST['id_session'];
    $mode = $_POST['mode_paiement'];

    // 🔍 check session
    $req = $bd->prepare("
        SELECT *
        FROM session
        WHERE id_session = ?
        AND statut = 'terminée'
    ");
    $req->execute([$id_session]);
    $session = $req->fetch(PDO::FETCH_ASSOC);

    if(!$session){
        echo "❌ Session invalide ou pas encore terminée";
    }
    else {

        // 💰 montant (sécurité: recalcul)
        $duree = $session['duree_prevue'];
        $montant = $session['prix_total'];

        // 🔒 éviter double paiement
        $check = $bd->prepare("
            SELECT COUNT(*) FROM paiement
            WHERE id_session = ?
        ");
        $check->execute([$id_session]);

        if($check->fetchColumn() > 0){
            die("❌ Déjà payé");
        }

        // 💾 insert paiement
        $bd->prepare("
            INSERT INTO paiement (id_session, montant, mode_paiement, statut)
            VALUES (?, ?, ?, 'payé')
        ")->execute([
            $id_session,
            $montant,
            $mode
        ]);

        echo "✅ Paiement validé";
    }
}
?>

<!-- ======================================================
     FORMULAIRE
====================================================== -->

<h2>➕ Paiement Session Terminée</h2>

<form method="POST">

    <select name="id_session" required>
        <option value="">-- Session terminée --</option>

        <?php
        $sessions = $bd->query("
            SELECT s.*, c.nom, c.prenom, p.nom_poste
            FROM session s
            LEFT JOIN client c ON s.id_client = c.id_client
            LEFT JOIN poste p ON s.id_poste = p.id_poste
            WHERE s.statut = 'terminée'
            ORDER BY s.id_session DESC
        ");

        foreach($sessions as $s){
            echo "<option value='{$s['id_session']}'>
                    #{$s['id_session']} |
                    {$s['nom']} {$s['prenom']} |
                    {$s['nom_poste']} |
                    {$s['prix_total']} Ar
                  </option>";
        }
        ?>
    </select>

    <select name="mode_paiement" required>
        <option value="cash">Cash</option>
        <option value="mobile_money">Mobile Money</option>
    </select>

    <button type="submit" name="pay">Valider Paiement</button>
</form>

<hr>

<!-- ======================================================
     HISTORIQUE
====================================================== -->

<h2>📋 Paiements</h2>

<?php
$paiements = $bd->query("
    SELECT p.*, s.id_session
    FROM paiement p
    JOIN session s ON p.id_session = s.id_session
    ORDER BY p.id_paiement DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<table border="1" cellpadding="8">

<tr>
    <th>ID</th>
    <th>Session</th>
    <th>Montant</th>
    <th>Mode</th>
    <th>Date</th>
    <th>Statut</th>
</tr>

<?php foreach($paiements as $p){ ?>
<tr>
    <td><?= $p['id_paiement'] ?></td>
    <td><?= $p['id_session'] ?></td>
    <td><?= $p['montant'] ?> Ar</td>
    <td><?= $p['mode_paiement'] ?></td>
    <td><?= $p['date_paiement'] ?></td>
    <td><?= $p['statut'] ?></td>
</tr>
<?php } ?>

</table>
<hr>

<h2>📊 Statistiques Paiement</h2>

<?php
// Total général
$total = $bd->query("
    SELECT SUM(montant) 
    FROM paiement
")->fetchColumn();

// Cash
$cash = $bd->query("
    SELECT SUM(montant)
    FROM paiement
    WHERE mode_paiement = 'cash'
")->fetchColumn();

// Mobile Money
$mobile = $bd->query("
    SELECT SUM(montant)
    FROM paiement
    WHERE mode_paiement = 'mobile_money'
")->fetchColumn();

// Nombre paiements
$nb = $bd->query("
    SELECT COUNT(*)
    FROM paiement
")->fetchColumn();

// Revenu du jour
$today = $bd->query("
    SELECT SUM(montant)
    FROM paiement
    WHERE DATE(date_paiement) = CURDATE()
")->fetchColumn();
?>

<div style="display:flex; gap:20px; flex-wrap:wrap;">

    <div style="border:1px solid #ccc; padding:10px;">
        💰 Total<br>
        <b><?= $total ?? 0 ?> Ar</b>
    </div>

    <div style="border:1px solid #ccc; padding:10px;">
        💵 Cash<br>
        <b><?= $cash ?? 0 ?> Ar</b>
    </div>

    <div style="border:1px solid #ccc; padding:10px;">
        📱 Mobile Money<br>
        <b><?= $mobile ?? 0 ?> Ar</b>
    </div>

    <div style="border:1px solid #ccc; padding:10px;">
        📊 Nombre paiements<br>
        <b><?= $nb ?></b>
    </div>

    <div style="border:1px solid #ccc; padding:10px;">
        📅 Aujourd’hui<br>
        <b><?= $today ?? 0 ?> Ar</b>
    </div>

</div>

</body>
</html>