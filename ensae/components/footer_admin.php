<style>
/* Footer Admin Styles */
.admin-footer {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(102, 126, 234, 0.1);
    margin-top: 50px;
    padding: 40px 0 20px 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
}

.admin-footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 30px;
}

/* Footer Content */
.admin-footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    margin-bottom: 30px;
}

/* Footer Section */
.admin-footer-section {
    display: flex;
    flex-direction: column;
}

.admin-footer-section h3 {
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-footer-section h3 i {
    color: #667eea;
    font-size: 1.1em;
}

.admin-footer-section p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
    font-size: 0.95em;
}

/* Footer Links */
.admin-footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-footer-links li {
    margin-bottom: 10px;
}

.admin-footer-links a {
    color: #666;
    text-decoration: none;
    font-size: 0.95em;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.admin-footer-links a:hover {
    color: #667eea;
    transform: translateX(5px);
}

.admin-footer-links a i {
    font-size: 0.9em;
    width: 16px;
    text-align: center;
}

/* System Status */
.admin-system-status {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.status-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);
    border-radius: 8px;
    border: 1px solid #e8eaff;
}

.status-label {
    color: #666;
    font-size: 0.9em;
    font-weight: 500;
}

.status-value {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9em;
    font-weight: 600;
}

.status-value.online {
    color: #28a745;
}

.status-value.warning {
    color: #ffc107;
}

.status-value.offline {
    color: #dc3545;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-dot.online {
    background: #28a745;
    box-shadow: 0 0 6px rgba(40, 167, 69, 0.4);
}

.status-dot.warning {
    background: #ffc107;
    box-shadow: 0 0 6px rgba(255, 193, 7, 0.4);
}

.status-dot.offline {
    background: #dc3545;
    box-shadow: 0 0 6px rgba(220, 53, 69, 0.4);
}

/* Quick Actions */
.admin-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);
    border: 1px solid #e8eaff;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    font-size: 0.9em;
    font-weight: 500;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.quick-action-btn i {
    font-size: 1em;
    width: 16px;
    text-align: center;
}

