const API_URL = "../../backend/routes/commandes.php";

let commandes = [];
let fournisseurs = [];
let produits = [];


// =====================================================
// ELEMENTS
// =====================================================

const orderModal =
    document.getElementById("orderModal");

const detailsModal =
    document.getElementById("detailsModal");

const orderForm =
    document.getElementById("orderForm");

const ordersTableBody =
    document.getElementById("ordersTableBody");

const emptyOrders =
    document.getElementById("emptyOrders");

const orderCount =
    document.getElementById("orderCount");

const message =
    document.getElementById("message");

const orderId =
    document.getElementById("orderId");

const orderDate =
    document.getElementById("orderDate");

const orderSupplier =
    document.getElementById("orderSupplier");

const orderStatus =
    document.getElementById("orderStatus");

const orderProducts =
    document.getElementById("orderProducts");

const orderTotal =
    document.getElementById("orderTotal");

const modalTitle =
    document.getElementById("modalTitle");

const searchOrder =
    document.getElementById("searchOrder");

const filterStatus =
    document.getElementById("filterStatus");

const filterSupplier =
    document.getElementById("filterSupplier");


// =====================================================
// CHARGEMENT INITIAL
// =====================================================

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        await loadOptions();

        await loadOrders();

        loadAdminInfo();

    }
);


// =====================================================
// OPTIONS
// =====================================================

