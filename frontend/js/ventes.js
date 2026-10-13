/* =========================================================
   PHARMASTOCK - GESTION DES VENTES
========================================================= */

const API_URL = "../../backend/routes/ventes.php";

let products = [];
let cart = [];
let selectedProduct = null;

let salesHistory = [];
let currentPage = 1;

const ITEMS_PER_PAGE = 10;


/* =========================================================
   INITIALISATION
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    loadProducts();
    loadSalesHistory();
    setupEvents();
    loadConnectedUser();

});


/* =========================================================
   UTILISATEUR CONNECTE
========================================================= */

async function loadConnectedUser() {

    try {

        const response = await fetch(
            "../../backend/auth/session.php",
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
            const utilisateur = result.data.utilisateur;

            const nameElement = document.getElementById("adminName");
            const avatarElement = document.getElementById("userAvatar");
            const roleElement = document.getElementById("userRole");

            const fullName = `${utilisateur.prenom || ""} ${utilisateur.nom || ""}`.trim();

            if (nameElement) {
                nameElement.textContent = fullName || "Admin";
            }

            if (avatarElement) {
                avatarElement.textContent = (fullName || "A").charAt(0).toUpperCase();
            }

            if (roleElement && utilisateur.role) {
                roleElement.textContent = window.PharmaSession
                    ? window.PharmaSession.roleLabel(utilisateur.role)
                    : utilisateur.role;
            }
        }

    } catch (error) {
        console.error("Erreur chargement utilisateur :", error);
    }

}


/* =========================================================
   CHARGER LES PRODUITS
========================================================= */

async function loadProducts() {

    try {

        const response = await fetch(
            `${API_URL}?action=products`,
            {
                method: "GET",
                credentials: "include",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message ||
                "Impossible de charger les médicaments."
            );

        }

        products =
            result.data.products ||
            result.data ||
            [];

    } catch (error) {

        console.error(error);

        showProductMessage(
            "Impossible de charger les médicaments.",
            "error"
        );

    }

}


/* =========================================================
   RECHERCHE PRODUIT
========================================================= */

function searchProducts(search) {

    const term =
        search
            .trim()
            .toLowerCase();

    const container =
        document.getElementById("productResults");

    if (!term) {

        container.innerHTML = "";

        return;
    }


    const results =
        products
            .filter(product => {

                const name =
                    String(product.nom || "")
                        .toLowerCase();

                const reference =
                    String(product.reference || "")
                        .toLowerCase();

                return (
                    name.includes(term) ||
                    reference.includes(term)
                );

            })
            .slice(0, 8);


    if (results.length === 0) {

        container.innerHTML = `
            <div class="product-result">
                <div class="result-info">
                    <strong>Aucun médicament trouvé</strong>
                    <small>Vérifiez le nom ou la référence.</small>
                </div>
            </div>
        `;

        return;
    }


    container.innerHTML =
        results.map(product => {

            const stock =
                Number(
                    product.stockTotal ??
                    product.stock ??
                    0
                );

            const price =
                Number(
                    product.prixVente ??
                    product.prixvente ??
                    0
                );


            return `

                <div
                    class="product-result"
                    data-id="${Number(product.id)}"
                >

                    <div class="result-info">

                        <strong>
                            ${escapeHtml(product.nom)}
                        </strong>

                        <small>
                            Réf. ${escapeHtml(product.reference)}
                        </small>

                    </div>


                    <div class="result-stock">

                        <strong>
                            ${formatNumber(stock)}
                        </strong>

                        <small>
                            ${formatCurrency(price)}
                        </small>

                    </div>

                </div>

            `;

        }).join("");


    container
        .querySelectorAll(".product-result[data-id]")
        .forEach(element => {

            element.addEventListener(
                "click",
                () => {

                    const id =
                        Number(element.dataset.id);

                    selectProduct(id);

                }
            );

        });

}


/* =========================================================
   SELECTIONNER PRODUIT
========================================================= */

