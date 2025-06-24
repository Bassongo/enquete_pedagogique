<style>
  @import "../assets/css/header.css";
</style>


<header>
  <div class="header-container">
    <!-- Logo -->
    <div class="logo">
      <img src="../assets/img/logo_ensae.png" alt="Logo ENSAE" class="logo-img">
      <div class="logo-text">
        <h1>E-election</h1>
        <h2>ENSAE Dakar</h2>
      </div>
    </div>

    <!-- Menu Toggle for Mobile -->
    <button class="menu-toggle" aria-label="Ouvrir le menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Navigation -->
    <nav class="main-nav">
      <ul class="nav-menu">

        <li><a href="index.php" class="nav-link"><span>Accueil</span></a></li>




        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle"><span>Elections ▼</span></a>
          <ul class="dropdown-menu">
            <li><a href="campagnes.php" class="nav-link"><span>Campagne</span></a></li>
            <li><a href="vote.php" class="nav-link"><span>Voter</span></a></li>
          </ul>
        </li>


        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle"><span>Candidatures ▼</span></a>
          <ul class="dropdown-menu">
            <li><a href="candidat.php" class="nav-link"><span>Candidater</span></a></li>
            <li><a href="mes-candidatures.php" class="nav-link"><span>Mes candidatures</span></a></li>
          </ul>
        </li>



        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle"><span>Bilan ▼</span></a>
          <ul class="dropdown-menu">
            <li><a href="statistique.php" class="nav-link"><span>Statistiques</span></a></li>
            <li><a href="resultat.php" class="nav-link"><span>Résultats</span></a></li>
          </ul>
        </li>

        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle"><span>Profil ▼</span></a>
          <ul class="dropdown-menu">
            <li><a href="me.php" id="profileBtn" class="nav-link"><span>Moi</span></a></li>
            <li><a href="../logout.php" class="nav-link"><span>Déconnexion</span></a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </div>

  <div id="profilePanelBg" class="side-panel-bg"></div>
  <aside id="profilePanel" class="side-panel">
    <button class="close-panel" id="closeProfilePanel">&times;</button>
    <div id="profilePanelInfo" class="profile-info"></div>
    <div id="panelActions"></div>
    <div id="panelCreationDate" class="creation-date"></div>
  </aside>
</header>


<script src="../assets/js/state.js"></script>
<script src="../assets/js/modal.js"></script>
<script src="../assets/js/header.js"></script>
<script src="../assets/js/include.js"></script>

<div id="modals"></div>
<script>
  includeComponent('#modals', '../components/modals.php');
</script>