async function loadOptions() {

    try {

        const response =
            await fetch(
                `${API_URL}?action=options`,
                {
                    method: "GET",
                    credentials: "include"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            throw new Error(
                result.message ||
                "Erreur lors du chargement des données."
            );
        }


        fournisseurs =
            result.data.fournisseurs || [];

        produits =
            result.data.produits || [];


        fillSupplierSelect();

        fillSupplierFilter();

    } catch (error) {

        console.error(error);

        showMessage(
            error.message,
            "error"
        );
    }
}


// =====================================================
// FOURNISSEURS
// =====================================================

function fillSupplierSelect() {

    orderSupplier.innerHTML = `
        <option value="">
            Sélectionner un fournisseur
        </option>
    `;


    fournisseurs.forEach(
        fournisseur => {

            const option =
                document.createElement("option");

            option.value =
                fournisseur.id;

            option.textContent =
                fournisseur.nom;

            orderSupplier.appendChild(option);

        }
    );
}


function fillSupplierFilter() {

    filterSupplier.innerHTML = `
        <option value="">
            Tous les fournisseurs
        </option>
    `;


    fournisseurs.forEach(
        fournisseur => {

            const option =
                document.createElement("option");

            option.value =
                fournisseur.id;

            option.textContent =
                fournisseur.nom;

            filterSupplier.appendChild(option);

        }
    );
}


// =====================================================
// COMMANDES
// =====================================================

async function loadOrders() {

    try {

        const response =
            await fetch(
                API_URL,
                {
                    method: "GET",
                    credentials: "include"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            throw new Error(
                result.message ||
                "Erreur lors du chargement des commandes."
            );
        }


        commandes =
            result.data.commandes || [];


        applyFilters();

    } catch (error) {

        console.error(error);

        ordersTableBody.innerHTML = "";

        emptyOrders.classList.add(
            "visible"
        );

        updateOrderCount(0);

        showMessage(
            error.message,
            "error"
        );
    }
}


// =====================================================
// AFFICHER COMMANDES
// =====================================================

function displayOrders(list) {

    ordersTableBody.innerHTML = "";


    updateOrderCount(
        list.length
    );


    if (list.length === 0) {

        emptyOrders.classList.add(
            "visible"
        );

        return;
    }


    emptyOrders.classList.remove(
        "visible"
    );


    list.forEach(
        commande => {

            const row =
                document.createElement("tr");


            row.innerHTML = `

                <td class="order-id">
                    ${escapeHtml(commande.id)}
                </td>

                <td>
                    ${formatDate(
                        commande.dateCommande
                    )}
                </td>

                <td class="order-supplier">
                    ${escapeHtml(
                        commande.fournisseur
                    )}
                </td>

                <td class="order-amount">
                    ${formatPrice(
                        commande.montantTotal
                    )}
                </td>

                <td>

                    <span
                        class="status-badge ${getStatusClass(
                            commande.statut
                        )}"
                    >
                        ${getStatusLabel(
                            commande.statut
                        )}
                    </span>

                </td>

                <td>

                    <div class="order-actions">

                        <button
                            type="button"
                            class="order-action-btn view-order-btn"
                            title="Voir les détails"
                        >
                            👁️
                        </button>

                        <button
                            type="button"
                            class="order-action-btn edit-order-btn"
                            title="Modifier"
                        >
                            ✏️
                        </button>

                        <button
                            type="button"
                            class="order-action-btn delete-order-btn"
                            title="Supprimer"
                        >
                            🗑️
                        </button>

                    </div>

                </td>
            `;


            row
                .querySelector(".view-order-btn")
                .addEventListener(
                    "click",
                    () => viewOrder(
                        commande.id
                    )
                );


            row
                .querySelector(".edit-order-btn")
                .addEventListener(
                    "click",
                    () => editOrder(
                        commande.id
                    )
                );


            row
                .querySelector(".delete-order-btn")
                .addEventListener(
                    "click",
                    () => deleteOrder(
                        commande.id
                    )
                );


            ordersTableBody.appendChild(row);

        }
    );
}


// =====================================================
// FILTRES
// =====================================================

function applyFilters() {

    const search =
        searchOrder.value
            .trim()
            .toLowerCase();


    const status =
        filterStatus.value;


    const supplier =
        filterSupplier.value;


    const filtered =
        commandes.filter(
            commande => {

                const text =
                    `${commande.id} ${commande.fournisseur} ${commande.statut}`
                        .toLowerCase();


                const matchesSearch =
                    search === "" ||
                    text.includes(search);


                const matchesStatus =
                    status === "" ||
                    commande.statut === status;


                const matchesSupplier =
                    supplier === "" ||
                    String(
                        commande.fournisseur_id
                    ) === String(supplier);


                return (
                    matchesSearch &&
                    matchesStatus &&
                    matchesSupplier
                );
            }
        );


    displayOrders(filtered);
}


searchOrder.addEventListener(
    "input",
    applyFilters
);

filterStatus.addEventListener(
    "change",
    applyFilters
);

filterSupplier.addEventListener(
    "change",
    applyFilters
);


// =====================================================
// AJOUT COMMANDE
// =====================================================

document
    .getElementById("addOrderBtn")
    .addEventListener(
        "click",
        openAddOrder
    );


function openAddOrder() {

    orderForm.reset();

    orderId.value = "";

    modalTitle.textContent =
        "Nouvelle commande";


    orderDate.value =
        getTodayDate();


    orderStatus.value =
        "EN_ATTENTE";


    orderProducts.innerHTML = "";


    addProductRow();


    calculateTotal();


    orderModal.style.display =
        "flex";
}


// =====================================================
// AJOUT LIGNE PRODUIT
// =====================================================

document
    .getElementById("addProductBtn")
    .addEventListener(
        "click",
        () => addProductRow()
    );


function addProductRow(
    selectedId = "",
    quantity = 1,
    price = ""
) {

    const row =
        document.createElement("div");


    row.className =
        "product-row";


    row.innerHTML = `

        <div>

            <label>
                Produit
            </label>

            <select class="product-select">

                <option value="">
                    Sélectionner un produit
                </option>

            </select>

        </div>


        <div>

            <label>
                Quantité
            </label>

            <input
                type="number"
                class="product-quantity"
                min="1"
                step="1"
                value="${quantity}"
            >

        </div>


        <div>

            <label>
                Prix unitaire
            </label>

            <input
                type="number"
                class="product-price"
                min="0"
                step="0.01"
                value="${price}"
            >

        </div>


        <div>

            <label>
                &nbsp;
            </label>

            <button
                type="button"
                class="remove-product-btn"
                title="Supprimer"
            >
                ×
            </button>

        </div>
    `;


    orderProducts.appendChild(row);


    const select =
        row.querySelector(
            ".product-select"
        );


    produits.forEach(
        produit => {

            const option =
                document.createElement("option");

            option.value =
                produit.id;

            option.textContent =
                `${produit.reference} - ${produit.nom}`;

            option.dataset.price =
                produit.prixAchat;

            select.appendChild(option);

        }
    );


    if (selectedId !== "") {

        select.value =
            selectedId;

    }


    select.addEventListener(
        "change",
        function () {

            const selectedOption =
                this.options[
                    this.selectedIndex
                ];


            if (
                selectedOption &&
                selectedOption.dataset.price
            ) {

                row.querySelector(
                    ".product-price"
                ).value =
                    selectedOption.dataset.price;

            }


            calculateTotal();

        }
    );


    row
        .querySelector(".product-quantity")
        .addEventListener(
            "input",
            calculateTotal
        );


    row
        .querySelector(".product-price")
        .addEventListener(
            "input",
            calculateTotal
        );


    row
        .querySelector(".remove-product-btn")
        .addEventListener(
            "click",
            function () {

                row.remove();

                calculateTotal();

            }
        );


    if (
        selectedId !== "" &&
        price === ""
    ) {

        const selectedOption =
            select.options[
                select.selectedIndex
            ];


        if (
            selectedOption &&
            selectedOption.dataset.price
        ) {

            row.querySelector(
                ".product-price"
            ).value =
                selectedOption.dataset.price;

        }
    }


    calculateTotal();
}


// =====================================================
// TOTAL
// =====================================================

function calculateTotal() {

    let total = 0;


    const rows =
        orderProducts.querySelectorAll(
            ".product-row"
        );


    rows.forEach(
        row => {

            const quantity =
                parseFloat(
                    row.querySelector(
                        ".product-quantity"
                    ).value
                ) || 0;


            const price =
                parseFloat(
                    row.querySelector(
                        ".product-price"
                    ).value
                ) || 0;


            total +=
                quantity * price;

        }
    );


    orderTotal.textContent =
        formatPrice(total);
}


// =====================================================
// ENREGISTRER
// =====================================================

orderForm.addEventListener(
    "submit",
    async function (event) {

        event.preventDefault();


        const rows =
            orderProducts.querySelectorAll(
                ".product-row"
            );


        if (rows.length === 0) {

            showMessage(
                "Ajoutez au moins un produit.",
                "error"
            );

            return;
        }


        const details = [];

        let invalid = false;


        rows.forEach(
            row => {

                const produitId =
                    parseInt(
                        row.querySelector(
                            ".product-select"
                        ).value,
                        10
                    );


                const quantite =
                    parseInt(
                        row.querySelector(
                            ".product-quantity"
                        ).value,
                        10
                    );


                const prix =
                    parseFloat(
                        row.querySelector(
                            ".product-price"
                        ).value
                    );


                if (
                    !produitId ||
                    quantite <= 0 ||
                    Number.isNaN(prix) ||
                    prix < 0
                ) {

                    invalid = true;

                    return;
                }


                details.push({
                    produit_id: produitId,
                    quantite: quantite,
                    prixUnitaire: prix
                });

            }
        );


        if (invalid) {

            showMessage(
                "Vérifiez les produits, quantités et prix.",
                "error"
            );

            return;
        }


        const data = {

            dateCommande:
                orderDate.value,

            statut:
                orderStatus.value,

            fournisseur_id:
                parseInt(
                    orderSupplier.value,
                    10
                ),

            details:
                details
        };


        if (!data.fournisseur_id) {

            showMessage(
                "Sélectionnez un fournisseur.",
                "error"
            );

            return;
        }


        try {

            const id =
                orderId.value;


            let response;


            if (id) {

                response =
                    await fetch(
                        `${API_URL}?id=${id}`,
                        {
                            method: "PUT",

                            credentials: "include",

                            headers: {
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(data)
                        }
                    );

            } else {

                response =
                    await fetch(
                        API_URL,
                        {
                            method: "POST",

                            credentials: "include",

                            headers: {
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(data)
                        }
                    );

            }


            const result =
                await response.json();


            if (
                !response.ok ||
                !result.success
            ) {

                throw new Error(
                    result.message ||
                    "Erreur lors de l'enregistrement."
                );
            }


            closeOrderModal();


            showMessage(
                result.message,
                "success"
            );


            await loadOrders();

        } catch (error) {

            console.error(error);

            showMessage(
                error.message,
                "error"
            );
        }

    }
);


// =====================================================
// MODIFIER
// =====================================================

async function editOrder(id) {

    try {

        const response =
            await fetch(
                `${API_URL}?action=details&id=${id}`,
                {
                    method: "GET",
                    credentials: "include"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            throw new Error(
                result.message ||
                "Commande introuvable."
            );
        }


        const commande =
            result.data.commande;


        const details =
            result.data.details;


        orderId.value =
            commande.id;

        orderDate.value =
            commande.dateCommande;

        orderSupplier.value =
            commande.fournisseur_id;

        orderStatus.value =
            commande.statut;


        modalTitle.textContent =
            "Modifier la commande";


        orderProducts.innerHTML = "";


        details.forEach(
            detail => {

                addProductRow(
                    detail.produit_id,
                    detail.quantite,
                    detail.prixUnitaire
                );

            }
        );


        calculateTotal();


        orderModal.style.display =
            "flex";

    } catch (error) {

        console.error(error);

        showMessage(
            error.message,
            "error"
        );
    }
}


// =====================================================
// DETAILS
// =====================================================

async function viewOrder(id) {

    const detailsModalContent =
        document.getElementById(
            "orderDetailsContent"
        );


    detailsModalContent.innerHTML =
        "Chargement...";


    detailsModal.style.display =
        "flex";


    try {

        const response =
            await fetch(
                `${API_URL}?action=details&id=${id}`,
                {
                    method: "GET",
                    credentials: "include"
                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            throw new Error(
                result.message ||
                "Erreur lors du chargement."
            );
        }


        const commande =
            result.data.commande;


        const details =
            result.data.details;


        let rows = "";


        details.forEach(
            detail => {

                rows += `

                    <tr>

                        <td>
                            ${escapeHtml(
                                detail.reference
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                detail.produit
                            )}
                        </td>

                        <td>
                            ${detail.quantite}
                        </td>

                        <td>
                            ${formatPrice(
                                detail.prixUnitaire
                            )}
                        </td>

                        <td>
                            ${formatPrice(
                                detail.sousTotal
                            )}
                        </td>

                    </tr>
                `;

            }
        );


        detailsModalContent.innerHTML = `

            <div class="details-info">

                <div class="details-info-item">

                    <span>
                        Numéro
                    </span>

                    <strong>
                        #${escapeHtml(
                            commande.id
                        )}
                    </strong>

                </div>


                <div class="details-info-item">

                    <span>
                        Date
                    </span>

                    <strong>
                        ${formatDate(
                            commande.dateCommande
                        )}
                    </strong>

                </div>


                <div class="details-info-item">

                    <span>
                        Fournisseur
                    </span>

                    <strong>
                        ${escapeHtml(
                            commande.fournisseur
                        )}
                    </strong>

                </div>


                <div class="details-info-item">

                    <span>
                        Statut
                    </span>

                    <strong>
                        ${getStatusLabel(
                            commande.statut
                        )}
                    </strong>

                </div>

            </div>


            <table class="details-products">

                <thead>

                    <tr>

                        <th>
                            Référence
                        </th>

                        <th>
                            Produit
                        </th>

                        <th>
                            Quantité
                        </th>

                        <th>
                            Prix unitaire
                        </th>

                        <th>
                            Sous-total
                        </th>

                    </tr>

                </thead>


                <tbody>

                    ${rows}

                </tbody>

            </table>


            <div class="details-total">

                <span>
                    Total :
                </span>

                <strong>
                    ${formatPrice(
                        commande.montantTotal
                    )}
                </strong>

            </div>
        `;

    } catch (error) {

        console.error(error);

        detailsModalContent.innerHTML = `
            <p>
                ${escapeHtml(
                    error.message
                )}
            </p>
        `;
    }
}


// =====================================================
// SUPPRIMER
// =====================================================

async function deleteOrder(id) {

    const commande =
        commandes.find(
            item =>
                Number(item.id) ===
                Number(id)
        );


    if (!commande) {
        return;
    }


    const confirmation =
        confirm(
            `Voulez-vous vraiment supprimer la commande #${commande.id} ?`
        );


    if (!confirmation) {
        return;
    }


    try {

        const response =
            await fetch(
                `${API_URL}?id=${id}`,
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

            throw new Error(
                result.message ||
                "Impossible de supprimer la commande."
            );
        }


        showMessage(
            result.message,
            "success"
        );


        await loadOrders();

    } catch (error) {

        console.error(error);

        showMessage(
            error.message,
            "error"
        );
    }
}


// =====================================================
// FERMETURE MODALS
// =====================================================

document
    .getElementById("closeModalBtn")
    .addEventListener(
        "click",
        closeOrderModal
    );


document
    .getElementById("cancelOrderBtn")
    .addEventListener(
        "click",
        closeOrderModal
    );


document
    .getElementById("closeDetailsModalBtn")
    .addEventListener(
        "click",
        closeDetailsModal
    );


function closeOrderModal() {

    orderModal.style.display =
        "none";

    orderForm.reset();

    orderId.value = "";

    orderProducts.innerHTML = "";
}


function closeDetailsModal() {

    detailsModal.style.display =
        "none";
}


orderModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target === orderModal
        ) {

            closeOrderModal();
        }

    }
);


detailsModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target === detailsModal
        ) {

            closeDetailsModal();
        }

    }
);


