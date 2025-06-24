// =====================================================
// DASHBOARD ADMINISTRATEUR - GESTION DES INTERACTIONS
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du graphique de participation
    initParticipationChart();
    
    // Gestion des boutons de navigation
    initNavigationButtons();
    
    // Gestion des modals
    initModals();
    
    // Gestion des actions rapides
    initQuickActions();
    
    // Mise à jour automatique des statistiques
    setInterval(updateStats, 30000); // Toutes les 30 secondes
});

// =====================================================
// GRAPHIQUE DE PARTICIPATION
// =====================================================

function initParticipationChart() {
    const ctx = document.getElementById('participationChart');
    if (!ctx) return;
    
    // Préparer les données pour Chart.js
    const labels = [];
    const data = [];
    
    // Remplir les 7 derniers jours
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        
        labels.push(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }));
        
        // Chercher les données correspondantes
        const dayData = participationData.find(item => item.vote_date === dateStr);
        data.push(dayData ? parseInt(dayData.vote_count) : 0);
    }
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votes par jour',
                data: data,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4CAF50',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        color: '#666'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#666'
                    }
                }
            }
        }
    });
}

// =====================================================
// NAVIGATION
// =====================================================

function initNavigationButtons() {
    const buttons = document.querySelectorAll('.sidebar-btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Gérer la navigation
            const buttonId = this.id;
            handleNavigation(buttonId);
        });
    });
}

function handleNavigation(buttonId) {
    switch (buttonId) {
        case 'btn-dashboard':
            showDashboard();
            break;
        case 'btn-gestion-elections':
            showElectionManagement();
            break;
        case 'btn-gestion-candidats':
            showCandidateManagement();
            break;
        case 'btn-gestion-comites':
            showCommitteeManagement();
            break;
        case 'btn-param-admin':
            showAdminSettings();
            break;
        case 'btn-profil':
            window.location.href = 'profil.php';
            break;
        case 'logoutBtn':
            logout();
            break;
    }
}

// =====================================================
// MODALS
// =====================================================

function initModals() {
    // Modal Démarrer Vote
    const startVotesBtn = document.getElementById('startVotesBtn');
    const startVotesModal = document.getElementById('startVotesModal');
    const closeStartVotes = document.getElementById('closeStartVotes');
    const nextToDates = document.getElementById('nextToDates');
    const validateVoteModal = document.getElementById('validateVoteModal');
    const cancelVoteModal = document.getElementById('cancelVoteModal');
    
    if (startVotesBtn) {
        startVotesBtn.addEventListener('click', () => {
            startVotesModal.style.display = 'block';
            loadElectionTypes();
        });
    }
    
    if (closeStartVotes) {
        closeStartVotes.addEventListener('click', () => {
            startVotesModal.style.display = 'none';
            resetVoteModal();
        });
    }
    
    if (nextToDates) {
        nextToDates.addEventListener('click', () => {
            const voteType = document.getElementById('voteType').value;
            if (voteType) {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                loadClubs(voteType);
            } else {
                showNotification('Veuillez sélectionner un type d\'élection', 'error');
            }
        });
    }
    
    if (validateVoteModal) {
        validateVoteModal.addEventListener('click', createVoteSession);
    }
    
    if (cancelVoteModal) {
        cancelVoteModal.addEventListener('click', () => {
            startVotesModal.style.display = 'none';
            resetVoteModal();
        });
    }
    
    // Modal Candidatures
    const startCandBtn = document.getElementById('startCandBtn');
    const startCandModal = document.getElementById('startCandModal');
    const closeStartCand = document.getElementById('closeStartCand');
    const candNextToDate = document.getElementById('candNextToDate');
    const validateCandModal = document.getElementById('validateCandModal');
    const cancelCandModal = document.getElementById('cancelCandModal');
    
    if (startCandBtn) {
        startCandBtn.addEventListener('click', () => {
            startCandModal.style.display = 'block';
            loadElectionTypesForCandidature();
        });
    }
    
    if (closeStartCand) {
        closeStartCand.addEventListener('click', () => {
            startCandModal.style.display = 'none';
            resetCandModal();
        });
    }
    
    if (candNextToDate) {
        candNextToDate.addEventListener('click', () => {
            const candType = document.getElementById('candType').value;
            if (candType) {
                document.getElementById('candStep1').style.display = 'none';
                document.getElementById('candStep2').style.display = 'block';
                loadClubsForCandidature(candType);
            } else {
                showNotification('Veuillez sélectionner une catégorie', 'error');
            }
        });
    }
    
    if (validateCandModal) {
        validateCandModal.addEventListener('click', createCandidatureSession);
    }
    
    if (cancelCandModal) {
        cancelCandModal.addEventListener('click', () => {
            startCandModal.style.display = 'none';
            resetCandModal();
        });
    }
    
    // Fermer les modals en cliquant à l'extérieur
    window.addEventListener('click', (event) => {
        if (event.target === startVotesModal) {
            startVotesModal.style.display = 'none';
            resetVoteModal();
        }
        if (event.target === startCandModal) {
            startCandModal.style.display = 'none';
            resetCandModal();
        }
    });
}