function selectProduct(id) {

    const product =
        products.find(
            item => Number(item.id) === id
        );

    if (!product) {
        return;
    }


    const stock =
        Number(
            product.stockTotal ??
            product.stock ??
            0
        );

    const price =
        Number(
            product.prixVente ??
            product.prixvente ??
            0
        );


    selectedProduct = {
        id: Number(product.id),
        reference: product.reference,
        nom: product.nom,
        prixVente: price,
        stockTotal: stock
    };


    document
        .getElementById("selectedProduct")
        .classList.remove("hidden");

    document
        .getElementById("quantitySection")
        .classList.remove("hidden");


    document.getElementById(
        "selectedProductName"
    ).textContent = product.nom;


    document.getElementById(
        "selectedProductReference"
    ).textContent =
        `Référence : ${product.reference}`;


    document.getElementById(
        "selectedProductStock"
    ).textContent =
        formatNumber(stock);


    document.getElementById(
        "selectedProductPrice"
    ).textContent =
        formatCurrency(price);


    document.getElementById(
        "quantityHelp"
    ).textContent =
        `Quantité disponible : ${formatNumber(stock)}`;


    const quantity =
        document.getElementById("quantityInput");

    quantity.value = 1;

    quantity.max = stock;


    document.getElementById(
        "productResults"
    ).innerHTML = "";


    document.getElementById(
        "productSearch"
    ).value = "";


    if (stock <= 0) {

        showProductMessage(
            "Ce médicament est en rupture de stock.",
            "error"
        );

        document.getElementById(
            "addToCartButton"
        ).disabled = true;

    } else {

        clearProductMessage();

        document.getElementById(
            "addToCartButton"
        ).disabled = false;

    }

}


/* =========================================================
   AJOUT AU PANIER
========================================================= */

function addToCart() {

    if (!selectedProduct) {

        showProductMessage(
            "Sélectionnez d'abord un médicament.",
            "error"
        );

        return;
    }


    const quantity =
        Number(
            document.getElementById(
                "quantityInput"
            ).value
        );


    if (!Number.isInteger(quantity) || quantity <= 0) {

        showProductMessage(
            "La quantité doit être supérieure à zéro.",
            "error"
        );

        return;
    }


    if (quantity > selectedProduct.stockTotal) {

        showProductMessage(
            "La quantité demandée dépasse le stock disponible.",
            "error"
        );

        return;
    }


    const existing =
        cart.find(
            item =>
                item.produit_id === selectedProduct.id
        );


    if (existing) {

        const newQuantity =
            existing.quantite + quantity;


        if (newQuantity > selectedProduct.stockTotal) {

            showProductMessage(
                "La quantité totale dépasse le stock disponible.",
                "error"
            );

            return;
        }


        existing.quantite =
            newQuantity;

    } else {

        cart.push({

            produit_id:
                selectedProduct.id,

            reference:
                selectedProduct.reference,

            nom:
                selectedProduct.nom,

            prixUnitaire:
                selectedProduct.prixVente,

            quantite:
                quantity,

            stockDisponible:
                selectedProduct.stockTotal

        });

    }


    renderCart();

    showProductMessage(
        "Médicament ajouté au panier.",
        "success"
    );

}


/* =========================================================
   AFFICHER PANIER
========================================================= */

function renderCart() {

    const body =
        document.getElementById("cartBody");

    const empty =
        document.getElementById("cartEmpty");

    const container =
        document.getElementById("cartContainer");


    const cartCount =
        document.getElementById("cartCount");


    if (cart.length === 0) {

        empty.classList.remove("hidden");

        container.classList.add("hidden");

        cartCount.textContent =
            "0 article";

        return;
    }


    empty.classList.add("hidden");

    container.classList.remove("hidden");


    body.innerHTML =
        cart.map((item, index) => {

            const total =
                item.quantite *
                item.prixUnitaire;


            return `

                <tr>

                    <td>

                        <div class="cart-product">

                            <strong>
                                ${escapeHtml(item.nom)}
                            </strong>

                            <small>
                                ${escapeHtml(item.reference)}
                            </small>

                        </div>

                    </td>


                    <td>
                        ${formatCurrency(item.prixUnitaire)}
                    </td>


                    <td>

                        <div class="cart-quantity">

                            <button
                                type="button"
                                data-action="minus"
                                data-index="${index}"
                            >
                                −
                            </button>

                            <strong>
                                ${item.quantite}
                            </strong>

                            <button
                                type="button"
                                data-action="plus"
                                data-index="${index}"
                            >
                                +
                            </button>

                        </div>

                    </td>


                    <td>
                        <strong>
                            ${formatCurrency(total)}
                        </strong>
                    </td>


                    <td>

                        <button
                            type="button"
                            class="remove-cart"
                            data-action="remove"
                            data-index="${index}"
                            title="Supprimer"
                        >
                            ×
                        </button>

                    </td>

                </tr>

            `;

        }).join("");


    const totalQuantity =
        cart.reduce(
            (sum, item) =>
                sum + item.quantite,
            0
        );


    const total =
        cart.reduce(
            (sum, item) =>
                sum +
                (
                    item.quantite *
                    item.prixUnitaire
                ),
            0
        );


    document.getElementById(
        "summaryQuantity"
    ).textContent =
        formatNumber(totalQuantity);


    document.getElementById(
        "cartTotal"
    ).textContent =
        formatCurrency(total);


    cartCount.textContent =
        `${formatNumber(totalQuantity)} article${totalQuantity > 1 ? "s" : ""}`;


    body
        .querySelectorAll("button[data-action]")
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    const index =
                        Number(button.dataset.index);

                    const action =
                        button.dataset.action;

                    updateCartItem(
                        index,
                        action
                    );

                }
            );

        });

}