// =====================================================
// DECONNEXION
// =====================================================

document
    .getElementById("logoutBtn")
    .addEventListener(
        "click",
        async function (event) {

            event.preventDefault();


            try {

                await fetch(
                    "../../backend/auth/connexion.php",
                    {
                        method: "POST",

                        credentials: "include",

                        headers: {
                            "Content-Type":
                                "application/x-www-form-urlencoded"
                        },

                        body:
                            "action=logout"
                    }
                );

            } catch (error) {

                console.error(error);

            } finally {

                window.location.href =
                    "connexion.html";
            }

        }
    );


// =====================================================
// INFORMATIONS ADMIN
// =====================================================

async function loadAdminInfo() {

    try {

        const response =
            await fetch(
                "../../backend/auth/session.php",
                {
                    method: "GET",
                    credentials: "include"
                }
            );


        if (!response.ok) {
            return;
        }


        const result =
            await response.json();


        if (
            !result.success ||
            !result.data ||
            !result.data.utilisateur
        ) {
            return;
        }


        const utilisateur =
            result.data.utilisateur;


        const name =
            `${utilisateur.prenom || ""} ${utilisateur.nom || ""}`
                .trim();


        if (name) {

            document
                .getElementById("adminName")
                .textContent = name;


            document
                .getElementById("adminAvatar")
                .textContent =
                name.charAt(0).toUpperCase();

        }

    } catch (error) {

        console.error(
            "Impossible de charger le profil.",
            error
        );
    }
}


