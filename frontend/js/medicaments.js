const API_URL =
    "../../backend/routes/medicaments.php";


const state = {

    products: [],

    categories: [],

    page: 1,

    perPage: 8,

    editingId: null

};


document.addEventListener(
    "DOMContentLoaded",
    async () => {

        bindEvents();

        await loadConnectedUser();

        await loadCategories();

        await loadMedicaments();

    }
);


/* =========================
   EVENEMENTS
========================= */

function bindEvents() {

    document
        .getElementById("searchInput")
        .addEventListener(
            "input",
            () => {

                state.page = 1;

                render();

            }
        );


    document
        .getElementById("globalSearch")
        .addEventListener(
            "input",
            function () {

                document
                    .getElementById("searchInput")
                    .value = this.value;

                state.page = 1;

                render();

            }
        );


    document
        .getElementById("categoryFilter")
        .addEventListener(
            "change",
            render
        );


    document
        .getElementById("statusFilter")
        .addEventListener(
            "change",
            render
        );


    document
        .getElementById("openCreateBtn")
        .addEventListener(
            "click",
            openModal
        );


    document
        .getElementById("closeModalBtn")
        .addEventListener(
            "click",
            closeModal
        );


    document
        .getElementById("cancelBtn")
        .addEventListener(
            "click",
            closeModal
        );


    document
        .getElementById("medicamentForm")
        .addEventListener(
            "submit",
            saveMedicament
        );
}

/**
 * =====================================================
 * INFORMATIONS DE L'ADMIN CONNECTÉ
 * =====================================================
 */

async function loadConnectedUser() {

    try {

        const response = await fetch(
            "../../backend/routes/dashboard.php",
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                },
                credentials: "include"
            }
        );

        if (!response.ok) {

            if (response.status === 401 || response.status === 403) {
                window.location.href = "connexion.html";
                return;
            }

            throw new Error(`Erreur HTTP ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || "Utilisateur non disponible.");
        }

        if (result.data && result.data.utilisateur) {
            updateAdminInfo(result.data.utilisateur);
        }

    } catch (error) {
        console.error("Erreur chargement utilisateur :", error);
    }
}

function updateAdminInfo(utilisateur) {

    if (!utilisateur) {
        return;
    }


    /*
     * Nom + prénom dans le profil.
     */

    const adminName =
        document.getElementById(
            "adminName"
        );


    if (adminName) {

        const nomComplet =
            `${utilisateur.prenom || ""} ${utilisateur.nom || ""}`
            .trim();


        adminName.textContent =
            nomComplet || "Admin";
    }


    /*
     * Prénom dans "Bonjour..."
     */

    const welcomeName =
        document.getElementById(
            "welcomeName"
        );


    if (welcomeName) {

        welcomeName.textContent =
            utilisateur.prenom || "Admin";
    }


    /*
     * Première lettre du prénom
     * dans l'avatar.
     */

    const avatar =
        document.querySelector(
            ".avatar"
        );


    if (avatar) {

        const prenom =
            utilisateur.prenom || "";


        if (prenom !== "") {

            avatar.textContent =
                prenom
                    .charAt(0)
                    .toUpperCase();

        } else {

            avatar.textContent = "A";
        }
    }
}



/* =========================
   CATEGORIES
========================= */

async function loadCategories() {

    const response =
        await fetch(
            `${API_URL}?action=categories`,
            {
                credentials: "include"
            }
        );

    const data =
        await response.json();


    state.categories =
        data.data || [];


    const filter =
        document.getElementById(
            "categoryFilter"
        );

    const select =
        document.getElementById(
            "categorie_id"
        );


    state.categories.forEach(
        category => {

            filter.insertAdjacentHTML(
                "beforeend",
                `
                <option value="${category.id}">
                    ${escapeHtml(category.nom)}
                </option>
                `
            );


            select.insertAdjacentHTML(
                "beforeend",
                `
                <option value="${category.id}">
                    ${escapeHtml(category.nom)}
                </option>
                `
            );

        }
    );
}


/* =========================
   MEDICAMENTS
========================= */

async function loadMedicaments() {

    const response =
        await fetch(
            `${API_URL}?action=list`,
            {
                credentials: "include"
            }
        );


    const data =
        await response.json();


    if (!data.success) {

        throw new Error(
            data.message
        );

    }


    state.products =
        data.data || [];


    render();
}


/* =========================
   FILTRE
========================= */

function getFilteredProducts() {

    const search =
        document
            .getElementById("searchInput")
            .value
            .toLowerCase()
            .trim();


    const category =
        document
            .getElementById("categoryFilter")
            .value;


    const status =
        document
            .getElementById("statusFilter")
            .value;


    return state.products.filter(
        product => {

            const searchOK =
                !search ||
                product.nom
                    .toLowerCase()
                    .includes(search) ||
                product.reference
                    .toLowerCase()
                    .includes(search);


            const categoryOK =
                !category ||
                String(product.categorie_id)
                === String(category);


            const statusOK =
                !status ||
                product.statut === status;


            return (
                searchOK &&
                categoryOK &&
                statusOK
            );
        }
    );
}


/* =========================
   AFFICHAGE
========================= */

function render() {

    const products =
        getFilteredProducts();


    const totalPages =
        Math.max(
            1,
            Math.ceil(
                products.length /
                state.perPage
            )
        );


    if (state.page > totalPages) {
        state.page = totalPages;
    }


    const start =
        (state.page - 1) *
        state.perPage;


    const pageProducts =
        products.slice(
            start,
            start + state.perPage
        );


    const tbody =
        document.getElementById(
            "medicamentsTableBody"
        );


    if (!pageProducts.length) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="6"
                    class="empty-state"
                >
                    Aucun médicament trouvé.
                </td>
            </tr>
        `;

    } else {

        tbody.innerHTML =
            pageProducts
                .map(renderRow)
                .join("");

    }


    document.getElementById(
        "resultsInfo"
    ).textContent =
        `${products.length} résultat${products.length > 1 ? "s" : ""}`;


    renderPagination(totalPages);
}


