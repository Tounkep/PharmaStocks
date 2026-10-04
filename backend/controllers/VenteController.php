<?php

class VenteController
{
    private Vente $vente;

    public function __construct(PDO $db)
    {
        $this->vente =
            new Vente($db);
    }


    /* =====================================================
       PRODUITS
    ====================================================== */

    public function products(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                'products' =>
                    $this->vente->getProducts()
            ]
        ]);
    }


    /* =====================================================
       HISTORIQUE
    ====================================================== */

    public function history(): void
    {
        $this->json([
            'success' => true,
            'data' => [
                'ventes' =>
                    $this->vente->getHistory()
            ]
        ]);
    }


    /* =====================================================
       DETAIL
    ====================================================== */

    public function detail(int $id): void
    {
        $vente =
            $this->vente->getDetail($id);


        if (!$vente) {

            $this->error(
                "Vente introuvable.",
                404
            );

            return;
        }


        $this->json([
            'success' => true,
            'data' => [
                'vente' => $vente
            ]
        ]);
    }


    /* =====================================================
       CREATION
    ====================================================== */

    public function create(): void
    {
        $data =
            $this->readJson();


        $userId =
            $this->getConnectedUserId();


        if (!$userId) {

            $this->error(
                "Utilisateur non connecté.",
                401
            );

            return;
        }


        $items =
            $data['items'] ?? [];


        try {

            $vente =
                $this->vente->create(
                    $items,
                    $userId
                );


            $this->json([
                'success' => true,
                'message' =>
                    'Vente enregistrée avec succès.',
                'data' => [
                    'vente' =>
                        $vente
                ]
            ], 201);


        } catch (
            InvalidArgumentException $e
        ) {

            $this->error(
                $e->getMessage(),
                400
            );


        } catch (RuntimeException $e) {

            $this->error(
                $e->getMessage(),
                409
            );


        } catch (Throwable $e) {

            $this->error(
                "Erreur lors de l'enregistrement de la vente.",
                500
            );

        }
    }


    /* =====================================================
       UTILISATEUR CONNECTE
    ====================================================== */

    private function getConnectedUserId(): ?int
    {
        if (
            session_status() ===
            PHP_SESSION_NONE
        ) {
            session_start();
        }


        $possibleIds = [

            $_SESSION['user_id'] ?? null,

            $_SESSION['utilisateur_id'] ?? null,

            $_SESSION['user']['id'] ?? null,

            $_SESSION['utilisateur']['id'] ?? null

        ];


        foreach ($possibleIds as $id) {

            if (
                $id !== null &&
                filter_var(
                    $id,
                    FILTER_VALIDATE_INT
                )
            ) {

                return (int)$id;

            }

        }


        return null;
    }


    /* =====================================================
       JSON
    ====================================================== */

    private function readJson(): array
    {
        $content =
            file_get_contents(
                'php://input'
            );


        $data =
            json_decode(
                $content,
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

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );

        exit;
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