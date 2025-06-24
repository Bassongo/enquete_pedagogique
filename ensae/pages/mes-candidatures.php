<?php
require_once('../config/database.php');
requireLogin();

// Récupérer les candidatures de l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT c.*, et.name as election_type, p.name as position_name, cl.name as club_name
    FROM candidatures c
    LEFT JOIN election_types et ON c.election_type_id = et.id
    LEFT JOIN positions p ON c.position_id = p.id
    LEFT JOIN clubs cl ON c.club_id = cl.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$candidatures = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Candidatures - Vote ENSAE</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/acceuils.css">
  <link rel="stylesheet" href="../assets/css/mes-candidatures.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .candidatures-list {
      margin-top: 30px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }
    .candidature-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.07);
      padding: 22px 24px;
      border: 1px solid #e1e5e9;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .candidature-card h3 {
      margin: 0 0 5px 0;
      font-size: 1.2em;
      color: #333;
    }
    .candidature-card .candidature-meta {
      font-size: 0.98em;
      color: #666;
      margin-bottom: 8px;
    }
    .candidature-card .candidature-status {
      font-weight: 600;
      border-radius: 6px;
      padding: 4px 12px;
      display: inline-block;
      font-size: 0.95em;
    }
    .candidature-status.pending { background: #fff3cd; color: #856404; }
    .candidature-status.approved { background: #d4edda; color: #155724; }
    .candidature-status.rejected { background: #f8d7da; color: #721c24; }
    .candidature-card .programme {
      font-size: 0.97em;
      color: #444;
      margin-top: 8px;
      background: #f8f9fa;
      border-radius: 8px;
      padding: 10px 12px;
      min-height: 40px;
    }
    .candidature-card .date {
      font-size: 0.92em;
      color: #888;
      margin-top: 6px;
    }
    .form-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }
    .secondary-btn {
      background: #6c757d;
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 1em;
      font-weight: 500;
      text-decoration: none;
      transition: background 0.2s;
      display: inline-block;
    }
    .secondary-btn:hover {
      background: #495057;
    }
    @media (max-width: 700px) {
      .candidatures-list { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <?php include('../components/header.php'); ?>
  <div class="container">
    <div id="mesCandidaturesPage">
      <h1>Mes candidatures</h1>
      <div class="candidatures-list">
        <?php if (empty($candidatures)): ?>
          <div class="candidature-card">
            <h3>Aucune candidature</h3>
            <div class="candidature-meta">Vous n'avez pas encore soumis de candidature.</div>
          </div>
        <?php else: ?>
          <?php foreach ($candidatures as $cand): ?>
            <div class="candidature-card">
              <h3><?php echo htmlspecialchars($cand['election_type']); ?>
                <?php if ($cand['club_name']): ?> - <?php echo htmlspecialchars($cand['club_name']); ?><?php endif; ?>
              </h3>
              <div class="candidature-meta">
                Poste : <strong><?php echo htmlspecialchars($cand['position_name']); ?></strong>
              </div>
              <span class="candidature-status <?php echo $cand['status']; ?>">
                <?php
                  if ($cand['status'] === 'pending') echo 'En attente';
                  elseif ($cand['status'] === 'approved') echo 'Approuvée';
                  else echo 'Rejetée';
                ?>
              </span>
              <div class="programme">
                <?php echo nl2br(htmlspecialchars($cand['programme'])); ?>
              </div>
              <div class="date">
                Soumise le <?php echo (new DateTime($cand['created_at']))->format('d/m/Y H:i'); ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="form-actions">
        <a href="candidat.php" class="secondary-btn"><i class="fas fa-arrow-left"></i> Retour</a>
      </div>
    </div>
  </div>
  <?php include('../components/footer.php'); ?>
</body>
</html> 