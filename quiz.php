<?php
session_start();

// 🔐 Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 🔧 Chargement des classes avec chemin correct
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Question.php';
require_once __DIR__ . '/classes/QuestionTexte.php';
require_once __DIR__ . '/classes/QuestionImage.php';
require_once __DIR__ . '/classes/QuestionAudio.php';
require_once __DIR__ . '/classes/Quiz.php';
require_once __DIR__ . '/classes/Badge.php';

$theme = $_GET['theme'] ?? '';
if (empty($theme)) {
    header('Location: index.php');
    exit;
}

try {
    // Instanciation du quiz
    $quiz = new Quiz($theme);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reponsesUtilisateur = $_POST['reponses'] ?? [];
        $tempsTotal = $_POST['temps_total'] ?? null;

        // Calcul du score
        $score = $quiz->calculerScore($reponsesUtilisateur);

        // Sauvegarde en BDD
        $quiz->sauvegarderScore($_SESSION['user_id'], $score, $tempsTotal);

        // 🔹 Attribution des badges
        $badge = new Badge();
        $badgesDebloques = $badge->verifierEtAttribuer(
            $_SESSION['user_id'], // ID utilisateur
            $theme,               // On utilise $theme comme identifiant
            $score,
            count($quiz->getQuestions())
        );

        $_SESSION['badgesDebloques'] = $badgesDebloques;
        $_SESSION['dernier_score'] = $score;
        $_SESSION['dernier_theme'] = $theme;
        $_SESSION['total_questions'] = count($quiz->getQuestions());

        header('Location: resultat.php');
        exit;
    }

    $themeInfo = $quiz->getThemeInfo();
    $questions = $quiz->getQuestions();

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz <?php echo htmlspecialchars($themeInfo['titre']); ?> - QuizMusic 🎵</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen">
<div class="container mx-auto px-4 py-8 max-w-4xl">

<header class="text-center mb-8">
    <a href="index.php" class="inline-block text-purple-300 hover:text-white transition-colors mb-4">
        ← Retour à l'accueil
    </a>

    <div class="bg-gradient-to-r <?php echo $themeInfo['couleur']; ?> rounded-2xl p-6 text-white mb-8">
        <div class="text-4xl mb-2"><?php echo $themeInfo['emoji']; ?></div>
        <h1 class="text-3xl font-bold mb-2">Quiz <?php echo htmlspecialchars($themeInfo['titre']); ?></h1>
        <p class="text-white/80">Bonjour <?php echo htmlspecialchars($_SESSION['user_pseudo']); ?> ! Répondez aux questions suivantes</p>
    </div>
</header>

<main>
<form method="POST" class="space-y-8">
    <input type="hidden" name="temps_total" id="temps_total" value="0">

    <?php foreach ($questions as $index => $question): ?>
        <?php echo $question->afficherHTML($index); ?>
    <?php endforeach; ?>

    <div class="text-center">
        <button type="submit"
                class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-4 px-8 rounded-2xl text-lg shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-200">
            🏆 Voir mes résultats !
        </button>
    </div>
</form>
</main>
</div>

<script>
// Chronomètre pour mesurer le temps de réponse
let tempsDebut = Date.now();
document.querySelector('form').addEventListener('submit', function() {
    let tempsTotal = Math.round((Date.now() - tempsDebut) / 1000);
    document.getElementById('temps_total').value = tempsTotal;
});
</script>
</body>
</html>
