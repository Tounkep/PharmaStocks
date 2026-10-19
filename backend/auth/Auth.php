<?php


/*
|--------------------------------------------------------------------------
| Contrôle d'accès commun aux routes de l'API
|--------------------------------------------------------------------------
|
| Utilisation dans une route :
|
|   require_once __DIR__ . '/../auth/Auth.php';
|
|   Auth::requireRole(['ADMIN', 'PHARMACIEN']);
|
| En cas d'échec, une réponse JSON 401 ou 403 est envoyée
| et le script s'arrête.
*/

class Auth
{
    public const ADMIN = 'ADMIN';
    public const PHARMACIEN = 'PHARMACIEN';
    public const EMPLOYE = 'EMPLOYE';

    /* =========================================
       SESSION
    ========================================= */

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /* =========================================
       UTILISATEUR CONNECTÉ
    ========================================= */

    public static function user(): ?array
    {
        self::startSession();

        if (!isset($_SESSION['utilisateur_id'])) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['utilisateur_id'],
            'nom' => $_SESSION['nom'] ?? '',
            'prenom' => $_SESSION['prenom'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => strtoupper($_SESSION['role'] ?? '')
        ];
    }

    public static function userId(): ?int
    {
        $user = self::user();

        return $user['id'] ?? null;
    }

    /* =========================================
       CONTRÔLE D'ACCÈS
    ========================================= */

    public static function requireLogin(): array
    {
        $user = self::user();

        if ($user === null) {
            self::deny(401, 'Utilisateur non connecté.');
        }

        return $user;
    }

    public static function requireRole(array $roles): array
    {
        $user = self::requireLogin();

        $roles = array_map(
            static fn($role) => strtoupper($role),
            $roles
        );

        if (!in_array($user['role'], $roles, true)) {
            self::deny(403, 'Accès non autorisé pour votre rôle.');
        }

        return $user;
    }

    public static function hasRole(array $roles): bool
    {
        $user = self::user();

        if ($user === null) {
            return false;
        }

        $roles = array_map(
            static fn($role) => strtoupper($role),
            $roles
        );

        return in_array($user['role'], $roles, true);
    }

    /* =========================================
       DÉCONNEXION
    ========================================= */

    public static function logout(): void
    {
        self::startSession();

        // Effacer les données de session
        $_SESSION = [];

        // Supprimer le cookie de session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Détruire la session côté serveur
        session_destroy();

        // Empêcher la réutilisation d'une page en cache
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Retour à la connexion
        header(
            'Location: /ProjetsWebD/PharmaStocks/frontend/pages/connexion.html'
        );
        exit;
    }

    /* =========================================
       ERREUR JSON POUR LES ROUTES API
    ========================================= */

    private static function deny(
        int $status,
        string $message
    ): void {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
