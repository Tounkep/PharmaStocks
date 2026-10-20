<?php

class EmployeeController
{
    private Employee $employee;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function index(): void
    {
        $role = strtoupper((string) ($_SESSION['role'] ?? ''));
        if (!isset($_SESSION['utilisateur_id']) || !in_array($role, ['EMPLOYE', 'PHARMACIEN', 'ADMIN'], true)) {
            http_response_code(401);
            $this->json(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
            return;
        }

        $userId = (int) $_SESSION['utilisateur_id'];
        $user = [
            'id' => $userId,
            'nom' => (string) ($_SESSION['nom'] ?? ''),
            'prenom' => (string) ($_SESSION['prenom'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? ''),
            'role' => (string) ($_SESSION['role'] ?? 'EMPLOYE'),
        ];

        try {
            $this->json([
                'success' => true,
                'data' => [
                    'utilisateur' => $user,
                    'ventesRecentes' => $this->employee->getRecentSales($userId),
                    'informationsRapides' => $this->employee->getQuickInfo(),
                    'notifications' => $this->employee->getNotifications(),
                ],
            ]);
        } catch (Throwable $e) {
            error_log('EmployeeController: ' . $e->getMessage());
            http_response_code(500);
            $this->json(['success' => false, 'message' => 'Impossible de charger les données du dashboard. Vérifiez la structure de la base.']);
        }
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
