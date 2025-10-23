<?php
require_once 'Database.php';

class Badge {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnexion();
    }

    // Vérifie et attribue les badges après chaque partie
    public function verifierEtAttribuer($userId, $quizId, $score, $totalQuestions) {
        $badgesAttribues = [];

        // 1️⃣ Premier pas
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM scores WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalParties = $stmt->fetchColumn();
        if ($totalParties == 1) {
            $this->attribuerBadge($userId, 'Premier pas');
            $badgesAttribues[] = 'Premier pas';
        }

        // 2️⃣ Perfectionniste
        if ($score == $totalQuestions) {
            $this->attribuerBadge($userId, 'Perfectionniste');
            $badgesAttribues[] = 'Perfectionniste';
        }

        // 3️⃣ Explorateur
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT questionnaire_id) 
            FROM scores 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $themesJoues = $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM questionnaires");
        $totalThemes = $stmt->fetchColumn();

        if ($themesJoues == $totalThemes) {
            $this->attribuerBadge($userId, 'Explorateur');
            $badgesAttribues[] = 'Explorateur';
        }

        // 4️⃣ Marathon
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM scores 
            WHERE user_id = ? 
              AND DATE(date_jeu) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $partiesAujourdHui = $stmt->fetchColumn();

        if ($partiesAujourdHui >= 10) {
            $this->attribuerBadge($userId, 'Marathon');
            $badgesAttribues[] = 'Marathon';
        }

        return $badgesAttribues;
    }

    // Attribue un badge à un utilisateur si pas déjà attribué
    private function attribuerBadge($userId, $badgeNom) {
        $stmt = $this->pdo->prepare("SELECT id FROM badges WHERE nom = ?");
        $stmt->execute([$badgeNom]);
        $badgeId = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        $stmt->execute([$userId, $badgeId]);
    }

    // Récupère tous les badges d’un utilisateur
    public function getBadgesUtilisateur($userId) {
        $stmt = $this->pdo->prepare("
            SELECT b.nom, b.description, ub.date_obtention 
            FROM user_badges ub
            JOIN badges b ON ub.badge_id = b.id
            WHERE ub.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
