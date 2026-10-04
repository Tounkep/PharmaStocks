document.addEventListener("DOMContentLoaded", () => {
    chargerUtilisateurConnecte();
    chargerUtilisateurs();
    initialiserEvenements();
});


/* =========================================================
   URL DES API
========================================================= */

const API_URL = "../../backend/routes/utilisateurs.php";
const AUTH_URL = "../../backend/auth/connexion.php";
const DASHBOARD_URL = "../../backend/routes/dashboard.php";


/* =========================================================
   INITIALISATION DES EVENEMENTS
========================================================= */

function initialiserEvenements() {

    const addUserBtn = document.getElementById("addUserBtn");

    if (addUserBtn) {
        addUserBtn.addEventListener("click", ouvrirModalAjout);
    }


    const closeModalBtn = document.getElementById("closeModalBtn");

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", fermerModal);
    }


    const cancelUserBtn = document.getElementById("cancelUserBtn");

    if (cancelUserBtn) {
        cancelUserBtn.addEventListener("click", fermerModal);
    }


    const userForm = document.getElementById("userForm");

    if (userForm) {
        userForm.addEventListener("submit", enregistrerUtilisateur);
    }


    const globalSearch = document.getElementById("globalSearch");

    if (globalSearch) {
        globalSearch.addEventListener("input", rechercherUtilisateur);
    }


    const logoutBtn = document.getElementById("logoutBtn");

    if (logoutBtn) {
        logoutBtn.addEventListener("click", deconnexion);
    }


    const userModal = document.getElementById("userModal");

    if (userModal) {

        userModal.addEventListener("click", (event) => {

            if (event.target === userModal) {
                fermerModal();
            }

        });
    }


    document.addEventListener("keydown", (event) => {

        if (event.key === "Escape") {
            fermerModal();
        }

    });
}


/* =========================================================
   UTILISATEUR CONNECTE
========================================================= */

async function chargerUtilisateurConnecte() {

    try {

        const response = await fetch(DASHBOARD_URL, {
            method: "GET",
            credentials: "include"
        });


        if (response.status === 401) {

            window.location.href = "connexion.html";

            return;
        }


        if (response.status === 403) {

            alert("Accès réservé à l'administrateur.");

            window.location.href = "connexion.html";

            return;
        }


        const result = await response.json();


        if (
            result.success &&
            result.data &&
            result.data.utilisateur
        ) {

            afficherUtilisateurConnecte(
                result.data.utilisateur
            );
        }

    } catch (error) {

        console.error(
            "Erreur lors du chargement de l'utilisateur connecté :",
            error
        );
    }
}


/* =========================================================
   AFFICHER UTILISATEUR CONNECTE
========================================================= */

function afficherUtilisateurConnecte(utilisateur) {

    const adminName =
        document.getElementById("adminName");


    if (adminName) {

        const nomComplet =
            `${utilisateur.prenom || ""} ${utilisateur.nom || ""}`.trim();

        adminName.textContent =
            nomComplet || "Admin";
    }


    const adminAvatar =
        document.getElementById("adminAvatar");


    if (adminAvatar) {

        const prenom =
            utilisateur.prenom || "";

        adminAvatar.textContent =
            prenom !== ""
                ? prenom.charAt(0).toUpperCase()
                : "A";
    }
}


/* =========================================================
   CHARGER LES UTILISATEURS
========================================================= */

async function chargerUtilisateurs() {

    try {

        const response = await fetch(API_URL, {
            method: "GET",
            credentials: "include"
        });


        if (response.status === 401) {

            window.location.href = "connexion.html";

            return;
        }


        if (response.status === 403) {

            afficherMessage(
                "Accès réservé à l'administrateur.",
                "error"
            );

            return;
        }


        const result =
            await response.json();


        if (!response.ok || !result.success) {

            afficherMessage(
                result.message ||
                "Impossible de charger les utilisateurs.",
                "error"
            );

            return;
        }


        const utilisateurs =
            result.data?.utilisateurs || [];


        afficherUtilisateurs(utilisateurs);

    } catch (error) {

        console.error(
            "Erreur lors du chargement des utilisateurs :",
            error
        );


        afficherMessage(
            "Erreur de communication avec le serveur.",
            "error"
        );
    }
}


