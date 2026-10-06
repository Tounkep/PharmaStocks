<?php

class StockController
{
    private Stock $stock;

    public function __construct(Stock $stock)
    {
        $this->stock = $stock;
    }


    /* =====================================================
       GET
    ====================================================== */

    public function index(): void
    {
        $this->stock->synchroniserStatutsLots();

        $this->json([
            'success' => true,
            'data' => [
                'resume' => $this->stock->getResume(),
                'lots' => $this->stock->getLots()
            ]
        ]);
    }


    public function mouvements(): void
    {
        $type = strtoupper(trim((string)($_GET['type'] ?? '')));

        $this->json([
            'success' => true,
            'data' => [
                'mouvements' => $this->stock->getMouvements(
                    $type !== '' ? $type : null,
                    $this->param('dateDebut'),
                    $this->param('dateFin')
                )
            ]
        ]);
    }


    public function produits(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                'produits' => $this->stock->getProduits()
            ]
        ]);
    }


    public function commandes(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                'commandes' => $this->stock->getCommandesAReceptionner()
            ]
        ]);
    }


    /* =====================================================
       POST
    ====================================================== */

    public function executer(int $utilisateurId): void
    {
        $data = $this->readJson();

        $action = $data['action'] ?? '';

        switch ($action) {

            case 'creerLot':

                $lotId = $this->stock->creerLot($data, $utilisateurId);

                $this->json([
                    'success' => true,
                    'message' => 'Lot ajouté au stock.',
                    'data' => ['id' => $lotId]
                ], 201);

                break;


            case 'mouvement':

                $resultat = $this->stock->enregistrerMouvementLot(
                    (int)($data['lot_id'] ?? 0),
                    (string)($data['type'] ?? ''),
                    (int)($data['quantite'] ?? -1),
                    $data['motif'] ?? null,
                    $utilisateurId
                );

                $this->json([
                    'success' => true,
                    'message' => 'Mouvement de stock enregistré.',
                    'data' => $resultat
                ]);

                break;


            case 'retirerLot':

                $resultat = $this->stock->retirerLot(
                    (int)($data['lot_id'] ?? 0),
                    $utilisateurId
                );

                $this->json([
                    'success' => true,
                    'message' => 'Lot retiré du stock.',
                    'data' => $resultat
                ]);

                break;


            case 'receptionnerCommande':

                $resultat = $this->stock->receptionnerCommande(
                    (int)($data['commande_id'] ?? 0),
                    is_array($data['lignes'] ?? null) ? $data['lignes'] : [],
                    $utilisateurId
                );

                $this->json([
                    'success' => true,
                    'message' => 'Commande réceptionnée, les lots ont été ajoutés au stock.',
                    'data' => $resultat
                ]);

                break;


            default:

                $this->error('Action inconnue.', 400);
        }
    }


    /* =====================================================
       OUTILS
    ====================================================== */

    private function param(string $name): ?string
    {
        $value = trim((string)($_GET[$name] ?? ''));

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
