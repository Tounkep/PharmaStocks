<?php

class AlerteController
{
    private Alerte $alerte;
    private Stock $stock;

    public function __construct(Alerte $alerte, Stock $stock)
    {
        $this->alerte = $alerte;
        $this->stock = $stock;
    }


    /* =====================================================
       LISTE
    ====================================================== */

    public function index(): void
    {
        $this->stock->synchroniserStatutsLots();
        $this->alerte->synchroniser();

        $statut = $this->param('statut');
        $type = $this->param('type');

        $this->json([
            'success' => true,
            'data' => [
                'alertes' => $this->alerte->getAll($statut, $type),
                'compteurs' => $this->alerte->compterNouvelles()
            ]
        ]);
    }


    /* =====================================================
       CHANGEMENT DE STATUT
    ====================================================== */

    public function changerStatut(int $utilisateurId): void
    {
        $data = $this->readJson();

        $id = (int)($data['id'] ?? 0);
        $statut = strtoupper(trim((string)($data['statut'] ?? '')));

        if ($id <= 0) {
            $this->error('Identifiant d\'alerte invalide.', 400);
            return;
        }

        $this->alerte->changerStatut($id, $statut, $utilisateurId);

        $this->json([
            'success' => true,
            'message' => 'Alerte mise à jour.'
        ]);
    }


    /* =====================================================
       OUTILS
    ====================================================== */

    private function param(string $name): ?string
    {
        $value = strtoupper(trim((string)($_GET[$name] ?? '')));

        return $value !== '' ? $value : null;
    }


    private function readJson(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Données JSON invalides.');
        }

        return $data;
    }


    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }


    private function error(string $message, int $status): void
    {
        $this->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