/* =========================================================
   AFFICHER LES UTILISATEURS
========================================================= */

function afficherUtilisateurs(utilisateurs) {

    const tbody =
        document.getElementById("usersTableBody");


    const emptyUsers =
        document.getElementById("emptyUsers");


    const userCount =
        document.getElementById("userCount");


    if (!tbody) {
        return;
    }


    // Vider le tableau
    tbody.innerHTML = "";


    // Mettre à jour le nombre
    if (userCount) {

        userCount.textContent =
            `${utilisateurs.length} utilisateur${utilisateurs.length > 1 ? "s" : ""}`;
    }


    // Aucun utilisateur
    if (utilisateurs.length === 0) {

        if (emptyUsers) {
            emptyUsers.style.display = "block";
        }

        return;
    }


    if (emptyUsers) {
        emptyUsers.style.display = "none";
    }


    utilisateurs.forEach((utilisateur) => {

        const tr =
            document.createElement("tr");


        /* ---------------------------------------------
           ROLE
        --------------------------------------------- */

        const role =
            (utilisateur.role || "").toUpperCase();


        let classeRole =
            "role-employe";


        let roleAffiche =
            "Employé";


        if (role === "ADMIN") {

            classeRole =
                "role-admin";

            roleAffiche =
                "Administrateur";

        } else if (role === "PHARMACIEN") {

            classeRole =
                "role-pharmacien";

            roleAffiche =
                "Pharmacien";
        }


        /* ---------------------------------------------
           LIGNE
        --------------------------------------------- */

        tr.innerHTML = `

            <!-- ID -->
            <td class="user-id">
                ${utilisateur.id}
            </td>


            <!-- NOM -->
            <td>
                <div class="user-name">
                    ${echapperHTML(utilisateur.nom || "")}
                </div>
            </td>


            <!-- PRENOM -->
            <td>
                <div class="user-firstname">
                    ${echapperHTML(utilisateur.prenom || "")}
                </div>
            </td>


            <!-- EMAIL -->
            <td>
                <div class="user-email">
                    ${echapperHTML(utilisateur.email || "")}
                </div>
            </td>


            <!-- ROLE -->
            <td>
                <span class="role-badge ${classeRole}">
                    ${roleAffiche}
                </span>
            </td>


            <!-- ACTIONS -->
            <td>
                <div class="user-actions">

                    <button
                        type="button"
                        class="edit-user-btn"
                        onclick="ouvrirModalModification(${utilisateur.id})"
                        title="Modifier"
                        aria-label="Modifier"
                    >
                        ✏️
                    </button>


                    <button
                        type="button"
                        class="delete-user-btn"
                        onclick="supprimerUtilisateur(${utilisateur.id})"
                        title="Supprimer"
                        aria-label="Supprimer"
                    >
                        🗑️
                    </button>

                </div>
            </td>
        `;


        tbody.appendChild(tr);
    });
}


/* =========================================================
   OUVRIR MODAL AJOUT
========================================================= */

function ouvrirModalAjout() {

    const modal =
        document.getElementById("userModal");


    const form =
        document.getElementById("userForm");


    const title =
        document.getElementById("modalTitle");


    const userId =
        document.getElementById("userId");


    const password =
        document.getElementById("motDePasse");


    const passwordHelp =
        document.getElementById("passwordHelp");


    if (!modal || !form) {
        return;
    }


    // Réinitialiser
    form.reset();


    // ID vide = nouvel utilisateur
    if (userId) {
        userId.value = "";
    }


    if (title) {

        title.textContent =
            "Ajouter un utilisateur";
    }


    if (password) {

        password.value = "";
        password.required = true;
    }


    if (passwordHelp) {

        passwordHelp.textContent =
            "Obligatoire lors de la création.";
    }


    modal.classList.add("active");
}