/* =========================================================
   MODIFIER PANIER
========================================================= */

function updateCartItem(index, action) {

    const item = cart[index];

    if (!item) {
        return;
    }


    if (action === "plus") {

        if (
            item.quantite >=
            item.stockDisponible
        ) {

            alert(
                "La quantité demandée dépasse le stock disponible."
            );

            return;
        }

        item.quantite++;

    }


    if (action === "minus") {

        item.quantite--;

        if (item.quantite <= 0) {

            cart.splice(index, 1);

        }

    }


    if (action === "remove") {

        cart.splice(index, 1);

    }


    renderCart();

}


/* =========================================================
   VIDER PANIER
========================================================= */

function clearCart() {

    if (cart.length === 0) {
        return;
    }


    if (
        !confirm(
            "Voulez-vous vraiment vider le panier ?"
        )
    ) {
        return;
    }


    cart = [];

    renderCart();

}


/* =========================================================
   VALIDER VENTE
========================================================= */

async function validateSale() {

    if (cart.length === 0) {

        alert(
            "Le panier est vide."
        );

        return;
    }


    const total =
        cart.reduce(
            (sum, item) =>
                sum +
                item.quantite *
                item.prixUnitaire,
            0
        );


    const confirmed =
        confirm(
            `Confirmer la vente de ${formatCurrency(total)} ?`
        );


    if (!confirmed) {
        return;
    }


    const button =
        document.getElementById(
            "validateSaleButton"
        );


    button.disabled = true;

    button.textContent =
        "Enregistrement...";


    try {

        const response =
            await fetch(
                API_URL,
                {
                    method: "POST",

                    credentials: "include",

                    headers: {
                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json"
                    },

                    body: JSON.stringify({

                        items:
                            cart.map(item => ({

                                produit_id:
                                    item.produit_id,

                                quantite:
                                    item.quantite,

                                prixUnitaire:
                                    item.prixUnitaire

                            }))

                    })

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
                "Impossible d'enregistrer la vente."
            );

        }


        alert(
            `Vente #${result.data.vente.id} enregistrée avec succès.`
        );


        cart = [];

        renderCart();

        selectedProduct = null;


        document
            .getElementById(
                "selectedProduct"
            )
            .classList.add("hidden");


        document
            .getElementById(
                "quantitySection"
            )
            .classList.add("hidden");


        document.getElementById(
            "productMessage"
        ).textContent = "";


        await loadProducts();

        await loadSalesHistory();


    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "Une erreur est survenue."
        );

    } finally {

        button.disabled = false;

        button.textContent =
            "✓ Valider la vente";

    }

}


/* =========================================================
   HISTORIQUE
========================================================= */

async function loadSalesHistory() {

    const body =
        document.getElementById(
            "salesHistoryBody"
        );


    body.innerHTML = `

        <tr>

            <td
                colspan="6"
                class="table-loading"
            >
                Chargement...
            </td>

        </tr>

    `;


    try {

        const response =
            await fetch(
                `${API_URL}?action=history`,
                {
                    method: "GET",
                    credentials: "include",
                    headers: {
                        "Accept": "application/json"
                    }
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
                "Impossible de charger l'historique."
            );

        }


        salesHistory =
            result.data.ventes ||
            result.data ||
            [];


        currentPage = 1;

        renderSalesHistory();

    } catch (error) {

        console.error(error);

        body.innerHTML = `

            <tr>

                <td
                    colspan="6"
                    class="table-empty"
                >
                    Impossible de charger l'historique.
                </td>

            </tr>

        `;

    }

}


