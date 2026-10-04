<?php

class Fournisseur
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les fournisseurs
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                id,
                nom,
                adresse,
                telephone,
                email
            FROM Fournisseur
            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Vérifier si un fournisseur existe déjà
     */
    public function existe(
        string $nom,
        string $email = '',
        int $id = 0
    ): bool {

        $sql = "
            SELECT id
            FROM Fournisseur
            WHERE nom = :nom
        ";

        $params = [
            'nom' => $nom
        ];

        if ($email !== '') {
            $sql .= " OR email = :email";
            $params['email'] = $email;
        }

        $sql .= "
            AND id != :id
            LIMIT 1
        ";

        $params['id'] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    /**
     * Ajouter un fournisseur
     */
    public function ajouter(
        string $nom,
        string $adresse,
        string $telephone,
        string $email
    ): bool {

        $sql = "
            INSERT INTO Fournisseur
            (
                nom,
                adresse,
                telephone,
                email
            )
            VALUES
            (
                :nom,
                :adresse,
                :telephone,
                :email
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'nom' => $nom,
            'adresse' => $adresse,
            'telephone' => $telephone,
            'email' => $email
        ]);
    }

    /**
     * Modifier un fournisseur
     */
    public function modifier(
        int $id,
        string $nom,
        string $adresse,
        string $telephone,
        string $email
    ): bool {

        $sql = "
            UPDATE Fournisseur
            SET
                nom = :nom,
                adresse = :adresse,
                telephone = :telephone,
                email = :email
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'adresse' => $adresse,
            'telephone' => $telephone,
            'email' => $email
        ]);
    }

    /**
     * Supprimer un fournisseur
     */
    public function supprimer(int $id): bool
    {
        $sql = "
            DELETE FROM Fournisseur
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id
        ]);
    }
}