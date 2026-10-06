<?php

class MedicamentController
{
    private Medicament $medicament;

    public function __construct(Medicament $medicament)
    {
        $this->medicament = $medicament;
    }


    public function index(): void
    {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {

            case 'list':

                $this->json([
                    'success' => true,
                    'data' => $this->medicament->getAll()
                ]);

                break;


            case 'categories':

                $this->json([
                    'success' => true,
                    'data' => $this->medicament->getCategories()
                ]);

                break;


            case 'detail':

                $id = $this->getId();

                $product =
                    $this->medicament->findById($id);

                if (!$product) {

                    $this->error(
                        "Médicament introuvable.",
                        404
                    );

                    return;
                }

                $this->json([
                    'success' => true,

                    'data' => [
                        'produit' => $product,
                        'lots' =>
                            $this->medicament->getLots($id),
                        'historique' =>
                            $this->medicament->getHistory($id)
                    ]
                ]);

                break;


            default:

                $this->error(
                    "Action inconnue.",
                    400
                );
        }
    }


    public function show(int $id): void
    {
        $product = $this->medicament->findById($id);

        if (!$product) {
            $this->error(
                "Médicament introuvable.",
                404
            );

            return;
        }

        $this->json([
            'success' => true,
            'data' => [
                'id' => $product['id'],
                'reference' => $product['reference'],
                'nom' => $product['nom'],
                'description' => $product['description'],
                'forme' => $product['forme'],
                'dosage' => $product['dosage'],
                'prixAchat' => $product['prixAchat'],
                'prixVente' => $product['prixVente'],
                'seuilMinimum' => $product['seuilMinimum'],
                'categorie' => $product['categorie_nom'] ?? '-',
                'etat' => $product['statut'],
                'stockTotal' => $product['stock'],
                'prochaineExpiration' => $product['dateExpiration'],
                'lots' => $this->medicament->getLots($id),
                'mouvements' => $this->medicament->getHistory($id)
            ]
        ]);
    }


    public function create(): void
    {
        $data = $this->readJson();

        $id = $this->medicament->create($data);

        $this->json([
            'success' => true,
            'data' => [
                'id' => $id
            ],
            'message' =>
                'Médicament créé avec succès.'
        ], 201);
    }

    


    public function update(): void
    {
        $id = $this->getId();

        $data = $this->readJson();

        $this->medicament->update(
            $id,
            $data
        );

        $this->json([
            'success' => true,
            'message' =>
                'Médicament modifié avec succès.'
        ]);
    }


    public function delete(): void
    {
        $id = $this->getId();

        $this->medicament->delete($id);

        $this->json([
            'success' => true,
            'message' =>
                'Médicament supprimé avec succès.'
        ]);
    }


    public function createLot(): void
    {
        $data = $this->readJson();
        $produitId = (int)($data['produit_id'] ?? 0);

        if ($produitId <= 0) {
            $this->error('ID du médicament invalide.', 400);
            return;
        }

        $lotId = $this->medicament->createLot($produitId, $data);

        $this->json([
            'success' => true,
            'data' => ['id' => $lotId],
            'message' => 'Lot ajouté avec succès.'
        ], 201);
    }


    public function updateLot(): void
    {
        $data = $this->readJson();
        $lotId = (int)($data['lot_id'] ?? 0);

        if ($lotId <= 0) {
            $this->error('ID du lot invalide.', 400);
            return;
        }

        $this->medicament->updateLot($lotId, $data);

        $this->json([
            'success' => true,
            'message' => 'Lot modifié avec succès.'
        ]);
    }


    public function createMovement(): void
    {
        $data = $this->readJson();
        $lotId = (int)($data['lot_id'] ?? 0);
        $type = strtoupper((string)($data['type'] ?? ''));
        $quantite = (int)($data['quantite'] ?? 0);
        $motif = $data['motif'] ?? null;
        $utilisateurId = $_SESSION['utilisateur_id'] ?? 1;

        if ($lotId <= 0) {
            $this->error('ID du lot invalide.', 400);
            return;
        }

        $result = $this->medicament->applyStockMovement(
            $lotId,
            $type,
            $quantite,
            $motif,
            (int)$utilisateurId
        );

        $this->json([
            'success' => true,
            'data' => $result,
            'message' => 'Mouvement de stock enregistré.'
        ]);
    }


    private function getId(): int
    {
        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id <= 0) {
            throw new InvalidArgumentException(
                "Identifiant invalide."
            );
        }

        return $id;
    }


    private function readJson(): array
    {
        $raw = file_get_contents(
            'php://input'
        );

        $data = json_decode(
            $raw,
            true
        );

        if (!is_array($data)) {
            throw new InvalidArgumentException(
                "Données JSON invalides."
            );
        }

        return $data;
    }


    private function json(
        array $data,
        int $status = 200
    ): void {

        http_response_code($status);

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }


    private function error(
        string $message,
        int $status
    ): void {

        $this->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}