/* =========================================================
   AFFICHER HISTORIQUE
========================================================= */

function getHistoryPeriodFilter(dateValue, period) {
    if (!dateValue || !period || period === "all") {
        return true;
    }

    const saleDate = new Date(String(dateValue).replace(" ", "T"));

    if (Number.isNaN(saleDate.getTime())) {
        return true;
    }

    const now = new Date();

    if (period === "week") {
        const startOfWeek = new Date(now);
        const currentDay = startOfWeek.getDay();
        const diffToMonday = (currentDay === 0 ? -6 : 1 - currentDay);
        startOfWeek.setDate(startOfWeek.getDate() + diffToMonday);
        startOfWeek.setHours(0, 0, 0, 0);

        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        endOfWeek.setHours(23, 59, 59, 999);

        return saleDate >= startOfWeek && saleDate <= endOfWeek;
    }

    if (period === "month") {
        return saleDate.getMonth() === now.getMonth() && saleDate.getFullYear() === now.getFullYear();
    }

    return true;
}

function getFilteredSalesForDisplay() {
    const search =
        document.getElementById("historySearch")
            .value
            .trim()
            .toLowerCase();

    const period =
        document.getElementById("historyPeriod")
            .value || "all";

    return salesHistory.filter(sale => {
        const number = String(sale.id || "").toLowerCase();
        const user = `${sale.prenom || ""} ${sale.nom || ""}`.toLowerCase();
        const matchesSearch = number.includes(search) || user.includes(search);
        const matchesPeriod = getHistoryPeriodFilter(sale.dateVente, period);

        return matchesSearch && matchesPeriod;
    });
}

function renderSalesHistory() {

    const body =
        document.getElementById(
            "salesHistoryBody"
        );

    const filtered = getFilteredSalesForDisplay();


    if (filtered.length === 0) {

        body.innerHTML = `

            <tr>

                <td
                    colspan="6"
                    class="table-empty"
                >
                    Aucune vente trouvée.
                </td>

            </tr>

        `;

        document.getElementById(
            "pagination"
        ).innerHTML = "";

        return;
    }


    const totalPages =
        Math.ceil(
            filtered.length /
            ITEMS_PER_PAGE
        );


    if (currentPage > totalPages) {
        currentPage = totalPages;
    }


    const start =
        (currentPage - 1) *
        ITEMS_PER_PAGE;


    const pageItems =
        filtered.slice(
            start,
            start + ITEMS_PER_PAGE
        );


    body.innerHTML =
        pageItems.map(sale => {

            const name =
                `${sale.prenom || ""} ${sale.nom || ""}`
                    .trim() ||
                "-";


            const articles =
                Number(
                    sale.nombreArticles ??
                    sale.nombre_articles ??
                    0
                );


            return `

                <tr>

                    <td>

                        <span class="sale-number">
                            #${sale.id}
                        </span>

                    </td>


                    <td>
                        ${formatDate(sale.dateVente)}
                    </td>


                    <td>
                        ${escapeHtml(name)}
                    </td>


                    <td>
                        ${formatNumber(articles)}
                    </td>


                    <td>

                        <span class="sale-amount">
                            ${formatCurrency(
                                sale.montantTotal
                            )}
                        </span>

                    </td>


                    <td>

                        <button
                            type="button"
                            class="view-sale"
                            data-id="${Number(sale.id)}"
                        >
                            Voir
                        </button>

                    </td>

                </tr>

            `;

        }).join("");


    body
        .querySelectorAll(".view-sale")
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    openSaleDetails(
                        Number(button.dataset.id)
                    );

                }
            );

        });


    renderPagination(totalPages);

}


/* =========================================================
   PAGINATION
========================================================= */

function renderPagination(totalPages) {

    const container =
        document.getElementById(
            "pagination"
        );


    if (totalPages <= 1) {

        container.innerHTML = "";

        return;
    }


    let html = "";


    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        html += `

            <button
                type="button"
                class="${page === currentPage ? "active" : ""}"
                data-page="${page}"
            >
                ${page}
            </button>

        `;

    }


    container.innerHTML = html;


    container
        .querySelectorAll("button")
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    currentPage =
                        Number(
                            button.dataset.page
                        );

                    renderSalesHistory();

                }
            );

        });

}


