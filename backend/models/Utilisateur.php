<?php

class Utilisateur
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les utilisateurs
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                u.id,
                u.nom,
                u.prenom,
                u.email,
                u.dateCreation,
                u.role_id,
                r.nom AS role
            FROM Utilisateur u
            INNER JOIN Role r ON u.role_id = r.id
            ORDER BY u.id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Récupérer tous les rôles
     */
    public function getRoles(): array
    {
        $sql = "
            SELECT
                id,
                nom
            FROM Role
            ORDER BY id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Vérifier si un email existe déjà
     *
     * @param string $email
     * @param int $id ID de l'utilisateur à exclure lors d'une modification
     */
    public function emailExiste(string $email, int $id = 0): bool
    {
        $sql = "
            SELECT id
            FROM Utilisateur
            WHERE email = :email
            AND id != :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'email' => $email,
            'id' => $id
        ]);

        return $stmt->fetch() !== false;
    }

    /**
     * Ajouter un utilisateur
     */
    public function ajouter(
        string $nom,
        string $prenom,
        string $email,
        string $motDePasse,
        int $roleId
    ): bool {
        $sql = "
            INSERT INTO Utilisateur
            (
                nom,
                prenom,
                email,
                motDePasse,
                dateCreation,
                role_id
            )
            VALUES
            (
                :nom,
                :prenom,
                :email,
                :motDePasse,
                :dateCreation,
                :role_id
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'motDePasse' => $motDePasse,
            'dateCreation' => date('Y-m-d'),
            'role_id' => $roleId
        ]);
    }

    /**
     * Modifier un utilisateur
     *
     * Le mot de passe n'est modifié que s'il est renseigné.
     */
    public function modifier(
        int $id,
        string $nom,
        string $prenom,
        string $email,
        int $roleId,
        string $motDePasse = ''
    ): bool {
        if ($motDePasse !== '') {
            $sql = "
                UPDATE Utilisateur
                SET
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    motDePasse = :motDePasse,
                    role_id = :role_id
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                'id' => $id,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'motDePasse' => $motDePasse,
                'role_id' => $roleId
            ]);
        }

        $sql = "
            UPDATE Utilisateur
            SET
                nom = :nom,
                prenom = :prenom,
                email = :email,
                role_id = :role_id
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role_id' => $roleId
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function supprimer(int $id): bool
    {
        $sql = "
            DELETE FROM Utilisateur
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id
        ]);
    }
}