/* =========================================================
   OUVRIR MODAL MODIFICATION
========================================================= */

async function ouvrirModalModification(id) {

    try {

        const response = await fetch(API_URL, {
            method: "GET",
            credentials: "include"
        });


        const result =
            await response.json();


        if (!response.ok || !result.success) {

            afficherMessage(
                result.message ||
                "Impossible de récupérer les utilisateurs.",
                "error"
            );

            return;
        }


        const utilisateurs =
            result.data?.utilisateurs || [];


        const utilisateur =
            utilisateurs.find(
                (user) =>
                    Number(user.id) === Number(id)
            );


        if (!utilisateur) {

            afficherMessage(
                "Utilisateur introuvable.",
                "error"
            );

            return;
        }


        const modal =
            document.getElementById("userModal");


        const title =
            document.getElementById("modalTitle");


        const userId =
            document.getElementById("userId");


        const nom =
            document.getElementById("nom");


        const prenom =
            document.getElementById("prenom");


        const email =
            document.getElementById("email");


        const motDePasse =
            document.getElementById("motDePasse");


        const roleId =
            document.getElementById("role_id");


        const passwordHelp =
            document.getElementById("passwordHelp");


        if (userId) {
            userId.value =
                utilisateur.id;
        }


        if (nom) {
            nom.value =
                utilisateur.nom || "";
        }


        if (prenom) {
            prenom.value =
                utilisateur.prenom || "";
        }


        if (email) {
            email.value =
                utilisateur.email || "";
        }


        if (roleId) {
            roleId.value =
                utilisateur.role_id || "";
        }


        if (motDePasse) {

            motDePasse.value = "";

            motDePasse.required = false;
        }


        if (passwordHelp) {

            passwordHelp.textContent =
                "Laissez vide pour conserver le mot de passe actuel.";
        }


        if (title) {

            title.textContent =
                "Modifier un utilisateur";
        }


        if (modal) {

            modal.classList.add("active");
        }

    } catch (error) {

        console.error(
            "Erreur lors de la modification :",
            error
        );


        afficherMessage(
            "Erreur de communication avec le serveur.",
            "error"
        );
    }
}


/* =========================================================
   ENREGISTRER
========================================================= */

async function enregistrerUtilisateur(event) {

    event.preventDefault();


    const userId =
        document.getElementById("userId")?.value.trim() || "";


    const nom =
        document.getElementById("nom")?.value.trim() || "";


    const prenom =
        document.getElementById("prenom")?.value.trim() || "";


    const email =
        document.getElementById("email")?.value.trim() || "";


    const motDePasse =
        document.getElementById("motDePasse")?.value || "";


    const roleId =
        document.getElementById("role_id")?.value || "";


    /* ---------------------------------------------
       VALIDATION
    --------------------------------------------- */

    if (
        nom === "" ||
        prenom === "" ||
        email === "" ||
        roleId === ""
    ) {

        afficherMessage(
            "Veuillez remplir tous les champs obligatoires.",
            "error"
        );

        return;
    }


    // Mot de passe obligatoire seulement à la création
    if (
        userId === "" &&
        motDePasse === ""
    ) {

        afficherMessage(
            "Le mot de passe est obligatoire lors de la création.",
            "error"
        );

        return;
    }


    /* ---------------------------------------------
       DONNEES
    --------------------------------------------- */

    const donnees = {

        nom: nom,

        prenom: prenom,

        email: email,

        motDePasse: motDePasse,

        role_id: Number(roleId)
    };


    try {

        let response;


        /* -----------------------------------------
           MODIFICATION
        ----------------------------------------- */

        if (userId !== "") {

            response = await fetch(
                `${API_URL}?id=${encodeURIComponent(userId)}`,
                {
                    method: "PUT",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    credentials: "include",

                    body: JSON.stringify(donnees)
                }
            );
        }


        /* -----------------------------------------
           AJOUT
        ----------------------------------------- */

        else {

            response = await fetch(
                API_URL,
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    credentials: "include",

                    body: JSON.stringify(donnees)
                }
            );
        }


        /* -----------------------------------------
           REPONSE
        ----------------------------------------- */

        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            afficherMessage(
                result.message ||
                "Une erreur est survenue.",
                "error"
            );

            return;
        }


        /* -----------------------------------------
           SUCCES
        ----------------------------------------- */

        afficherMessage(
            result.message ||
            (
                userId !== ""
                    ? "Utilisateur modifié avec succès."
                    : "Utilisateur ajouté avec succès."
            ),
            "success"
        );


        fermerModal();


        // Recharger la liste
        await chargerUtilisateurs();

    } catch (error) {

        console.error(
            "Erreur lors de l'enregistrement :",
            error
        );


        afficherMessage(
            "Erreur de communication avec le serveur.",
            "error"
        );
    }
}


