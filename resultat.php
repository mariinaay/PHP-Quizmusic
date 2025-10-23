<?php
/**
 * QuizMusic - Page de résultats
 * Affiche le score, le niveau et les badges débloqués
 */

session_start();

// 🔐 Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 🔐 Vérification qu'un quiz a été joué
if (!isset($_SESSION['dernier_score']) || !isset($_SESSION['total_questions'])) {
    header('Location: index.php');
    exit;
}

// 📌 Récupération des données du quiz
$score = $_SESSION['dernier_score'];
$total = $_SESSION['total_questions'];
$theme = $_SESSION['dernier_theme'];
$pseudo = $_SESSION['user_pseudo'];

// 🔹 Pourcentage de réussite
$pourcentage = $total > 0 ? ($score / $total) * 100 : 0;

// 🔹 Détermination du niveau selon le score
$niveaux = [
    ['min' => 0, 'max' => 2, 'titre' => '🔇 Débutant', 'badge' => 'Mélomane en herbe', 'couleur' => 'from-gray-400 to-gray-600', 'message' => 'C\'est un début ! La musique n\'a pas encore de secrets pour vous.'],
    ['min' => 3, 'max' => 4, 'titre' => '🎵 Amateur', 'badge' => 'Auditeur curieux', 'couleur' => 'from-blue-400 to-blue-600', 'message' => 'Pas mal ! Vous commencez à reconnaître quelques classiques.'],
    ['min' => 5, 'max' => 6, 'titre' => '🎶 Confirmé', 'badge' => 'Mélomane averti', 'couleur' => 'from-purple-400 to-purple-600', 'message' => 'Bravo ! Vous avez de bonnes bases musicales.'],
    ['min' => 7, 'max' => 8, 'titre' => '🎸 Expert', 'badge' => 'Connaisseur', 'couleur' => 'from-orange-400 to-orange-600', 'message' => 'Impressionnant ! Peu de choses vous échappent.'],
    ['min' => 9, 'max' => 10, 'titre' => '🏆 Maître', 'badge' => 'Virtuose musical', 'couleur' => 'from-yellow-400 to-yellow-600', 'message' => 'Exceptionnel ! Vous êtes un véritable expert !']
];

// 🔹 Trouver le niveau actuel
$niveauActuel = null;
foreach ($niveaux as $niveau) {
    if ($score >= $niveau['min'] && $score <= $niveau['max']) {
        $niveauActuel = $niveau;
        break;
    }
}

// 🔹 Charger infos thème depuis la BDD
require_once 'classes/Database.php';
$pdo = Database::getConnexion();
$stmt = $pdo->prepare("SELECT * FROM questionnaires WHERE code = ?");
$stmt->execute([$theme]);
$themeInfo = $stmt->fetch();

// Protection si thème non trouvé
if (!$themeInfo) {
    $themeInfo = [
        'titre' => 'Quiz inconnu',
        'emoji' => '❓',
        'couleur' => 'from-gray-500 to-gray-700'
    ];
}

// 🔹 Récupérer les badges débloqués lors de ce quiz
$badgesDebloques = $_SESSION['badgesDebloques'] ?? [];
unset($_SESSION['badgesDebloques']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - QuizMusic 🎵</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInScale { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        @keyframes bounceIn { 0% { transform: scale(0); opacity: 0; } 50% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
        .animate-fade-scale { animation: fadeInScale 0.8s ease-out; }
        .animate-bounce-in { animation: bounceIn 1s ease-out; }
        @keyframes progressBar { from { width: 0%; } to { width: var(--progress-width); } }
        .progress-animated { animation: progressBar 2s ease-out forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen">
<div class="container mx-auto px-4 py-8 max-w-4xl">

    <!-- Carte de résultat principale -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden animate-fade-scale">

        <!-- En-tête avec gradient du thème -->
        <div class="bg-gradient-to-r <?php echo $themeInfo['couleur']; ?> p-8 text-white text-center">
            <div class="text-6xl mb-4"><?php echo $themeInfo['emoji']; ?></div>
            <h1 class="text-3xl font-bold mb-2">Quiz <?php echo htmlspecialchars($themeInfo['titre']); ?></h1>
            <p class="text-white/80">Résultats de <?php echo htmlspecialchars($pseudo); ?></p>
        </div>

        <!-- Score principal -->
        <div class="p-8 text-center">

            <!-- Badge du niveau avec animation -->
            <div class="inline-block animate-bounce-in mb-6">
                <div class="bg-gradient-to-r <?php echo $niveauActuel['couleur']; ?> text-white px-8 py-4 rounded-2xl shadow-xl">
                    <div class="text-5xl mb-2"><?php echo $niveauActuel['titre']; ?></div>
                    <div class="text-xl font-semibold"><?php echo $niveauActuel['badge']; ?></div>
                </div>
            </div>

            <!-- Score numérique -->
            <div class="text-7xl font-bold text-gray-800 mb-4">
                <?php echo $score; ?> / <?php echo $total; ?>
            </div>

            <!-- Pourcentage -->
            <div class="text-2xl text-gray-600 mb-6">
                <?php echo round($pourcentage); ?>% de réussite
            </div>

            <!-- Barre de progression visuelle -->
            <div class="max-w-md mx-auto mb-8">
                <div class="bg-gray-200 rounded-full h-6 overflow-hidden">
                    <div class="progress-animated h-full bg-gradient-to-r <?php echo $niveauActuel['couleur']; ?> rounded-full flex items-center justify-end pr-3"
                         style="--progress-width: <?php echo round($pourcentage); ?>%;">
                        <span class="text-white text-sm font-bold"><?php echo round($pourcentage); ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Message personnalisé -->
            <div class="bg-purple-50 border-2 border-purple-200 rounded-2xl p-6 mb-6">
                <p class="text-lg text-gray-700 leading-relaxed">
                    <?php echo $niveauActuel['message']; ?>
                </p>
            </div>

            <!-- 🔹 Affichage des badges débloqués -->
            <?php if (!empty($badgesDebloques)): ?>
                <div class="mb-6 bg-green-100 text-green-800 p-4 rounded-xl text-center">
                    🎉 Félicitations ! Vous avez débloqué les badges suivants :<br>
                    <?php foreach ($badgesDebloques as $b): ?>
                        <span class="bg-yellow-400 text-black px-3 py-1 rounded-lg mr-2 inline-block"><?php echo htmlspecialchars($b); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Boutons d'action -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="quiz.php?theme=<?php echo urlencode($theme); ?>"
                   class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                    🔄 Rejouer
                </a>
                <a href="historique.php"
                   class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                    📊 Mon historique
                </a>
                <a href="index.php"
                   class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg">
                    🏠 Accueil
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if ($score >= 7): ?>
    // Confetti si score >= 7
    function createConfetti() {
        const confetti = document.createElement('div');
        confetti.style.position = 'fixed';
        confetti.style.width = '10px';
        confetti.style.height = '10px';
        confetti.style.backgroundColor = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'][Math.floor(Math.random() * 5)];
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.top = '-10px';
        confetti.style.borderRadius = '50%';
        confetti.style.zIndex = '9999';
        document.body.appendChild(confetti);

        let pos = -10;
        const fall = setInterval(() => {
            if (pos >= window.innerHeight) {
                clearInterval(fall);
                confetti.remove();
            } else {
                pos += 5;
                confetti.style.top = pos + 'px';
            }
        }, 50);
    }

    for (let i = 0; i < 50; i++) {
        setTimeout(createConfetti, i * 100);
    }
    <?php endif; ?>
</script>
</body>
</html>
