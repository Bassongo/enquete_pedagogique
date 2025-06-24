<?php
require_once('../config/database.php');
requireLogin();

// Récupérer les types d'élections
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$election_types = $stmt->fetchAll();

// Récupérer les clubs actifs
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();

// Par défaut, type sélectionné = premier type
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : ($election_types[0]['id'] ?? 1);
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;

// Récupérer la session de candidature active pour ce type (et club si club)
if ($type_id == 2 && $club_id) { // Club
    $stmt = $pdo->prepare("
        SELECT cs.* FROM candidature_sessions cs
        WHERE cs.is_active = 1 AND cs.election_type_id = ? AND cs.club_id = ?
        AND cs.start_time <= NOW() AND cs.end_time >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$type_id, $club_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT cs.* FROM candidature_sessions cs
        WHERE cs.is_active = 1 AND cs.election_type_id = ?
        AND cs.start_time <= NOW() AND cs.end_time >= NOW()
        LIMIT 1
    ");
    $stmt->execute([$type_id]);
}
$active_session = $stmt->fetch();

// Récupérer les candidats approuvés pour cette session
$candidates = [];
if ($active_session) {
    if ($type_id == 2 && $club_id) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.classe, p.name as position_name
            FROM candidatures c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN positions p ON c.position_id = p.id
            WHERE c.election_type_id = ? AND c.status = 'approved' AND c.club_id = ?
            ORDER BY p.name, u.username
        ");
        $stmt->execute([$type_id, $club_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.classe, p.name as position_name
            FROM candidatures c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN positions p ON c.position_id = p.id
            WHERE c.election_type_id = ? AND c.status = 'approved'
            ORDER BY p.name, u.username
        ");
        $stmt->execute([$type_id]);
    }
    $candidates = $stmt->fetchAll();
}

// Récupérer les positions pour ce type d'élection
$stmt = $pdo->prepare("SELECT id, name FROM positions WHERE election_type_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$type_id]);
$positions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Campagnes électorales - Vote ENSAE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/campagne.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
    body { 
        background: linear-gradient(135deg, #f4f7fb 0%, #e0e7ff 100%); 
        font-family: 'Montserrat', Arial, sans-serif; 
        min-height: 100vh;
    }
    .main-campagne { 
        max-width: 1200px; 
        margin: 2rem auto; 
        padding: 2.5rem 1.5rem; 
        background: #fff; 
        border-radius: 22px; 
        box-shadow: 0 8px 32px 0 rgba(37,99,235,0.10); 
    }
    .campagne-header { 
        text-align: center; 
        margin-bottom: 2.5rem; 
    }
    .campagne-header h1 { 
        color: #2563eb; 
        font-size: 2.7em; 
        font-weight: 700; 
        margin-bottom: 10px; 
        letter-spacing: -1px; 
    }
    .campagne-header p { 
        color: #555; 
        font-size: 1.15em; 
    }
    .selection { 
        background: #f8fafc; 
        border-radius: 16px; 
        padding: 2rem; 
        margin-bottom: 2rem; 
        box-shadow: 0 2px 10px #2563eb11; 
    }
    .selection form { 
        display: flex; 
        gap: 1.5rem; 
        align-items: center; 
        justify-content: center; 
        flex-wrap: wrap; 
    }
    .selection label { 
        font-weight: 600; 
        color: #2563eb; 
        font-size: 1.05em; 
    }
    .selection select { 
        padding: 10px 15px; 
        border-radius: 10px; 
        border: 2px solid #e1e5e9; 
        font-size: 1em; 
        background: #fff; 
        color: #333; 
        transition: border-color 0.2s; 
        min-width: 180px; 
    }
    .selection select:focus { 
        outline: none; 
        border-color: #2563eb; 
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1); 
    }
    .campagne-info { 
        background: linear-gradient(135deg, #e0e7ff 60%, #f0fdfa 100%); 
        border-radius: 16px; 
        padding: 1.5rem; 
        margin-bottom: 2rem; 
        text-align: center; 
        box-shadow: 0 2px 10px #2563eb11; 
    }
    .campagne-info .status-active { 
        color: #1abc9c; 
        font-weight: 600; 
        font-size: 1.1em; 
    }
    .campagne-info .status-inactive { 
        color: #e74c3c; 
        font-weight: 600; 
        font-size: 1.1em; 
    }
    .no-sessions { 
        text-align: center; 
        padding: 3rem 2rem; 
        background: #f8fafc; 
        border-radius: 16px; 
        box-shadow: 0 2px 10px #2563eb11; 
    }
    .no-sessions i { 
        font-size: 3em; 
        color: #cbd5e1; 
        margin-bottom: 1rem; 
    }
    .no-sessions h3 { 
        color: #64748b; 
        font-size: 1.3em; 
        margin-bottom: 0.5rem; 
    }
    .no-sessions p { 
        color: #94a3b8; 
        font-size: 1.05em; 
    }
    .candidates-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
        gap: 2rem; 
        margin-top: 2rem; 
    }
    .candidate-card { 
        background: linear-gradient(135deg, #f8fafc 60%, #f1f5f9 100%); 
        border-radius: 18px; 
        box-shadow: 0 4px 20px 0 rgba(37,99,235,0.08); 
        overflow: hidden; 
        transition: all 0.3s ease; 
        border: 1px solid #e2e8f0; 
    }
    .candidate-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 30px 0 rgba(37,99,235,0.15); 
    }
    .candidate-header { 
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
        color: #fff; 
        padding: 2rem 1.5rem; 
        text-align: center; 
        position: relative; 
    }
    .candidate-photo { 
        margin-bottom: 1rem; 
    }
    .candidate-photo img { 
        width: 100px; 
        height: 100px; 
        border-radius: 50%; 
        object-fit: cover; 
        border: 4px solid #fff; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
    }
    .candidate-info h4 { 
        font-size: 1.4em; 
        font-weight: 700; 
        margin-bottom: 0.5rem; 
        color: #fff; 
    }
    .candidate-class, .candidate-position { 
        display: block; 
        font-size: 0.95em; 
        opacity: 0.9; 
        margin-bottom: 0.3rem; 
    }
    .candidate-program { 
        padding: 2rem 1.5rem; 
    }
    .candidate-program strong { 
        color: #2563eb; 
        font-size: 1.1em; 
        display: block; 
        margin-bottom: 1rem; 
        font-weight: 600; 
    }
    .candidate-program p { 
        color: #4a5568; 
        line-height: 1.6; 
        font-size: 0.95em; 
        margin: 0; 
        max-height: 120px; 
        overflow-y: auto; 
    }
    .position-badge { 
        background: rgba(255,255,255,0.2); 
        color: #fff; 
        padding: 4px 12px; 
        border-radius: 20px; 
        font-size: 0.85em; 
        font-weight: 600; 
        display: inline-block; 
        margin-top: 0.5rem; 
    }
    .stats-overview { 
        display: flex; 
        gap: 1.5rem; 
        margin-bottom: 2rem; 
        flex-wrap: wrap; 
        justify-content: center; 
    }
    .stat-item { 
        background: linear-gradient(135deg, #e0e7ff 60%, #f0fdfa 100%); 
        border-radius: 12px; 
        padding: 1rem 1.5rem; 
        text-align: center; 
        box-shadow: 0 2px 10px #2563eb11; 
        min-width: 120px; 
    }
    .stat-number { 
        font-size: 1.8em; 
        font-weight: 700; 
        color: #2563eb; 
        display: block; 
    }
    .stat-label { 
        font-size: 0.9em; 
        color: #64748b; 
        margin-top: 0.3rem; 
    }
    @media (max-width: 768px) { 
        .main-campagne { 
            margin: 1rem; 
            padding: 1.5rem 1rem; 
        } 
        .campagne-header h1 { 
            font-size: 2em; 
        } 
        .selection form { 
            flex-direction: column; 
            gap: 1rem; 
        } 
        .selection select { 
            width: 100%; 
            max-width: 300px; 
        } 
        .candidates-grid { 
            grid-template-columns: 1fr; 
            gap: 1.5rem; 
        } 
        .stats-overview { 
            flex-direction: column; 
            align-items: center; 
        } 
    }
    </style>
</head>
<body>
    <?php include('../components/header.php'); ?>
    
    <main class="main-campagne">
        <div class="campagne-header">
            <h1><i class="fas fa-bullhorn"></i> Campagnes Électorales</h1>
            <p>Découvrez les candidats et leurs programmes pour les élections en cours</p>
        </div>

        <!-- Sélecteurs -->
        <section class="selection">
            <form method="get" id="typeForm">
                <label for="type-election"><i class="fas fa-filter"></i> Type d'élection :</label>
                <select id="type-election" name="type_id" onchange="this.form.submit()">
                    <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php if ($type['id'] == $type_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($type_id == 2): ?>
                <label for="club-select"><i class="fas fa-users"></i> Club :</label>
                <select id="club-select" name="club_id" onchange="this.form.submit()">
                    <option value="">-- Tous les clubs --</option>
                    <?php foreach ($clubs as $club): ?>
                    <option value="<?php echo $club['id']; ?>" <?php if ($club_id == $club['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($club['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </form>
        </section>

        <!-- Informations de la campagne -->
        <div class="campagne-info">
            <?php if (!$active_session): ?>
            <div class="status-inactive">
                <i class="fas fa-calendar-times"></i> Aucune campagne active pour ce type
                <?php if ($type_id == 2 && $club_id) echo ' et ce club'; ?>
            </div>
            <?php else: ?>
            <div class="status-active">
                <i class="fas fa-calendar-check"></i> Session ouverte jusqu'au 
                <strong><?php echo (new DateTime($active_session['end_time']))->format('d/m/Y à H:i'); ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistiques rapides -->
        <?php if ($active_session && !empty($candidates)): ?>
        <div class="stats-overview">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($candidates); ?></span>
                <span class="stat-label">Candidats</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count(array_unique(array_column($candidates, 'position_name'))); ?></span>
                <span class="stat-label">Postes</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo count(array_unique(array_column($candidates, 'classe'))); ?></span>
                <span class="stat-label">Classes</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenu des candidats -->
        <section id="contenu-election">
            <?php if (!$active_session): ?>
            <div class="no-sessions">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucune campagne en cours</h3>
                <p>Il n'y a actuellement aucune campagne ouverte pour ce type d'élection
                <?php if ($type_id == 2 && $club_id) echo ' et ce club'; ?>.</p>
            </div>
            <?php elseif (empty($candidates)): ?>
            <div class="no-sessions">
                <i class="fas fa-users"></i>
                <h3>Aucun candidat pour cette campagne</h3>
                <p>Aucun candidat n'a encore été approuvé pour cette session de campagne.</p>
            </div>
            <?php else: ?>
            <div class="candidates-grid">
                <?php foreach ($candidates as $cand): ?>
                <div class="candidate-card">
                    <div class="candidate-header">
                        <div class="candidate-photo">
                            <?php if (!empty($cand['photo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($cand['photo_url']); ?>"
                                alt="Photo de <?php echo htmlspecialchars($cand['username']); ?>">
                            <?php else: ?>
                            <img src="../assets/img/default-avatar.png" alt="Photo par défaut">
                            <?php endif; ?>
                        </div>
                        <div class="candidate-info">
                            <h4><?php echo htmlspecialchars($cand['username']); ?></h4>
                            <span class="candidate-class">
                                <i class="fas fa-graduation-cap"></i> Classe <?php echo htmlspecialchars($cand['classe']); ?>
                            </span>
                            <span class="candidate-position">
                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($cand['position_name']); ?>
                            </span>
                            <div class="position-badge">
                                <?php echo htmlspecialchars($cand['position_name']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="candidate-program">
                        <strong><i class="fas fa-file-alt"></i> Programme électoral :</strong>
                        <p><?php echo nl2br(htmlspecialchars($cand['programme'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include('../components/footer.php'); ?>
    <script src="../assets/js/state.js"></script>
    <script src="../assets/js/campagne.js"></script>
</body>
</html> 