function initHeader() {
  // Activation onglet courant
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function (e) {
      if (this.classList.contains('disabled-link')) {
        e.preventDefault();
        alert("Cette page est désactivée pour le moment.");
        return;
      }
      document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const menuToggle = document.querySelector('.menu-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', function () {
      menuToggle.classList.toggle('active');
      navMenu.classList.toggle('active');
    });
  }

  // Pour que le clic sur .dropdown-toggle ouvre/ferme le menu
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const parent = this.closest('.dropdown');
      parent.classList.toggle('show');
    });
  });
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
  });

  function toggleLink(selector, disabled) {
    const link = document.querySelector(selector);
    if (!link) return;
    if (disabled) {
      link.classList.add('disabled-link');
    } else {
      link.classList.remove('disabled-link');
    }
  }

  function updateNavVisibility() {
    const state = getState();
    const candidatureOn = isCandidatureActive();
    const voteOn = isVoteActive();

    toggleLink('a[href$="candidat.html"]', !candidatureOn);
    toggleLink('a[href$="campagnes.html"]', !candidatureOn);
    toggleLink('a[href$="vote.html"]', !voteOn);

    const categories = ['aes', 'classe', 'club'];
    let canSeeStats = false;
    let showResults = false;
    categories.forEach(cat => {
      const v = cat === 'club' ? state.vote.club : state.vote[cat];
      if (!v) return;
      if (v.active && userHasVoted(cat)) canSeeStats = true;
      if (!v.active && v.endTime && Date.now() >= v.endTime && Date.now() <= v.endTime + 7 * 24 * 60 * 60 * 1000) {
        showResults = true;
      }
    });
    toggleLink('a[href$="statistique.html"]', !canSeeStats);
    toggleLink('a[href$="resultat.html"]', !showResults);
  }

  document.addEventListener('DOMContentLoaded', updateNavVisibility);
  document.addEventListener('stateChanged', updateNavVisibility);

  const profileBtn = document.getElementById('profileBtn');
  const panel = document.getElementById('profilePanel');
  const panelBg = document.getElementById('profilePanelBg');
  const closePanelBtn = document.getElementById('closeProfilePanel');


  function renderProfile() {
      const info = document.getElementById('profilePanelInfo');
      const actions = document.getElementById('panelActions');
      const dateEl = document.getElementById('panelCreationDate');
      if (!info || !actions || !dateEl) return;
  
      const user = JSON.parse(localStorage.getItem('currentUser') || 'null');
      if (!user) {
        info.innerHTML = '<p>Aucun utilisateur connecté.</p>';
        actions.innerHTML = '';
        dateEl.textContent = '';
        return;
      }
      const inscr = user.inscritDepuis ? new Date(user.inscritDepuis).toLocaleDateString() : '';
  
      // Construction du profil dans l'ordre demandé
      info.innerHTML = `
        <div class="profile-block">
          <p class="profile-username"><strong>@</strong>${user.username || ''}</p>
          ${user.photo ? `<img src="${user.photo}" class="profile-photo" alt="photo">` : ''}
          <p class="profile-nom">${user.nom || ''}</p>
          <p class="profile-prenom">${user.prenom || ''}</p>
        </div>
      `;
  
      let html = '';
      const comites = JSON.parse(localStorage.getItem('comites') || '{}');
      const cats = Object.keys(comites).filter(c => (comites[c] || []).some(m => m.email === user.email));
      if (cats.length > 0) {
        html += `<div class="committee-section" style="text-align:center;margin:1.5rem 0;">
          <button class="admin-btn" id="startCandPanel">Ouvrir candidatures</button>
          <button class="admin-btn danger" id="stopCandPanel">Fermer candidatures</button>
          <button class="admin-btn" id="startVotePanel">Ouvrir votes</button>
          <button class="admin-btn danger" id="stopVotePanel">Fermer votes</button>
        </div>`;
      }
      actions.innerHTML = html;
  
      // Ajout des boutons en bas de la barre
      actions.innerHTML += `
        <div class="profile-actions-bottom">
          <button class="admin-btn" id="editProfileBtn">Modifier mes infos</button>
          <button class="admin-btn" id="changePwdBtn">Changer mon mot de passe</button>
        </div>
      `;
  
      dateEl.innerHTML = inscr ? `Inscrit depuis : ${inscr}` : '';
      
      // Gestion des boutons de comité
      if (cats.length > 0) {
        document.getElementById('startCandPanel').onclick = () => {
          if (window.resetCandModal) window.resetCandModal();
          const candType = document.getElementById('candType');
          const candStep1 = document.getElementById('candStep1');
          const candStep2 = document.getElementById('candStep2');
          if (cats.length === 1 && candType && candStep1 && candStep2) {
            candType.value = cats[0];
            candStep1.style.display = 'none';
            candStep2.style.display = 'block';
          } else if (candStep1 && candStep2) {
            candStep1.style.display = 'block';
            candStep2.style.display = 'none';
          }
          const modal = document.getElementById('startCandModal');
          if (modal) {
            closePanel();
            modal.style.display = 'flex';
          }
        };
      
        document.getElementById('stopCandPanel').onclick = () => {
          if (window.openCloseSession) {
            closePanel();
            window.openCloseSession('candidature');
          }
        };
      
        document.getElementById('startVotePanel').onclick = () => {
          if (window.resetVoteModal) window.resetVoteModal();
          const voteType = document.getElementById('voteType');
          const step1 = document.getElementById('step1');
          const step2 = document.getElementById('step2');
          if (cats.length === 1 && voteType && step1 && step2) {
            voteType.value = cats[0];
            step1.style.display = 'none';
            step2.style.display = 'block';
          } else if (step1 && step2) {
            step1.style.display = 'block';
            step2.style.display = 'none';
          }
          const modal = document.getElementById('startVotesModal');
          if (modal) {
            closePanel();
            modal.style.display = 'flex';
          }
        };
      
        document.getElementById('stopVotePanel').onclick = () => {
          if (window.openCloseSession) {
            closePanel();
            window.openCloseSession('vote');
          }
        };
      }
      
  
      // Ajoute ici les gestionnaires pour les boutons "Modifier mes infos" et "Changer mon mot de passe"
      document.getElementById('editProfileBtn').onclick = () => {
        // Ouvre le formulaire de modification du profil
        alert('Fonctionnalité à implémenter : Modifier mes infos');
      };
      document.getElementById('changePwdBtn').onclick = () => {
        // Ouvre le formulaire de changement de mot de passe
        alert('Fonctionnalité à implémenter : Changer mon mot de passe');
      };
  }

  function openPanel() {
    renderProfile();
    if (panel) panel.classList.add('open');
    if (panelBg) panelBg.classList.add('open');
  }
  function closePanel() {
    if (panel) panel.classList.remove('open');
    if (panelBg) panelBg.classList.remove('open');
  }

  if (profileBtn) profileBtn.addEventListener('click', e => { e.preventDefault(); openPanel(); });
  if (closePanelBtn) closePanelBtn.addEventListener('click', closePanel);
  if (panelBg) panelBg.addEventListener('click', closePanel);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHeader);
} else {
  initHeader();
}