/* =========================
   LIGNE
========================= */

function renderRow(product) {

    const statusClass = {

        NORMAL: "status-normal",

        FAIBLE: "status-faible",

        RUPTURE: "status-rupture",

        EXPIRE: "status-expire"

    }[product.statut];


    const statusText = {

        NORMAL: "Normal",

        FAIBLE: "Faible",

        RUPTURE: "Rupture",

        EXPIRE: "Expiré"

    }[product.statut];


    const stockClass =
        Number(product.stock) === 0
            ? "stock-zero"
            : Number(product.stock)
                <= Number(product.seuilMinimum)
                ? "stock-low"
                : "";


    return `
        <tr>

            <td>

                <div class="product-name">
                    ${escapeHtml(product.nom)}
                </div>

                <small>
                    ${escapeHtml(product.reference)}
                </small>

            </td>


            <td>
                ${escapeHtml(
                    product.categorie_nom
                )}
            </td>


            <td class="${stockClass}">
                ${product.stock}
            </td>


            <td>
                ${formatDate(
                    product.dateExpiration
                )}
            </td>


            <td>

                <span
                    class="status-badge ${statusClass}"
                >
                    ${statusText}
                </span>

            </td>


            <td>

                <div class="action-buttons">

                    <button
                        class="icon-button"
                        onclick="viewProduct(${product.id})"
                        title="Voir"
                    >
                        👁
                    </button>


                    <button
                        class="icon-button"
                        onclick="editProduct(${product.id})"
                        title="Modifier"
                    >
                        ✎
                    </button>


                    <button
                        class="icon-button"
                        onclick="deleteProduct(${product.id})"
                        title="Supprimer"
                    >
                        🗑
                    </button>

                </div>

            </td>

        </tr>
    `;
}


