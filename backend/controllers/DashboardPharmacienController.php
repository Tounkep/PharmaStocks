<?php

class DashboardPharmacienController
{
    private DashboardPharmacien $dashboard;
    private Alerte $alerte;
    private Stock $stock;

    public function __construct(
        DashboardPharmacien $dashboard,
        Alerte $alerte,
        Stock $stock
    ) {
        $this->dashboard = $dashboard;
        $this->alerte = $alerte;
        $this->stock = $stock;
    }


    public function index(array $utilisateur): void
    {
        /*
         * Mise à jour des statuts des lots et des alertes
         * avant de calculer les indicateurs.
         */
        $this->stock->synchroniserStatutsLots();
        $this->alerte->synchroniser();

        $this->json([
            'success' => true,
            'data' => [
                'utilisateur' => $utilisateur,
                'statistiques' => $this->dashboard->getStatistiques(),
                'alertes' => $this->alerte->compterNouvelles(),
                'ventes7Jours' => $this->dashboard->getVentes7Jours(),
                'aReapprovisionner' => $this->dashboard->getProduitsAReapprovisionner(),
                'lotsAExpiration' => $this->dashboard->getLotsAExpiration(),
                'derniersMouvements' => $this->dashboard->getDerniersMouvements(),
                'commandesEnCours' => $this->dashboard->getCommandesEnCours()
            ]
        ]);
    }


    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