// =====================================================
// ACTIONS RAPIDES
// =====================================================

function initQuickActions() {
    const actionCards = document.querySelectorAll('.action-card');
    
    actionCards.forEach(card => {
        card.addEventListener('click', function() {
            const action = this.querySelector('h3').textContent;
            handleQuickAction(action);
        });
    });
}

function handleQuickAction(action) {
    switch (action) {
        case 'Démarrer un Vote':
            document.getElementById('startVotesBtn').click();
            break;
        case 'Ouvrir Candidatures':
            document.getElementById('startCandBtn').click();
            break;
        case 'Voir Statistiques':
            showStatistics();
            break;
        case 'Paramètres':
            showAdminSettings();
            break;
    }
}

// =====================================================
// REQUÊTES AJAX
// =====================================================

function loadElectionTypes() {
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_election_types'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateElectionTypes(data.data);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

function loadClubs(electionTypeId) {
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=get_clubs&election_type_id=${electionTypeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateClubs(data.data);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function createVoteSession() {
    const voteType = document.getElementById('voteType').value;
    const startTime = document.getElementById('startVote').value;
    const endTime = document.getElementById('endVote').value;
    const clubSelect = document.getElementById('clubSelect');
    const clubId = clubSelect ? clubSelect.value : '';
    
    if (!voteType || !startTime || !endTime) {
        showNotification('Toutes les informations sont requises', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'start_vote_session');
    formData.append('election_type_id', voteType);
    formData.append('club_id', clubId);
    formData.append('start_time', startTime);
    formData.append('end_time', endTime);
    
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('startVotesModal').style.display = 'none';
            resetVoteModal();
            // Recharger la page pour mettre à jour les données
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

function createCandidatureSession() {
    const candType = document.getElementById('candType').value;
    const startCandDate = document.getElementById('startCandDate').value;
    const endCandDate = document.getElementById('endCandDate').value;
    const clubCandSelect = document.getElementById('clubCandSelect');
    const clubId = clubCandSelect ? clubCandSelect.value : '';
    
    if (!candType || !startCandDate || !endCandDate) {
        showNotification('Toutes les informations sont requises', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'start_candidature_session');
    formData.append('election_type_id', candType);
    formData.append('club_id', clubId);
    formData.append('start_time', startCandDate);
    formData.append('end_time', endCandDate);
    
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('startCandModal').style.display = 'none';
            resetCandModal();
            // Recharger la page pour mettre à jour les données
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

function loadElectionTypesForCandidature() {
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_election_types'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateElectionTypesForCandidature(data.data);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

function populateElectionTypesForCandidature(types) {
    const select = document.getElementById('candType');
    select.innerHTML = '<option value="" selected disabled>Choisir une catégorie</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.name;
        select.appendChild(option);
    });
}

function loadClubsForCandidature(electionTypeId) {
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=load_clubs_for_candidature&election_type_id=${electionTypeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateClubsForCandidature(data.data);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

function populateClubsForCandidature(clubs) {
    const container = document.getElementById('candStep2');
    let clubSelect = document.getElementById('clubCandSelect');
    
    if (!clubSelect) {
        clubSelect = document.createElement('select');
        clubSelect.id = 'clubCandSelect';
        clubSelect.className = 'form-control';
        
        const label = document.createElement('label');
        label.innerHTML = '<i class="fas fa-users"></i> Club (optionnel)';
        label.htmlFor = 'clubCandSelect';
        
        const formGroup = document.createElement('div');
        formGroup.className = 'form-group';
        formGroup.appendChild(label);
        formGroup.appendChild(clubSelect);
        
        // Insérer avant les actions
        const actions = container.querySelector('.form-actions');
        container.insertBefore(formGroup, actions);
    }
    
    clubSelect.innerHTML = '<option value="">Aucun club spécifique</option>';
    
    clubs.forEach(club => {
        const option = document.createElement('option');
        option.value = club.id;
        option.textContent = club.name;
        clubSelect.appendChild(option);
    });
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

function populateElectionTypes(types) {
    const select = document.getElementById('voteType');
    select.innerHTML = '<option value="" selected disabled>Choisir un type</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.name;
        select.appendChild(option);
    });
}

function populateClubs(clubs) {
    const container = document.getElementById('step2');
    let clubSelect = document.getElementById('clubSelect');
    
    if (!clubSelect) {
        clubSelect = document.createElement('select');
        clubSelect.id = 'clubSelect';
        clubSelect.className = 'form-control';
        
        const label = document.createElement('label');
        label.innerHTML = '<i class="fas fa-users"></i> Club (optionnel)';
        label.htmlFor = 'clubSelect';
        
        const formGroup = document.createElement('div');
        formGroup.className = 'form-group';
        formGroup.appendChild(label);
        formGroup.appendChild(clubSelect);
        
        // Insérer avant les actions
        const actions = container.querySelector('.form-actions');
        container.insertBefore(formGroup, actions);
    }
    
    clubSelect.innerHTML = '<option value="">Aucun club spécifique</option>';
    
    clubs.forEach(club => {
        const option = document.createElement('option');
        option.value = club.id;
        option.textContent = club.name;
        clubSelect.appendChild(option);
    });
}

function resetVoteModal() {
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('voteType').value = '';
    document.getElementById('startVote').value = '';
    document.getElementById('endVote').value = '';
    
    const clubSelect = document.getElementById('clubSelect');
    if (clubSelect) {
        clubSelect.remove();
    }
}

function resetCandModal() {
    document.getElementById('candStep1').style.display = 'block';
    document.getElementById('candStep2').style.display = 'none';
    document.getElementById('candType').value = '';
    document.getElementById('startCandDate').value = '';
    document.getElementById('endCandDate').value = '';
    
    const clubCandSelect = document.getElementById('clubCandSelect');
    if (clubCandSelect) {
        clubCandSelect.remove();
    }
}

function showNotification(message, type = 'info') {
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    // Ajouter au body
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Fermer automatiquement après 5 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Fermer manuellement
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

function updateStats() {
    fetch('../admin/actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=get_dashboard_stats'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateDashboardStats(data.data);
        }
    })
    .catch(error => {
        console.error('Erreur mise à jour stats:', error);
    });
}

function updateDashboardStats(stats) {
    // Mettre à jour les statistiques affichées
    const statCards = document.querySelectorAll('.stat-card h3');
    if (statCards.length >= 4) {
        statCards[0].textContent = number_format(stats.total_students);
        statCards[1].textContent = number_format(stats.total_votes);
        statCards[2].textContent = number_format(stats.total_candidates);
        statCards[3].textContent = number_format(stats.active_elections);
    }
}

function number_format(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        window.location.href = '../logout.php';
    }
}

// =====================================================
// FONCTIONS DE NAVIGATION (À IMPLÉMENTER)
// =====================================================

function showDashboard() {
    // Déjà sur le dashboard
}

function showElectionManagement() {
    // Rediriger vers la page de gestion des élections
    window.location.href = 'elections.php';
}

function showCandidateManagement() {
    // Rediriger vers la page de gestion des candidats
    window.location.href = 'candidates.php';
}

function showCommitteeManagement() {
    // Rediriger vers la page de gestion des comités
    window.location.href = 'committees.php';
}

function showAdminSettings() {
    // Rediriger vers la page des paramètres admin
    window.location.href = 'settings.php';
}

function showStatistics() {
    // Rediriger vers la page des statistiques
    window.location.href = 'statistics.php';
}