/* =========================================================
   SUPPRIMER
========================================================= */

async function supprimerUtilisateur(id) {

    const confirmation =
        confirm(
            "Voulez-vous vraiment supprimer cet utilisateur ?"
        );


    if (!confirmation) {
        return;
    }


    try {

        const response =
            await fetch(
                `${API_URL}?id=${encodeURIComponent(id)}`,
                {
                    method: "DELETE",
                    credentials: "include"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            afficherMessage(
                result.message ||
                "Impossible de supprimer l'utilisateur.",
                "error"
            );

            return;
        }


        afficherMessage(
            result.message ||
            "Utilisateur supprimé avec succès.",
            "success"
        );


        await chargerUtilisateurs();

    } catch (error) {

        console.error(
            "Erreur lors de la suppression :",
            error
        );


        afficherMessage(
            "Erreur de communication avec le serveur.",
            "error"
        );
    }
}


/* =========================================================
   FERMER LE MODAL
========================================================= */

function fermerModal() {

    const modal =
        document.getElementById("userModal");


    if (modal) {

        modal.classList.remove("active");
    }
}


/* =========================================================
   RECHERCHE
========================================================= */

function rechercherUtilisateur() {

    const searchInput =
        document.getElementById("globalSearch");


    const tbody =
        document.getElementById("usersTableBody");


    if (!searchInput || !tbody) {
        return;
    }


    const recherche =
        searchInput.value
            .toLowerCase()
            .trim();


    const lignes =
        tbody.querySelectorAll("tr");


    lignes.forEach((ligne) => {

        const texte =
            ligne.textContent
                .toLowerCase();


        if (texte.includes(recherche)) {

            ligne.style.display = "";

        } else {

            ligne.style.display = "none";
        }

    });
}


/* =========================================================
   DECONNEXION
========================================================= */

async function deconnexion(event) {

    if (event) {
        event.preventDefault();
    }


    try {

        await fetch(
            AUTH_URL,
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body: "action=logout",

                credentials: "include"
            }
        );

    } catch (error) {

        console.error(
            "Erreur lors de la déconnexion :",
            error
        );

    } finally {

        window.location.href =
            "connexion.html";
    }
}


/* =========================================================
   MESSAGE
========================================================= */

function afficherMessage(
    message,
    type = "success"
) {

    const element =
        document.getElementById("message");


    if (!element) {
        return;
    }


    element.textContent =
        message;


    element.className =
        "users-message";


    if (type === "error") {

        element.classList.add("error");

    } else {

        element.classList.add("success");
    }


    element.style.display =
        "block";


    setTimeout(() => {

        element.style.display =
            "none";

    }, 4000);
}


/* =========================================================
   PROTECTION CONTRE L'INJECTION HTML
========================================================= */

function echapperHTML(texte) {

    const div =
        document.createElement("div");


    div.textContent =
        texte ?? "";


    return div.innerHTML;
}