// =====================================================
// UTILITAIRES
// =====================================================

function updateOrderCount(count) {

    orderCount.textContent =
        `${count} commande${count > 1 ? "s" : ""}`;
}


function getTodayDate() {

    const today =
        new Date();


    const year =
        today.getFullYear();


    const month =
        String(
            today.getMonth() + 1
        ).padStart(2, "0");


    const day =
        String(
            today.getDate()
        ).padStart(2, "0");


    return `${year}-${month}-${day}`;
}


function formatDate(date) {

    if (!date) {
        return "-";
    }


    const parts =
        date.split("-");


    if (parts.length !== 3) {
        return date;
    }


    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}


function formatPrice(value) {

    const number =
        parseFloat(value) || 0;


    return number.toLocaleString(
        "fr-FR",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    ) + " €";
}


function getStatusLabel(status) {

    const labels = {

        EN_ATTENTE:
            "En attente",

        VALIDEE:
            "Validée",

        RECUE:
            "Reçue",

        ANNULEE:
            "Annulée"

    };


    return labels[status] || status;
}


function getStatusClass(status) {

    const classes = {

        EN_ATTENTE:
            "status-en-attente",

        VALIDEE:
            "status-validee",

        RECUE:
            "status-recue",

        ANNULEE:
            "status-annulee"

    };


    return classes[status] || "";
}


function escapeHtml(value) {

    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}


function showMessage(
    text,
    type
) {

    message.textContent =
        text;

    message.className =
        `commandes-message ${type}`;

    message.style.display =
        "block";


    setTimeout(
        function () {

            message.style.display =
                "none";

        },
        4000
    );
}