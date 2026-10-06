document.addEventListener("DOMContentLoaded", () => {
    chargerUtilisateurConnecte();
    chargerFournisseurs();
    initialiserEvenements();
});


const API_FOURNISSEURS = "../../backend/routes/fournisseurs.php";
const API_DASHBOARD = "../../backend/routes/dashboard.php";


/* =========================================================
   INITIALISATION
========================================================= */

function initialiserEvenements() {

    const addButton = document.getElementById("addSupplierBtn");

    if (addButton) {
        addButton.addEventListener("click", ouvrirModalAjout);
    }


    const closeButton = document.getElementById("closeModalBtn");

    if (closeButton) {
        closeButton.addEventListener("click", fermerModal);
    }


    const cancelButton = document.getElementById("cancelSupplierBtn");

    if (cancelButton) {
        cancelButton.addEventListener("click", fermerModal);
    }


    const modal = document.getElementById("supplierModal");

    if (modal) {

        modal.addEventListener("click", event => {

            if (event.target === modal) {
                fermerModal();
            }

        });
    }


    document.addEventListener("keydown", event => {

        if (event.key === "Escape") {
            fermerModal();
        }

    });


    const form = document.getElementById("supplierForm");

    if (form) {
        form.addEventListener("submit", enregistrerFournisseur);
    }


    const search = document.getElementById("globalSearch");

    if (search) {

        search.addEventListener("input", () => {
            rechercherFournisseur(search.value);
        });

    }


    const logoutButton = document.getElementById("logoutBtn");

    if (logoutButton) {
        logoutButton.addEventListener("click", deconnexion);
    }
}


/* =========================================================
   UTILISATEUR CONNECTÉ
========================================================= */

async function chargerUtilisateurConnecte() {

    try {

        const response = await fetch(API_DASHBOARD, {
            method: "GET",
            credentials: "include"
        });

        if (response.status === 401) {
            window.location.href = "connexion.html";
            return;
        }

        if (response.status === 403) {
            window.location.href = "dashboard-admin.html";
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
            "Erreur lors de la récupération de l'utilisateur :",
            error
        );
    }
}


