<style>
/* Header Admin Styles */
.admin-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
  border-bottom: 1px solid rgba(102, 126, 234, 0.1);
}

.admin-header-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 70px;
}

/* Logo Section */
.admin-logo {
  display: flex;
  align-items: center;
  gap: 15px;
  text-decoration: none;
  color: inherit;
}

.admin-logo-img {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
}

.admin-logo-text h1 {
  font-size: 1.5em;
  font-weight: 700;
  color: #333;
  margin: 0;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.admin-logo-text h2 {
  font-size: 0.8em;
  font-weight: 500;
  color: #666;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Admin Info */
.admin-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.admin-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 25px;
  color: white;
  font-weight: 500;
  box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.admin-user i {
  font-size: 1.1em;
}

.admin-notifications {
  position: relative;
  padding: 8px;
  border-radius: 50%;
  background: #f8f9ff;
  border: 2px solid #e8eaff;
  cursor: pointer;
  transition: all 0.3s ease;
}

.admin-notifications:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

.admin-notifications .notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 0.7em;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

/* Burger Menu */
.burger-menu {
  display: none;
  flex-direction: column;
  cursor: pointer;
  padding: 5px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.burger-menu:hover {
  background: rgba(102, 126, 234, 0.1);
}

.burger-line {
  width: 25px;
  height: 3px;
  background: #333;
  margin: 3px 0;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.burger-menu.active .burger-line:nth-child(1) {
  transform: rotate(45deg) translate(5px, 5px);
}

.burger-menu.active .burger-line:nth-child(2) {
  opacity: 0;
}

.burger-menu.active .burger-line:nth-child(3) {
  transform: rotate(-45deg) translate(7px, -6px);
}

/* Mobile Menu */
.mobile-menu {
  position: fixed;
  top: 70px;
  left: 0;
  width: 100%;
  height: calc(100vh - 70px);
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  transform: translateX(-100%);
  transition: transform 0.3s ease;
  z-index: 999;
  overflow-y: auto;
}

.mobile-menu.active {
  transform: translateX(0);
}

.mobile-menu-content {
  padding: 30px 20px;
}

.mobile-menu-section {
  margin-bottom: 30px;
}

.mobile-menu-section h3 {
  color: #333;
  font-size: 1.1em;
  font-weight: 600;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e8eaff;
  display: flex;
  align-items: center;
  gap: 10px;
}

.mobile-menu-section h3 i {
  color: #667eea;
}

.mobile-menu-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px 20px;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #fff 0%, #f8f9ff 100%);
  border-radius: 12px;
  border: 1px solid #e8eaff;
  text-decoration: none;
  color: #333;
  font-weight: 500;
  transition: all 0.3s ease;
}

.mobile-menu-item:hover {
  transform: translateX(5px);
  border-color: #667eea;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.mobile-menu-item i {
  font-size: 1.2em;
  color: #667eea;
  width: 20px;
  text-align: center;
}

.mobile-menu-item.logout {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
  border-color: #e74c3c;
}

.mobile-menu-item.logout i {
  color: white;
}

.mobile-menu-item.logout:hover {
  background: linear-gradient(135deg, #c0392b, #a93226);
}

/* Responsive Design */
@media (max-width: 768px) {
  .admin-header-container {
    padding: 0 15px;
    height: 60px;
  }
  
  .admin-logo-img {
    width: 35px;
    height: 35px;
  }
  
  .admin-logo-text h1 {
    font-size: 1.2em;
  }
  
  .admin-logo-text h2 {
    font-size: 0.7em;
  }
  
  .admin-info {
    gap: 10px;
  }
  
  .admin-user {
    padding: 6px 12px;
    font-size: 0.9em;
  }
  
  .admin-user span {
    display: none;
  }
  
  .burger-menu {
    display: flex;
  }
}

@media (max-width: 480px) {
  .admin-logo-text h2 {
    display: none;
  }
  
  .admin-notifications {
    padding: 6px;
  }
  
  .admin-notifications i {
    font-size: 0.9em;
  }
}

/* Overlay pour fermer le menu */
.mobile-menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 998;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.mobile-menu-overlay.active {
  opacity: 1;
  visibility: visible;
}
</style>

<header class="admin-header">
  <div class="admin-header-container">
    <!-- Logo -->
    <a href="../admin/dashboard.php" class="admin-logo">
      <img src="../assets/img/logo_ensae.png" alt="Logo ENSAE" class="admin-logo-img">
      <div class="admin-logo-text">
        <h1>E-election</h1>
        <h2>Administration</h2>
      </div>
    </a>

    <!-- Admin Info -->
    <div class="admin-info">
      <!-- Notifications -->
      <div class="admin-notifications" id="adminNotifications">
        <i class="fas fa-bell"></i>
        <span class="notification-badge">3</span>
      </div>
      
      <!-- User Info -->
      <div class="admin-user">
        <i class="fas fa-user-shield"></i>
        <span>Administrateur</span>
      </div>
      
      <!-- Burger Menu -->
      <div class="burger-menu" id="burgerMenu">
        <div class="burger-line"></div>
        <div class="burger-line"></div>
        <div class="burger-line"></div>
      </div>
    </div>
  </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
  <div class="mobile-menu-content">
    <!-- Dashboard Section -->
    <div class="mobile-menu-section">
      <h3><i class="fas fa-tachometer-alt"></i> Dashboard</h3>
      <a href="../admin/dashboard.php" class="mobile-menu-item">
        <i class="fas fa-home"></i>
        <span>Accueil Dashboard</span>
      </a>
    </div>
    
    <!-- Elections Section -->
    <div class="mobile-menu-section">
      <h3><i class="fas fa-vote-yea"></i> Élections</h3>
      <a href="#" class="mobile-menu-item" id="mobileStartVote">
        <i class="fas fa-play-circle"></i>
        <span>Démarrer un Vote</span>
      </a>
      <a href="#" class="mobile-menu-item" id="mobileStartCand">
        <i class="fas fa-user-plus"></i>
        <span>Ouvrir Candidatures</span>
      </a>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-list"></i>
        <span>Gérer les Élections</span>
      </a>
    </div>
    
    <!-- Management Section -->
    <div class="mobile-menu-section">
      <h3><i class="fas fa-cogs"></i> Gestion</h3>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-users"></i>
        <span>Gérer les Candidats</span>
      </a>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-user-tie"></i>
        <span>Gérer les Comités</span>
      </a>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-chart-bar"></i>
        <span>Statistiques</span>
      </a>
    </div>
    
    <!-- Settings Section -->
    <div class="mobile-menu-section">
      <h3><i class="fas fa-wrench"></i> Paramètres</h3>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-cog"></i>
        <span>Paramètres Système</span>
      </a>
      <a href="#" class="mobile-menu-item">
        <i class="fas fa-user-cog"></i>
        <span>Profil Admin</span>
      </a>
    </div>
    
    <!-- Logout Section -->
    <div class="mobile-menu-section">
      <a href="../logout.php" class="mobile-menu-item logout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Se Déconnecter</span>
      </a>
    </div>
  </div>
</div>

<!-- Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.getElementById('burgerMenu');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const adminNotifications = document.getElementById('adminNotifications');
    
    // Toggle mobile menu
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        mobileMenu.classList.toggle('active');
        mobileMenuOverlay.classList.toggle('active');
        document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
    });
    
    // Close menu when clicking overlay
    mobileMenuOverlay.addEventListener('click', function() {
        burgerMenu.classList.remove('active');
        mobileMenu.classList.remove('active');
        this.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Close menu when clicking menu items
    const mobileMenuItems = document.querySelectorAll('.mobile-menu-item');
    mobileMenuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!this.classList.contains('logout')) {
                burgerMenu.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Notifications click
    adminNotifications.addEventListener('click', function() {
        showNotification('3 nouvelles notifications', 'info');
    });
    
    // Mobile menu actions
    const mobileStartVote = document.getElementById('mobileStartVote');
    const mobileStartCand = document.getElementById('mobileStartCand');
    
    if (mobileStartVote) {
        mobileStartVote.addEventListener('click', function() {
            const startVotesModal = document.getElementById('startVotesModal');
            if (startVotesModal) {
                startVotesModal.style.display = 'block';
            }
        });
    }
    
    if (mobileStartCand) {
        mobileStartCand.addEventListener('click', function() {
            const startCandModal = document.getElementById('startCandModal');
            if (startCandModal) {
                startCandModal.style.display = 'block';
            }
        });
    }
    
    // Show notification function
    function showNotification(message, type = 'info') {
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
                    top: 80px;
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
    }
});
</script> 