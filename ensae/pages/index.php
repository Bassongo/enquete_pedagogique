<?php
include '../config/database.php';

// Protection de la page - redirection si non connecté
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/acceuils.css">

</head>

<body>

    <?php include ('../components/header.php'); ?>


    <div class="carousel-container">
        <div class="carousel-slides">
            <div class="carousel-slide">
                <img src="../assets/img/slide1.png" alt="Vote électronique">
                <div class="carousel-text">Vote électronique facile</div>
            </div>
            <div class="carousel-slide">
                <img src="../assets/img/slide2.png" alt="Sécurité du vote">
                <div class="carousel-text">Sécurité garantie</div>
            </div>
            <div class="carousel-slide">
                <img src="../assets/img/slide3.png" alt="Transparence">
                <div class="carousel-text">Transparence assurée</div>
            </div>
        </div>
        <button class="carousel-btn prev">&#10094;</button>
        <button class="carousel-btn next">&#10095;</button>
    </div>

    <div class="container">

        <div id="accueil">

            <div class="content">
                <div class="section">
                    <h3>🎯 Objectifs</h3>
                    <ul>
                        <li>Inscription en ligne des candidats</li>
                        <li>Gestion des électeurs</li>
                        <li>Votes anonymes et sécurisés</li>
                        <li>Résultats en temps réel</li>
                    </ul>
                </div>

                <div class="section">
                    <h3>🗳️ Processus électoral</h3>
                    <ul>
                        <li>Phase d'inscription</li>
                        <li>Campagne électorale</li>
                        <li>Vote en ligne</li>
                        <li>Publication des résultats</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>




    <!-- Footer dynamique -->
    <?php include ('../components/footer.php'); ?>
    <!-- Inclusion dynamique du header/footer -->
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/carousel.js"></script>



</body>

</html>