function afficherUtilisateurConnecte(utilisateur) {

    const adminName = document.getElementById("adminName");

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
   CHARGER FOURNISSEURS
========================================================= */

async function chargerFournisseurs() {

    try {

        const response = await fetch(
            API_FOURNISSEURS,
            {
                method: "GET",
                credentials: "include"
            }
        );

        if (response.status === 401) {
            window.location.href = "connexion.html";
            return;
        }

        if (response.status === 403) {

            afficherMessage(
                "Vous n'avez pas les droits pour gérer les fournisseurs.",
                "error"
            );

            return;
        }

        const result = await response.json();

        if (!result.success) {

            afficherMessage(
                result.message || "Impossible de charger les fournisseurs.",
                "error"
            );

            return;
        }

        afficherFournisseurs(
            result.data.fournisseurs || []
        );

    } catch (error) {

        console.error(
            "Erreur lors du chargement des fournisseurs :",
            error
        );

        afficherMessage(
            "Une erreur est survenue lors du chargement des fournisseurs.",
            "error"
        );
    }
}


/* =========================================================
   AFFICHER FOURNISSEURS
========================================================= */

function afficherFournisseurs(fournisseurs) {

    const tbody =
        document.getElementById("suppliersTableBody");

    const empty =
        document.getElementById("emptySuppliers");

    const count =
        document.getElementById("supplierCount");


    if (!tbody) {
        return;
    }


    tbody.innerHTML = "";


    if (count) {

        count.textContent =
            `${fournisseurs.length} fournisseur${fournisseurs.length > 1 ? "s" : ""}`;
    }


    if (fournisseurs.length === 0) {

        if (empty) {
            empty.style.display = "block";
        }

        return;
    }


    if (empty) {
        empty.style.display = "none";
    }


    fournisseurs.forEach(fournisseur => {

        const row =
            document.createElement("tr");


        row.innerHTML = `
            <td class="supplier-id">
                ${echapperHTML(fournisseur.id)}
            </td>

            <td class="supplier-name">
                ${echapperHTML(fournisseur.nom)}
            </td>

            <td class="supplier-address">
                ${echapperHTML(fournisseur.adresse || "-")}
            </td>

            <td class="supplier-phone">
                ${echapperHTML(fournisseur.telephone || "-")}
            </td>

            <td class="supplier-email">
                ${echapperHTML(fournisseur.email || "-")}
            </td>

            <td>
                <div class="supplier-actions">

                    <button
                        type="button"
                        class="edit-supplier-btn"
                        title="Modifier"
                        aria-label="Modifier"
                        data-id="${fournisseur.id}"
                    >
                        ✏️
                    </button>

                    <button
                        type="button"
                        class="delete-supplier-btn"
                        title="Supprimer"
                        aria-label="Supprimer"
                        data-id="${fournisseur.id}"
                    >
                        🗑️
                    </button>

                </div>
            </td>
        `;


        tbody.appendChild(row);
    });


    document
        .querySelectorAll(".edit-supplier-btn")
        .forEach(button => {

            button.addEventListener("click", () => {

                const id =
                    parseInt(button.dataset.id);

                modifierFournisseur(id);
            });

        });


    document
        .querySelectorAll(".delete-supplier-btn")
        .forEach(button => {

            button.addEventListener("click", () => {

                const id =
                    parseInt(button.dataset.id);

                supprimerFournisseur(id);
            });

        });
}


/* =========================================================
   MODAL AJOUT
========================================================= */

function ouvrirModalAjout() {

    const modal =
        document.getElementById("supplierModal");

    const form =
        document.getElementById("supplierForm");

    const title =
        document.getElementById("modalTitle");

    const id =
        document.getElementById("supplierId");


    if (form) {
        form.reset();
    }

    if (id) {
        id.value = "";
    }

    if (title) {
        title.textContent =
            "Ajouter un fournisseur";
    }

    if (modal) {
        modal.classList.add("active");
    }
}


/* =========================================================
   FERMER MODAL
========================================================= */

function fermerModal() {

    const modal =
        document.getElementById("supplierModal");

    if (modal) {
        modal.classList.remove("active");
    }
}


/* =========================================================
   ENREGISTRER
========================================================= */

async function enregistrerFournisseur(event) {

    event.preventDefault();


    const id =
        document.getElementById("supplierId").value.trim();

    const nom =
        document.getElementById("nom").value.trim();

    const adresse =
        document.getElementById("adresse").value.trim();

    const telephone = document
    .getElementById("telephone")
    .value
    .trim();

    if (
        telephone !== "" &&
        !/^\d{1,11}$/.test(telephone)
    ) {
        showMessage(
            "Le numéro de téléphone doit contenir au maximum 11 chiffres.",
            "error"
        );

        return;
    }
    const email =
        document.getElementById("email").value.trim();


    if (nom === "") {

        afficherMessage(
            "Le nom du fournisseur est obligatoire.",
            "error"
        );

        return;
    }


    const donnees = {
        nom,
        adresse,
        telephone,
        email
    };


    try {

        let url = API_FOURNISSEURS;
        let method = "POST";


        if (id !== "") {

            url =
                `${API_FOURNISSEURS}?id=${encodeURIComponent(id)}`;

            method = "PUT";
        }


        const response = await fetch(
            url,
            {
                method,
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify(donnees)
            }
        );


        const result =
            await response.json();


        if (!result.success) {

            afficherMessage(
                result.message || "Une erreur est survenue.",
                "error"
            );

            return;
        }


        fermerModal();

        afficherMessage(
            result.message,
            "success"
        );


        await chargerFournisseurs();


    } catch (error) {

        console.error(
            "Erreur lors de l'enregistrement :",
            error
        );

        afficherMessage(
            "Une erreur est survenue lors de l'enregistrement.",
            "error"
        );
    }
}


/* =========================================================
   MODIFIER
========================================================= */

async function modifierFournisseur(id) {

    try {

        const response = await fetch(
            API_FOURNISSEURS,
            {
                method: "GET",
                credentials: "include"
            }
        );


        const result =
            await response.json();


        if (!result.success) {
            return;
        }


        const fournisseurs =
            result.data.fournisseurs || [];


        const fournisseur =
            fournisseurs.find(
                item => parseInt(item.id) === id
            );


        if (!fournisseur) {

            afficherMessage(
                "Fournisseur introuvable.",
                "error"
            );

            return;
        }


        document.getElementById("supplierId").value =
            fournisseur.id;

        document.getElementById("nom").value =
            fournisseur.nom || "";

        document.getElementById("adresse").value =
            fournisseur.adresse || "";

        document.getElementById("telephone").value =
            fournisseur.telephone || "";

        document.getElementById("email").value =
            fournisseur.email || "";


        const title =
            document.getElementById("modalTitle");

        if (title) {
            title.textContent =
                "Modifier un fournisseur";
        }


        const modal =
            document.getElementById("supplierModal");

        if (modal) {
            modal.classList.add("active");
        }


    } catch (error) {

        console.error(
            "Erreur lors de la modification :",
            error
        );

    }
}


/* =========================================================
   SUPPRIMER
========================================================= */

async function supprimerFournisseur(id) {

    const confirmation =
        confirm(
            "Voulez-vous vraiment supprimer ce fournisseur ?"
        );


    if (!confirmation) {
        return;
    }


    try {

        const response = await fetch(
            `${API_FOURNISSEURS}?id=${encodeURIComponent(id)}`,
            {
                method: "DELETE",
                credentials: "include"
            }
        );


        const result =
            await response.json();


        if (!result.success) {

            afficherMessage(
                result.message || "Impossible de supprimer le fournisseur.",
                "error"
            );

            return;
        }


        afficherMessage(
            result.message,
            "success"
        );


        await chargerFournisseurs();


    } catch (error) {

        console.error(
            "Erreur lors de la suppression :",
            error
        );

        afficherMessage(
            "Une erreur est survenue lors de la suppression.",
            "error"
        );
    }
}


/* =========================================================
   RECHERCHE
========================================================= */

function rechercherFournisseur(texte) {

    const recherche =
        texte.toLowerCase().trim();

    const lignes =
        document.querySelectorAll(
            "#suppliersTableBody tr"
        );


    lignes.forEach(ligne => {

        const contenu =
            ligne.textContent.toLowerCase();

        ligne.style.display =
            contenu.includes(recherche)
                ? ""
                : "none";
    });
}


/* =========================================================
   MESSAGE
========================================================= */

function afficherMessage(message, type) {

    const element =
        document.getElementById("message");


    if (!element) {
        return;
    }


    element.textContent = message;

    element.className =
        `users-message ${type}`;

    element.style.display = "block";


    setTimeout(() => {

        element.style.display = "none";

    }, 4000);
}


/* =========================================================
   ECHAPPER HTML
========================================================= */

function echapperHTML(value) {

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   DECONNEXION
========================================================= */

async function deconnexion(event) {

    event.preventDefault();


    try {

        await fetch(
            "../../backend/auth/connexion.php",
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
            "Erreur de déconnexion :",
            error
        );

    } finally {

        window.location.href =
            "connexion.html";
    }
}