/* =========================
   ACTIONS
========================= */

window.viewProduct =
    function(id) {

        window.location.href =
            `medicament-detail.html?id=${id}`;

    };


window.editProduct =
    function(id) {

        const product =
            state.products.find(
                p => Number(p.id) === Number(id)
            );


        if (!product) return;


        state.editingId = id;


        document.getElementById(
            "modalTitle"
        ).textContent =
            "Modifier le médicament";


        document.getElementById(
            "medicamentId"
        ).value = product.id;


        document.getElementById(
            "nom"
        ).value = product.nom;


        document.getElementById(
            "reference"
        ).value = product.reference;


        document.getElementById(
            "categorie_id"
        ).value = product.categorie_id;


        document.getElementById(
            "dosage"
        ).value =
            product.dosage || "";


        document.getElementById(
            "forme"
        ).value =
            product.forme || "";


        document.getElementById(
            "prixAchat"
        ).value =
            product.prixAchat;


        document.getElementById(
            "prixVente"
        ).value =
            product.prixVente;


        document.getElementById(
            "seuilMinimum"
        ).value =
            product.seuilMinimum;


        document.getElementById(
            "description"
        ).value =
            product.description || "";


        document
            .getElementById("modalOverlay")
            .classList.remove("hidden");
    };


window.deleteProduct =
    async function(id) {

        if (
            !confirm(
                "Voulez-vous supprimer ce médicament ?"
            )
        ) {
            return;
        }


        const response =
            await fetch(
                `${API_URL}?id=${id}`,
                {
                    method: "DELETE",
                    credentials: "include"
                }
            );


        const data =
            await response.json();


        if (!data.success) {

            alert(data.message);

            return;
        }


        await loadMedicaments();
    };


/* =========================
   MODAL
========================= */

function openModal() {

    state.editingId = null;

    document
        .getElementById("medicamentForm")
        .reset();


    document.getElementById(
        "modalTitle"
    ).textContent =
        "Ajouter un médicament";


    document
        .getElementById("modalOverlay")
        .classList.remove("hidden");
}


function closeModal() {

    document
        .getElementById("modalOverlay")
        .classList.add("hidden");
}


/* =========================
   ENREGISTREMENT
========================= */

async function saveMedicament(event) {

    event.preventDefault();


    const payload = {

        reference:
            document.getElementById(
                "reference"
            ).value.trim(),

        nom:
            document.getElementById(
                "nom"
            ).value.trim(),

        description:
            document.getElementById(
                "description"
            ).value.trim(),

        forme:
            document.getElementById(
                "forme"
            ).value.trim(),

        dosage:
            document.getElementById(
                "dosage"
            ).value.trim(),

        prixAchat:
            Number(
                document.getElementById(
                    "prixAchat"
                ).value
            ),

        prixVente:
            Number(
                document.getElementById(
                    "prixVente"
                ).value
            ),

        seuilMinimum:
            Number(
                document.getElementById(
                    "seuilMinimum"
                ).value
            ),

        categorie_id:
            Number(
                document.getElementById(
                    "categorie_id"
                ).value
            )
    };


    const isEdit =
        state.editingId !== null;


    const url =
        isEdit
            ? `${API_URL}?id=${state.editingId}`
            : API_URL;


    const response =
        await fetch(
            url,
            {
                method:
                    isEdit
                        ? "PUT"
                        : "POST",

                credentials: "include",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify(payload)
            }
        );


    const data =
        await response.json();


    if (!data.success) {

        document.getElementById(
            "formMessage"
        ).textContent =
            data.message;

        return;
    }


    closeModal();

    await loadMedicaments();
}


/* =========================
   OUTILS
========================= */

function formatDate(value) {

    if (!value) return "—";

    const date =
        new Date(
            value + "T00:00:00"
        );


    return date.toLocaleDateString(
        "fr-FR"
    );
}


function escapeHtml(value) {

    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}