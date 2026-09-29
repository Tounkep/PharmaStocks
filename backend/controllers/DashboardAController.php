<?php

class DashboardController
{
    private Dashboard $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }


    /**
     * Retourne toutes les données du dashboard admin.
     */
    public function index(): void
    {
        try {

            $data = [
                'statistiques' => $this->dashboard->getStatistiques(),

                'stockFaible' => $this->dashboard->getProduitsStockFaible(),

                'expirationsProches' => $this->dashboard->getExpirationsProches(),

                'lotsExpires' => $this->dashboard->getLotsExpires(),

                'dernieresVentes' => $this->dashboard->getDernieresVentes(),

                'derniersMouvements' => $this->dashboard->getDerniersMouvements(),

                'alertes' => $this->dashboard->getAlertes(),

                'activiteRecente' => $this->dashboard->getActiviteRecente(),

                'ventesDerniersJours' => $this->dashboard->getVentesDerniersJours(),

                'commandesRecentes' => $this->dashboard->getCommandesRecentes()
            ];


            http_response_code(200);

            echo json_encode(
                [
                    'success' => true,
                    'data' => $data,
                    'message' => 'Données du dashboard récupérées avec succès.'
                ],
                JSON_UNESCAPED_UNICODE
            );

        } catch (PDOException $e) {

            http_response_code(500);

            echo json_encode(
                [
                    'success' => false,
                    'message' => 'Erreur lors de la récupération des données du dashboard.'
                ],
                JSON_UNESCAPED_UNICODE
            );
        }
    }
}