/* =========================================================
   DETAIL VENTE
========================================================= */

async function openSaleDetails(id) {

    const modal =
        document.getElementById(
            "saleModal"
        );

    const body =
        document.getElementById(
            "saleModalBody"
        );


    modal.classList.remove("hidden");

    body.textContent =
        "Chargement...";


    try {

        const response =
            await fetch(
                `${API_URL}?action=detail&id=${id}`,
                {
                    method: "GET",
                    credentials: "include",
                    headers: {
                        "Accept": "application/json"
                    }
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
                "Impossible de charger la vente."
            );

        }


        const sale =
            result.data.vente;


        document.getElementById(
            "modalSaleNumber"
        ).textContent =
            `Vente #${sale.id}`;


        const name =
            `${sale.prenom || ""} ${sale.nom || ""}`
                .trim() ||
            "-";


        body.innerHTML = `

            <div class="modal-sale-info">

                <div class="modal-info-box">

                    <span>
                        Date
                    </span>

                    <strong>
                        ${formatDate(sale.dateVente)}
                    </strong>

                </div>


                <div class="modal-info-box">

                    <span>
                        Utilisateur
                    </span>

                    <strong>
                        ${escapeHtml(name)}
                    </strong>

                </div>


                <div class="modal-info-box">

                    <span>
                        Total
                    </span>

                    <strong>
                        ${formatCurrency(
                            sale.montantTotal
                        )}
                    </strong>

                </div>

            </div>


            <table class="modal-detail-table">

                <thead>

                    <tr>

                        <th>
                            Médicament
                        </th>

                        <th>
                            Lot
                        </th>

                        <th>
                            Qté
                        </th>

                        <th>
                            Prix
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                    ${
                        (sale.details || [])
                        .map(detail => `

                            <tr>

                                <td>
                                    ${escapeHtml(
                                        detail.produitNom
                                    )}
                                </td>

                                <td>
                                    ${escapeHtml(
                                        detail.numeroLot
                                    )}
                                </td>

                                <td>
                                    ${formatNumber(
                                        detail.quantite
                                    )}
                                </td>

                                <td>
                                    ${formatCurrency(
                                        detail.prixUnitaire
                                    )}
                                </td>

                                <td>
                                    ${formatCurrency(
                                        Number(detail.quantite) *
                                        Number(detail.prixUnitaire)
                                    )}
                                </td>

                            </tr>

                        `).join("")
                    }

                </tbody>

            </table>


            <div class="modal-total">

                <span>
                    Total
                </span>

                <strong>
                    ${formatCurrency(
                        sale.montantTotal
                    )}
                </strong>

            </div>

        `;


    } catch (error) {

        body.innerHTML = `
            <div class="form-message error">
                ${escapeHtml(error.message)}
            </div>
        `;

    }

}


/* =========================================================
   EVENEMENTS
========================================================= */

