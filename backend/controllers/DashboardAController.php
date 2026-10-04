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

                /*
                 * =====================================================
                 * UTILISATEUR CONNECTÉ
                 * =====================================================
                 */

                'utilisateur' => [
                    'id' => $_SESSION['utilisateur_id'] ?? null,
                    'nom' => $_SESSION['nom'] ?? '',
                    'prenom' => $_SESSION['prenom'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                    'role' => $_SESSION['role'] ?? ''
                ],


                /*
                 * =====================================================
                 * STATISTIQUES
                 * =====================================================
                 */

                'statistiques' =>
                    $this->dashboard->getStatistiques(),


                /*
                 * =====================================================
                 * STOCK FAIBLE
                 * =====================================================
                 */

                'stockFaible' =>
                    $this->dashboard->getProduitsStockFaible(),


                /*
                 * =====================================================
                 * EXPIRATIONS PROCHES
                 * =====================================================
                 */

                'expirationsProches' =>
                    $this->dashboard->getExpirationsProches(),


                /*
                 * =====================================================
                 * LOTS EXPIRÉS
                 * =====================================================
                 */

                'lotsExpires' =>
                    $this->dashboard->getLotsExpires(),


                /*
                 * =====================================================
                 * DERNIÈRES VENTES
                 * =====================================================
                 */

                'dernieresVentes' =>
                    $this->dashboard->getDernieresVentes(),


                /*
                 * =====================================================
                 * DERNIERS MOUVEMENTS
                 * =====================================================
                 */

                'derniersMouvements' =>
                    $this->dashboard->getDerniersMouvements(),


                /*
                 * =====================================================
                 * ALERTES
                 * =====================================================
                 */

                'alertes' =>
                    $this->dashboard->getAlertes(),


                /*
                 * =====================================================
                 * ACTIVITÉ RÉCENTE
                 * =====================================================
                 */

                'activiteRecente' =>
                    $this->dashboard->getActiviteRecente(),


                /*
                 * =====================================================
                 * VENTES DES DERNIERS JOURS
                 * =====================================================
                 */

                'ventesDerniersJours' =>
                    $this->dashboard->getVentesDerniersJours(),


                /*
                 * =====================================================
                 * COMMANDES RÉCENTES
                 * =====================================================
                 */

                'commandesRecentes' =>
                    $this->dashboard->getCommandesRecentes()
            ];


            http_response_code(200);


            echo json_encode(
                [
                    'success' => true,
                    'data' => $data,
                    'message' =>
                        'Données du dashboard récupérées avec succès.'
                ],
                JSON_UNESCAPED_UNICODE
            );


        } catch (PDOException $e) {

            http_response_code(500);


            echo json_encode(
                [
                    'success' => false,
                    'message' =>
                        'Erreur lors de la récupération des données du dashboard.'
                ],
                JSON_UNESCAPED_UNICODE
            );
        }
    }
}