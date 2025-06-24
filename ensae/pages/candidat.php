<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté
requireLogin();

// Récupérer les sessions de candidature actives
$stmt = $pdo->query("
    SELECT 
        cs.*,
        et.name as election_type,
        c.name as club_name
    FROM candidature_sessions cs
    LEFT JOIN election_types et ON cs.election_type_id = et.id
    LEFT JOIN clubs c ON cs.club_id = c.id
    WHERE cs.is_active = 1 
    AND cs.start_time <= NOW() 
    AND cs.end_time >= NOW()
    ORDER BY cs.start_time DESC
");
$active_sessions = $stmt->fetchAll();

// Récupérer les types d'élections
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$election_types = $stmt->fetchAll();

// Récupérer les clubs
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();

// Récupérer les positions
$stmt = $pdo->query("SELECT id, name, election_type_id FROM positions WHERE is_active = 1 ORDER BY name");
$positions = $stmt->fetchAll();

// Vérifier si l'utilisateur a déjà une candidature en cours
$stmt = $pdo->prepare("
    SELECT COUNT(*) as has_candidature 
    FROM candidatures 
    WHERE user_id = ? AND status IN ('pending', 'approved')
");
$stmt->execute([$_SESSION['user_id']]);
$hasCandidature = $stmt->fetch()['has_candidature'] > 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une candidature - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/candidat.css">
    <style>
    .candidat-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .candidat-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .candidat-header h1 {
        color: #333;
        font-size: 2.5em;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .candidat-header p {
        color: #666;
        font-size: 1.1em;
    }

    .sessions-info {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .session-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #e1e5e9;
    }

    .session-card h3 {
        color: #333;
        font-size: 1.2em;
        margin-bottom: 10px;
    }

    .session-card p {
        color: #666;
        margin-bottom: 15px;
    }

    .session-time {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        text-align: center;
        font-size: 0.9em;
        font-weight: 600;
    }

    .candidature-form {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .form-section {
        margin-bottom: 30px;
    }

    .form-section h3 {
        color: #333;
        font-size: 1.3em;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section h3 i {
        color: #667eea;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: #333;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .no-sessions {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .no-sessions i {
        font-size: 4em;
        color: #ccc;
        margin-bottom: 20px;
    }

    .no-sessions h3 {
        color: #666;
        font-size: 1.5em;
        margin-bottom: 10px;
    }

    .no-sessions p {
        color: #999;
        font-size: 1.1em;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }

    @media (max-width: 768px) {
        .candidat-container {
            padding: 0 15px;
            margin: 20px auto;
        }

        .candidat-header h1 {
            font-size: 2em;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }
    }
    </style>
</head>

<body>
    <?php include('../components/header.php'); ?>

    <div class="candidat-container">
        <div class="candidat-header">
            <h1><i class="fas fa-user-plus"></i> Créer une candidature</h1>
            <p>Postulez pour représenter votre communauté étudiante</p>
        </div>

        <?php if ($hasCandidature): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Attention :</strong> Vous avez déjà une candidature en cours.
                Vous ne pouvez pas soumettre plusieurs candidatures simultanément.
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($active_sessions)): ?>
        <div class="no-sessions">
            <i class="fas fa-calendar-times"></i>
            <h3>Aucune session de candidature active</h3>
            <p>Il n'y a actuellement aucune session de candidature ouverte. Revenez plus tard pour postuler.</p>
        </div>
        <?php else: ?>
        <div class="sessions-info">
            <h2><i class="fas fa-info-circle"></i> Sessions de candidature ouvertes</h2>
            <div class="sessions-grid">
                <?php foreach ($active_sessions as $session): ?>
                <?php
                    $now = new DateTime();
                    $end = new DateTime($session['end_time']);
                    $timeLeft = $now->diff($end);
                ?>
                <div class="session-card">
                    <h3><?php echo htmlspecialchars($session['election_type']); ?></h3>
                    <p>
                        <?php if ($session['club_name']): ?>
                        <?php echo htmlspecialchars($session['club_name']); ?>
                        <?php else: ?>
                        Élection générale
                        <?php endif; ?>
                    </p>
                    <div class="session-time">
                        <i class="fas fa-clock"></i>
                        Ferme dans <?php echo $timeLeft->format('%h h %i m'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="candidature-form">
            <form id="candidatureForm" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required
                                value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="classe">Classe *</label>
                        <select id="classe" name="classe" required>
                            <option value="">-- Sélectionnez une classe --</option>
                            <option value="AS1" <?php echo ($_SESSION['classe'] ?? '') === 'AS1' ? 'selected' : ''; ?>>
                                AS1</option>
                            <option value="AS2" <?php echo ($_SESSION['classe'] ?? '') === 'AS2' ? 'selected' : ''; ?>>
                                AS2</option>
                            <option value="AS3" <?php echo ($_SESSION['classe'] ?? '') === 'AS3' ? 'selected' : ''; ?>>
                                AS3</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-vote-yea"></i> Détails de la candidature</h3>
                    <div class="form-group">
                        <label for="electionType">Type d'élection *</label>
                        <select id="electionType" name="electionType" required>
                            <option value="">-- Sélectionnez un type d'élection --</option>
                            <?php foreach ($election_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="clubGroup" style="display:none;">
                        <label for="clubSelect">Club *</label>
                        <select id="clubSelect" name="club">
                            <option value="">-- Sélectionnez un club --</option>
                            <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="positionSelect">Poste *</label>
                        <select id="positionSelect" name="position" required>
                            <option value="">-- Sélectionnez d'abord un type d'élection --</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-file-alt"></i> Programme électoral</h3>
                    <div class="form-group">
                        <label for="programme">Votre programme électoral *</label>
                        <textarea id="programme" name="programme" rows="8" required
                            placeholder="Décrivez votre programme électoral, vos objectifs et vos propositions..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-image"></i> Photo de profil</h3>
                    <div class="form-group">
                        <label for="photo">Photo de profil (optionnel)</label>
                        <input type="file" id="photo" name="photo" accept="image/*">
                        <small style="color: #666; margin-top: 5px; display: block;">
                            Formats acceptés : JPG, PNG, GIF. Taille maximale : 2MB.
                        </small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" <?php echo $hasCandidature ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane"></i>
                        Soumettre ma candidature
                    </button>
                    <a href="mes-candidatures.php" class="btn-secondary">
                        <i class="fas fa-list"></i>
                        Mes candidatures
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <?php include('../components/footer.php'); ?>

    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script>
    // Données des positions par type d'élection
    const positionsData = <?php echo json_encode($positions); ?>;

    // Gestion du changement de type d'élection
    document.getElementById('electionType').addEventListener('change', function() {
        const electionTypeId = this.value;
        const clubGroup = document.getElementById('clubGroup');
        const positionSelect = document.getElementById('positionSelect');

        // Réinitialiser les sélections
        positionSelect.innerHTML = '<option value="">-- Sélectionnez un poste --</option>';

        if (electionTypeId) {
            // Filtrer les positions pour ce type d'élection
            const positions = positionsData.filter(pos => pos.election_type_id == electionTypeId);

            positions.forEach(position => {
                const option = document.createElement('option');
                option.value = position.id;
                option.textContent = position.name;
                positionSelect.appendChild(option);
            });

            // Afficher/masquer le sélecteur de club
            if (electionTypeId == 2) { // Type Club
                clubGroup.style.display = 'block';
                document.getElementById('clubSelect').required = true;
            } else {
                clubGroup.style.display = 'none';
                document.getElementById('clubSelect').required = false;
            }
        } else {
            clubGroup.style.display = 'none';
            document.getElementById('clubSelect').required = false;
        }
    });

    // Soumission du formulaire
    document.getElementById('candidatureForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'submit_candidature');

        // Validation côté client améliorée
        const electionType = formData.get('electionType');
        const position = formData.get('position');
        const programme = formData.get('programme');
        const nom = formData.get('nom');
        const prenom = formData.get('prenom');
        const classe = formData.get('classe');
        const club = formData.get('club');

        // Validation des champs obligatoires
        if (!electionType || !position || !programme || !nom || !prenom || !classe) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        // Validation du programme (50-5000 caractères comme côté serveur)
        if (programme.length < 50) {
            alert('Le programme électoral doit contenir au moins 50 caractères');
            return;
        }

        if (programme.length > 5000) {
            alert('Le programme électoral ne peut pas dépasser 5000 caractères');
            return;
        }

        // Validation spécifique pour les élections de club
        if (electionType == 2 && !club) {
            alert('Veuillez sélectionner un club pour ce type d\'élection');
            return;
        }

        // Validation du fichier photo
        const photoFile = formData.get('photo');
        if (photoFile && photoFile.size > 0) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (!allowedTypes.includes(photoFile.type)) {
                alert('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                return;
            }

            if (photoFile.size > maxSize) {
                alert('Fichier trop volumineux. Taille maximale : 2MB.');
                return;
            }
        }

        if (confirm('Êtes-vous sûr de vouloir soumettre votre candidature ?')) {
            // Afficher un indicateur de chargement
            const submitBtn = document.querySelector('.btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
            submitBtn.disabled = true;

            fetch('../actions/candidature_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('RAW RESPONSE:', response);
                    return response.text().then(text => {
                        console.log('RAW TEXT:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Réponse non JSON: ' + text);
                        }
                    });
                })
                .then(data => {
                    console.log('DATA:', data);
                    if (data.success) {
                        displayCandidates(data.data.candidates, data.data.session_info);
                        document.getElementById('voteModal').style.display = 'block';
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion. Veuillez réessayer.');
                })
                .finally(() => {
                    // Restaurer le bouton
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        }
    });
    </script>
</body>

</html>