function setupEvents() {

    const productSearch =
        document.getElementById(
            "productSearch"
        );


    productSearch.addEventListener(
        "input",
        event => {

            searchProducts(
                event.target.value
            );

        }
    );


    document.getElementById(
        "increaseQuantity"
    ).addEventListener(
        "click",
        () => {

            if (!selectedProduct) {
                return;
            }

            const input =
                document.getElementById(
                    "quantityInput"
                );

            const value =
                Number(input.value) || 1;


            if (
                value <
                selectedProduct.stockTotal
            ) {

                input.value =
                    value + 1;

            }

        }
    );


    document.getElementById(
        "decreaseQuantity"
    ).addEventListener(
        "click",
        () => {

            const input =
                document.getElementById(
                    "quantityInput"
                );

            const value =
                Number(input.value) || 1;


            if (value > 1) {
                input.value =
                    value - 1;
            }

        }
    );


    document.getElementById(
        "addToCartButton"
    ).addEventListener(
        "click",
        addToCart
    );


    document.getElementById(
        "clearCartButton"
    ).addEventListener(
        "click",
        clearCart
    );


    document.getElementById(
        "validateSaleButton"
    ).addEventListener(
        "click",
        validateSale
    );


    document.getElementById(
        "historySearch"
    ).addEventListener(
        "input",
        () => {

            currentPage = 1;

            renderSalesHistory();

        }
    );

    document.getElementById(
        "historyPeriod"
    ).addEventListener(
        "change",
        () => {
            currentPage = 1;
            renderSalesHistory();
        }
    );

    document.getElementById(
        "exportHistoryButton"
    ).addEventListener(
        "click",
        exportFilteredSalesHistory
    );


    document.getElementById(
        "refreshButton"
    ).addEventListener(
        "click",
        async () => {

            const button =
                document.getElementById(
                    "refreshButton"
                );

            button.disabled = true;

            button.textContent =
                "Actualisation...";


            await Promise.all([
                loadProducts(),
                loadSalesHistory()
            ]);


            button.disabled = false;

            button.textContent =
                "↻ Actualiser";

        }
    );


    document.getElementById(
        "closeSaleModal"
    ).addEventListener(
        "click",
        closeSaleModal
    );


    document.getElementById(
        "saleModal"
    ).addEventListener(
        "click",
        event => {

            if (
                event.target.id ===
                "saleModal"
            ) {

                closeSaleModal();

            }

        }
    );


    document.getElementById(
        "logoutBtn"
    ).addEventListener(
        "click",
        async event => {

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
    );

}


/* =========================================================
   FERMER MODAL
========================================================= */

function closeSaleModal() {

    document
        .getElementById("saleModal")
        .classList.add("hidden");

}


/* =========================================================
   MESSAGES
========================================================= */

function showProductMessage(
    message,
    type
) {

    const element =
        document.getElementById(
            "productMessage"
        );

    element.textContent =
        message;

    element.className =
        `form-message ${type}`;

}


function clearProductMessage() {

    const element =
        document.getElementById(
            "productMessage"
        );

    element.textContent = "";

    element.className =
        "form-message";

}


/* =========================================================
   FORMATAGE
========================================================= */

function exportFilteredSalesHistory() {
    const filtered = getFilteredSalesForDisplay();

    if (!filtered.length) {
        alert("Aucune vente disponible pour cette période.");
        return;
    }

    const headers = ["N°", "Date", "Utilisateur", "Médicament(s)", "Articles", "Montant (EUR)"];

    const rows = filtered.map(sale => {
        const user = `${sale.prenom || ""} ${sale.nom || ""}`.trim() || "-";
        const articles = Number(sale.nombreArticles ?? sale.nombre_articles ?? 0);
        const montant = Number(sale.montantTotal ?? 0);
        const medicaments = sale.medicaments || "-";

        return [
            sale.id,
            formatDate(sale.dateVente),
            user,
            medicaments,
            articles,
            montant.toFixed(2).replace(".", ",") + " €"
        ];
    });

    const htmlRows = [
        `<tr>${headers.map(header => `<th style="border:1px solid #d0d7de; padding:8px 10px; background:#f3f6f9; text-align:left; font-weight:700;">${escapeHtml(header)}</th>`).join("")}</tr>`
    ];

    rows.forEach(row => {
        htmlRows.push(
            `<tr>${row.map(value => `<td style="border:1px solid #d0d7de; padding:8px 10px;">${escapeHtml(value)}</td>`).join("")}</tr>`
        );
    });

    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #d0d7de; padding: 8px 10px; }
            </style>
        </head>
        <body>
            <table>
                ${htmlRows.join("")}
            </table>
        </body>
        </html>
    `;

    const blob = new Blob(["\uFEFF" + html], {
        type: "application/vnd.ms-excel;charset=utf-8;"
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    const period = document.getElementById("historyPeriod").value;
    const label = period === "week" ? "semaine" : period === "month" ? "mois" : "toutes";

    link.href = url;
    link.download = `historique-ventes-${label}-${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function formatNumber(value) {

    return new Intl.NumberFormat(
        "fr-FR"
    ).format(
        Number(value || 0)
    );

}


function formatCurrency(value) {

    return new Intl.NumberFormat(
        "fr-FR",
        {
            style: "currency",
            currency: "EUR"
        }
    ).format(
        Number(value || 0)
    );

}


function formatDate(value) {

    if (!value) {
        return "-";
    }


    const date =
        new Date(
            String(value).replace(
                " ",
                "T"
            )
        );


    if (Number.isNaN(date.getTime())) {
        return value;
    }


    return date.toLocaleDateString(
        "fr-FR"
    );

}


/* =========================================================
   PROTECTION XSS
========================================================= */

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";

    }


    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

}