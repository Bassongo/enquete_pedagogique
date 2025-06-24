<?php
require_once('../config/database.php');
requireLogin();

// Récupérer les types d'élections
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$election_types = $stmt->fetchAll();

// Par défaut, afficher les résultats du premier type d'élection
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : ($election_types[0]['id'] ?? 1);

// Récupérer les sessions de vote terminées pour ce type
$stmt = $pdo->prepare("
    SELECT vs.*, et.name as election_type, c.name as club_name
    FROM vote_sessions vs
    LEFT JOIN election_types et ON vs.election_type_id = et.id
    LEFT JOIN clubs c ON vs.club_id = c.id
    WHERE vs.election_type_id = ? AND vs.end_time < NOW()
    ORDER BY vs.end_time DESC
");
$stmt->execute([$type_id]);
$sessions = $stmt->fetchAll();

// Récupérer les résultats pour la dernière session terminée
$results = [];
if (!empty($sessions)) {
    $last_session = $sessions[0];
    $stmt = $pdo->prepare("
        SELECT r.*, u.username as candidate_name, p.name as position_name
        FROM results r
        JOIN candidatures c ON r.candidature_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN positions p ON c.position_id = p.id
        WHERE r.vote_session_id = ?
        ORDER BY r.rank ASC
    ");
    $stmt->execute([$last_session['id']]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats des votes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/resultat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .main-result { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 40px; }
        .main-result h1 { text-align: center; color: #333; font-size: 2.2em; margin-bottom: 30px; }
        .selection { margin-bottom: 30px; text-align: center; }
        .selection label { font-weight: 500; color: #333; margin-right: 10px; }
        .selection select { padding: 8px 15px; border-radius: 8px; border: 2px solid #e1e5e9; font-size: 1em; }
        .resultats-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .resultats-table th, .resultats-table td { padding: 12px 10px; border-bottom: 1px solid #e1e5e9; text-align: left; }
        .resultats-table th { background: #f8f9fa; color: #333; font-weight: 600; }
        .resultats-table tr.winner { background: #d4edda; }
        .resultats-table td.winner-badge { color: #28a745; font-weight: 700; font-size: 1.1em; }
        .no-results { text-align: center; color: #888; margin-top: 40px; font-size: 1.1em; }
        @media (max-width: 700px) { .main-result { padding: 15px; } .resultats-table th, .resultats-table td { padding: 8px 4px; } }
    </style>
</head>
<body>
    <?php include('../components/header.php'); ?>
    <main class="main-result">
        <h1>🎉 Résultats des votes 🎉</h1>
        <form class="selection" method="get">
            <label for="type-result">Type d'élection :</label>
            <select id="type-result" name="type_id" onchange="this.form.submit()">
                <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php if ($type['id'] == $type_id) echo 'selected'; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if (empty($sessions)): ?>
            <div class="no-results">Aucun résultat disponible pour ce type d'élection.</div>
        <?php else: ?>
            <h2 style="text-align:center; margin-top:30px; color:#444; font-size:1.3em;">
                Dernière session : <?php echo htmlspecialchars($sessions[0]['election_type']); ?>
                <?php if ($sessions[0]['club_name']): ?> - <?php echo htmlspecialchars($sessions[0]['club_name']); ?><?php endif; ?>
                <br><span style="font-size:0.95em; color:#888;">Terminée le <?php echo (new DateTime($sessions[0]['end_time']))->format('d/m/Y H:i'); ?></span>
            </h2>
            <?php if (empty($results)): ?>
                <div class="no-results">Aucun résultat calculé pour cette session.</div>
            <?php else: ?>
                <table class="resultats-table">
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Candidat</th>
                            <th>Poste</th>
                            <th>Votes</th>
                            <th>%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                        <tr<?php if ($row['is_winner']) echo ' class="winner"'; ?>>
                            <td><?php echo $row['rank']; ?></td>
                            <td><?php echo htmlspecialchars($row['candidate_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                            <td><?php echo $row['total_votes']; ?></td>
                            <td><?php echo $row['percentage']; ?>%</td>
                            <td class="winner-badge">
                                <?php if ($row['is_winner']): ?>🏆 Vainqueur<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php include('../components/footer.php'); ?>
</body>
</html> 