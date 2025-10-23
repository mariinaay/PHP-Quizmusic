<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Badge.php';

// 🔐 Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Connexion à la base
$pdo = Database::getConnexion();
$userId = $_SESSION['user_id'];

// 📌 Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT pseudo, email, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 📌 Nombre total de parties jouées
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_parties FROM scores WHERE user_id = ?");
$stmt->execute([$userId]);
$totalParties = $stmt->fetchColumn();

// 📌 Thème préféré (le plus joué)
$stmt = $pdo->prepare("
    SELECT q.titre AS theme_prefere, COUNT(*) AS nb 
    FROM scores h
    JOIN questionnaires q ON h.questionnaire_id = q.id
    WHERE h.user_id = ?
    GROUP BY h.questionnaire_id
    ORDER BY nb DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$themePrefereResult = $stmt->fetchColumn();
$themePrefere = $themePrefereResult ?: 'Aucun';

// 🔧 Gestion modification du pseudo
$messageSucces = '';
if (isset($_POST['pseudo'])) {
    $nouveauPseudo = trim($_POST['pseudo']);
    if ($nouveauPseudo !== '') {
        $stmt = $pdo->prepare("UPDATE users SET pseudo = ? WHERE id = ?");
        $stmt->execute([$nouveauPseudo, $userId]);
        $_SESSION['user_pseudo'] = $nouveauPseudo;
        $messageSucces = "Pseudo mis à jour avec succès !";
        $user['pseudo'] = $nouveauPseudo;
    }
}

// 📌 Récupérer les badges de l'utilisateur
$badge = new Badge();
$badges = $badge->getBadgesUtilisateur($userId);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['pseudo']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen text-white">
    <div class="container mx-auto px-4 py-8 max-w-3xl">

        <h1 class="text-4xl font-bold mb-6 text-center">👤 Mon profil</h1>

        <?php if ($messageSucces): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded-xl mb-6 text-center">
                <?php echo htmlspecialchars($messageSucces); ?>
            </div>
        <?php endif; ?>

        <!-- Informations utilisateur -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-6">
            <p><strong>Pseudo :</strong> <?php echo htmlspecialchars($user['pseudo']); ?></p>
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Date d'inscription :</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
            <p><strong>Total de parties jouées :</strong> <?php echo $totalParties; ?></p>
            <p><strong>Thème préféré :</strong> <?php echo htmlspecialchars($themePrefere); ?></p>
        </div>

        <!-- Affichage des badges -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">🎖️ Mes badges</h2>
            <?php if ($badges): ?>
                <ul class="flex gap-4 flex-wrap">
                    <?php foreach ($badges as $b): ?>
                        <li class="bg-yellow-400 text-black px-3 py-1 rounded-lg">
                            <?php echo htmlspecialchars($b['nom']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Aucun badge débloqué pour l'instant 😔</p>
            <?php endif; ?>
        </div>

        <!-- Formulaire pour modifier le pseudo -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
            <h2 class="text-2xl font-semibold mb-4">Modifier mon pseudo</h2>
            <form method="POST" class="flex flex-col gap-4">
                <input type="text" name="pseudo" placeholder="Nouveau pseudo" 
                       class="p-3 rounded-lg text-black" required>
                <button type="submit" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                    💾 Enregistrer
                </button>
            </form>
        </div>

        <!-- Lien retour -->
        <div class="text-center mt-6">
            <a href="index.php" class="underline text-purple-200 hover:text-white">⬅️ Retour à l'accueil</a>
        </div>

    </div>
</body>

</html>
