
<?php

require_once __DIR__ . '/../models/Rapport.php';

class RapportController
{
    private Rapport $rapport;

    public function __construct(PDO $db)
    {
        $this->rapport = new Rapport($db);
    }

    public function index(): array
    {
        $dateDebut = $_GET['dateDebut'] ?? null;
        $dateFin = $_GET['dateFin'] ?? null;

        if (($dateDebut === null) !== ($dateFin === null)) {
            throw new InvalidArgumentException('Sélectionnez les deux dates ou aucune.');
        }

        if ($dateDebut === null) {
            return [
                'statistiques' => $this->rapport->getStatistiques(),
                'evolutionVentes' => $this->rapport->getEvolutionVentes(),
                'commandesParStatut' => $this->rapport->getCommandesParStatut(),
                'evolutionMouvements' => $this->rapport->getEvolutionMouvements(),
                'derniersMouvements' => $this->rapport->getDerniersMouvements()
            ];
        }

        $debut = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebut);
        $fin = DateTimeImmutable::createFromFormat('!Y-m-d', $dateFin);

        if (
            !$debut ||
            !$fin ||
            $debut->format('Y-m-d') !== $dateDebut ||
            $fin->format('Y-m-d') !== $dateFin ||
            $dateDebut > $dateFin
        ) {
            throw new InvalidArgumentException(
                'Les dates sélectionnées sont invalides.'
            );
        }

        return [
            'statistiques' => $this->rapport->getStatistiques(
                $dateDebut,
                $dateFin
            ),
            'evolutionVentes' => $this->rapport->getEvolutionVentes(
                $dateDebut,
                $dateFin
            ),
            'commandesParStatut' => $this->rapport->getCommandesParStatut(
                $dateDebut,
                $dateFin
            ),
            'evolutionMouvements' => $this->rapport->getEvolutionMouvements($dateDebut, $dateFin),
            'derniersMouvements' => $this->rapport->getDerniersMouvements($dateDebut, $dateFin)
        ];
    }
}