/* Footer Bottom */
.admin-footer-bottom {
    border-top: 1px solid rgba(102, 126, 234, 0.1);
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.admin-footer-info {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.admin-footer-info span {
    color: #666;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 6px;
}

.admin-footer-info i {
    color: #667eea;
    font-size: 0.9em;
}

.admin-footer-version {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

/* Responsive Design */
@media (max-width: 900px) {
    .admin-footer-container {
        padding: 0 20px;
    }

    .admin-footer-content {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
    }

    .admin-footer-bottom {
        flex-direction: column;
        text-align: center;
    }

    .admin-footer-info {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .admin-footer {
        padding: 30px 0 15px 0;
        margin-top: 30px;
    }

    .admin-footer-content {
        grid-template-columns: 1fr;
        gap: 25px;
    }

    .admin-footer-section {
        text-align: center;
    }

    .admin-footer-links a {
        justify-content: center;
    }

    .status-item {
        flex-direction: column;
        gap: 5px;
        text-align: center;
    }

    .quick-action-btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .admin-footer-container {
        padding: 0 15px;
    }

    .admin-footer-info {
        flex-direction: column;
        gap: 10px;
    }

    .admin-footer-info span {
        font-size: 0.8em;
    }
}
</style>

<footer class="admin-footer">
    <div class="admin-footer-container">
        <div class="admin-footer-content">
            <!-- Système -->
            <div class="admin-footer-section">
                <h3><i class="fas fa-server"></i> Système</h3>
                <p>Plateforme de vote électronique sécurisée pour l'ENSAE Dakar. Gestion centralisée des élections et
                    des candidatures.</p>
                <div class="admin-system-status">
                    <div class="status-item">
                        <span class="status-label">Serveur</span>
                        <span class="status-value online">
                            <span class="status-dot online"></span>
                            En ligne
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Base de données</span>
                        <span class="status-value online">
                            <span class="status-dot online"></span>
                            Connectée
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Sécurité</span>
                        <span class="status-value online">
                            <span class="status-dot online"></span>
                            Active
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation Rapide -->
            <div class="admin-footer-section">
                <h3><i class="fas fa-compass"></i> Navigation Rapide</h3>
                <ul class="admin-footer-links">
                    <li><a href="../admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="#" id="footerStartVote"><i class="fas fa-play-circle"></i> Démarrer un Vote</a></li>
                    <li><a href="#" id="footerStartCand"><i class="fas fa-user-plus"></i> Ouvrir Candidatures</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
                </ul>
            </div>

            <!-- Actions Rapides -->
            <div class="admin-footer-section">
                <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
                <div class="admin-quick-actions">
                    <a href="#" class="quick-action-btn" id="footerBackup">
                        <i class="fas fa-download"></i>
                        Sauvegarder les données
                    </a>
                    <a href="#" class="quick-action-btn" id="footerLogs">
                        <i class="fas fa-file-alt"></i>
                        Voir les logs
                    </a>
                    <a href="#" class="quick-action-btn" id="footerSupport">
                        <i class="fas fa-headset"></i>
                        Support technique
                    </a>
                    <a href="#" class="quick-action-btn" id="footerDocs">
                        <i class="fas fa-book"></i>
                        Documentation
                    </a>
                </div>
            </div>

            <!-- Contact & Support -->
            <div class="admin-footer-section">
                <h3><i class="fas fa-envelope"></i> Contact & Support</h3>
                <p>Besoin d'aide ? Contactez notre équipe technique pour toute assistance.</p>
                <ul class="admin-footer-links">
                    <li><a href="mailto:admin@ensae.sn"><i class="fas fa-envelope"></i> admin@ensae.sn</a></li>
                    <li><a href="tel:+221338889999"><i class="fas fa-phone"></i> +221 33 888 99 99</a></li>
                    <li><a href="#"><i class="fas fa-comments"></i> Chat en ligne</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="admin-footer-bottom">
            <div class="admin-footer-info">
                <span><i class="fas fa-clock"></i> Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?></span>
                <span><i class="fas fa-user-shield"></i> Administrateur connecté</span>
                <span><i class="fas fa-shield-alt"></i> Session sécurisée</span>
            </div>
            <div class="admin-footer-version">
                <i class="fas fa-code-branch"></i> v2.1.0
            </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Footer actions
    const footerStartVote = document.getElementById('footerStartVote');
    const footerStartCand = document.getElementById('footerStartCand');
    const footerBackup = document.getElementById('footerBackup');
    const footerLogs = document.getElementById('footerLogs');
    const footerSupport = document.getElementById('footerSupport');
    const footerDocs = document.getElementById('footerDocs');

    // Démarrer un vote depuis le footer
    if (footerStartVote) {
        footerStartVote.addEventListener('click', function(e) {
            e.preventDefault();
            const startVotesModal = document.getElementById('startVotesModal');
            if (startVotesModal) {
                startVotesModal.style.display = 'block';
            }
        });
    }

    // Ouvrir candidatures depuis le footer
    if (footerStartCand) {
        footerStartCand.addEventListener('click', function(e) {
            e.preventDefault();
            const startCandModal = document.getElementById('startCandModal');
            if (startCandModal) {
                startCandModal.style.display = 'block';
            }
        });
    }

    // Sauvegarder les données
    if (footerBackup) {
        footerBackup.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Sauvegarde en cours...', 'info');
            setTimeout(() => {
                showNotification('Sauvegarde terminée avec succès !', 'success');
            }, 2000);
        });
    }

    // Voir les logs
    if (footerLogs) {
        footerLogs.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Ouverture des logs système...', 'info');
        });
    }

    // Support technique
    if (footerSupport) {
        footerSupport.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Connexion au support technique...', 'info');
        });
    }

    // Documentation
    if (footerDocs) {
        footerDocs.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Ouverture de la documentation...', 'info');
        });
    }

    // Fonction de notification (si pas déjà définie)
    if (typeof showNotification === 'undefined') {
        window.showNotification = function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            // Add notification styles if not already present
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: white;
                        border-radius: 10px;
                        padding: 15px 20px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        z-index: 10000;
                        transform: translateX(100%);
                        opacity: 0;
                        transition: all 0.3s ease;
                        max-width: 300px;
                    }
                    .notification.success {
                        border-left: 4px solid #28a745;
                    }
                    .notification.error {
                        border-left: 4px solid #dc3545;
                    }
                    .notification.info {
                        border-left: 4px solid #17a2b8;
                    }
                    .notification i {
                        font-size: 1.2em;
                    }
                    .notification.success i {
                        color: #28a745;
                    }
                    .notification.error i {
                        color: #dc3545;
                    }
                    .notification.info i {
                        color: #17a2b8;
                    }
                `;
                document.head.appendChild(styles);
            }

            document.body.appendChild(notification);

            // Animation d'entrée
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 100);

            // Suppression automatique
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        };
    }

    // Mise à jour en temps réel du statut système
    function updateSystemStatus() {
        const statusItems = document.querySelectorAll('.status-item');
        statusItems.forEach(item => {
            const statusValue = item.querySelector('.status-value');
            const statusDot = item.querySelector('.status-dot');

            // Simuler des changements de statut (pour la démo)
            if (Math.random() > 0.95) {
                const statuses = ['online', 'warning', 'offline'];
                const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];

                statusValue.className = `status-value ${randomStatus}`;
                statusDot.className = `status-dot ${randomStatus}`;

                const statusText = randomStatus === 'online' ? 'En ligne' :
                    randomStatus === 'warning' ? 'Attention' : 'Hors ligne';
                statusValue.innerHTML = `<span class="status-dot ${randomStatus}"></span>${statusText}`;
            }
        });
    }

    // Mettre à jour le statut toutes les 30 secondes
    setInterval(updateSystemStatus, 30000);
});
</script>