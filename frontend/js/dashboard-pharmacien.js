/* =========================================================
   PHARMASTOCK - DASHBOARD PHARMACIEN
========================================================= */

const DASHBOARD_URL = "../../backend/routes/dashboardPharmacien.php";

const U = PharmaUtils;

let salesChart = null;


document.addEventListener("DOMContentLoaded", () => {

    document
        .getElementById("refreshButton")
        .addEventListener("click", loadDashboard);

    /*
     * Recherche : ouvre la page Stock filtrée.
     */
    document
        .getElementById("globalSearch")
        .addEventListener("keydown", event => {

            if (event.key === "Enter" && event.target.value.trim() !== "") {
                window.location.href =
                    "stock.html?q=" + encodeURIComponent(event.target.value.trim());
            }
        });

    document.getElementById("todayLabel").textContent =
        "Voici l'état de la pharmacie, " +
        new Date().toLocaleDateString("fr-FR", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric"
        }) + ".";

    loadDashboard();
});


/* =========================================================
   CHARGEMENT
========================================================= */

async function loadDashboard() {

    const button = document.getElementById("refreshButton");

    button.disabled = true;

    try {

        const { data } = await U.api(DASHBOARD_URL);

        renderWelcome(data.utilisateur);
        renderStats(data.statistiques, data.alertes);
        renderChart(data.ventes7Jours);
        renderAlertSummary(data.alertes);
        renderRestock(data.aReapprovisionner);
        renderExpiry(data.lotsAExpiration);
        renderMovements(data.derniersMouvements);
        renderOrders(data.commandesEnCours);

        if (window.PharmaSession && window.PharmaSession.updateAlertCount) {
            window.PharmaSession.updateAlertCount(Number(data.alertes.total || 0));
        }

    } catch (error) {

        console.error("Erreur dashboard :", error);

        U.toast(error.message || "Impossible de charger le dashboard.", "error");

    } finally {

        button.disabled = false;
    }
}


/* =========================================================
   EN-TÊTE ET INDICATEURS
========================================================= */

function renderWelcome(utilisateur) {

    if (!utilisateur) {
        return;
    }

    document.getElementById("welcomeName").textContent =
        utilisateur.prenom || "";
}


function renderStats(stats, alertes) {

    setText("statCaJour", U.currency(stats.chiffreAffairesJour));

    setText(
        "statVentesJour",
        `${U.number(stats.ventesJour)} vente${stats.ventesJour > 1 ? "s" : ""} aujourd'hui`
    );

    setText("statStock", U.number(stats.stockTotal));

    setText("statProduits", `${U.number(stats.totalProduits)} médicaments référencés`);

    setText("statAlertes", U.number(alertes.total));

    setText(
        "statAlertesDetail",
        `${stats.ruptures} rupture${stats.ruptures > 1 ? "s" : ""}, ` +
        `${stats.lotsPerimes} lot${stats.lotsPerimes > 1 ? "s" : ""} périmé${stats.lotsPerimes > 1 ? "s" : ""}`
    );

    setText("statCommandes", U.number(stats.commandesEnCours));

    setText("statCaMois", `CA du mois : ${U.currency(stats.chiffreAffairesMois)}`);
}


/* =========================================================
   GRAPHIQUE
========================================================= */

function renderChart(jours) {

    const canvas = document.getElementById("salesChart");

    if (!canvas || typeof Chart === "undefined") {
        return;
    }

    const labels = jours.map(jour =>
        new Date(jour.date + "T00:00:00").toLocaleDateString("fr-FR", {
            weekday: "short",
            day: "numeric"
        })
    );

    if (salesChart) {
        salesChart.destroy();
    }

    salesChart = new Chart(canvas, {

        type: "bar",

        data: {
            labels,
            datasets: [{
                label: "Chiffre d'affaires",
                data: jours.map(jour => jour.total),
                backgroundColor: "#087f80",
                borderRadius: 6,
                maxBarThickness: 38
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => {
                            const jour = jours[context.dataIndex];
                            return `${U.currency(jour.total)} (${jour.nombre} vente${jour.nombre > 1 ? "s" : ""})`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => value + " €"
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}


/* =========================================================
   ALERTES
========================================================= */

function renderAlertSummary(alertes) {

    const container = document.getElementById("alertSummary");

    const lignes = [
        ["RUPTURE", "danger", "Ruptures de stock", "Produits sans stock vendable"],
        ["EXPIRE", "danger", "Lots périmés", "À retirer des rayons"],
        ["STOCK_FAIBLE", "warning", "Stocks faibles", "Sous le seuil minimum"],
        ["EXPIRATION_PROCHE", "orange", "Expirations proches", "Dans les 30 prochains jours"]
    ];

    container.innerHTML = lignes.map(([type, classe, titre, detail]) => `
        <a class="alert-item ${classe}" href="alertes.html?type=${type}" style="text-decoration:none;color:inherit;">
            <div class="alert-icon">${U.ALERTES[type][2]}</div>
            <div class="alert-content">
                <strong>${U.number(alertes[type] || 0)} ${titre.toLowerCase()}</strong>
                <small>${detail}</small>
            </div>
            <span>→</span>
        </a>
    `).join("");
}


/* =========================================================
   LISTES
========================================================= */

function renderRestock(produits) {

    renderList("restockList", produits, "Aucun produit sous le seuil. 👍", produit => {

        const stock = Number(produit.stock);

        return `
            <div class="ph-list-item">
                <div class="ph-list-main">
                    <strong>${U.escape(produit.nom)}</strong>
                    <small>Réf. ${U.escape(produit.reference)} · seuil ${U.number(produit.seuilMinimum)}</small>
                </div>
                <div class="ph-list-side">
                    ${stock === 0
                        ? '<span class="badge badge-red">Rupture</span>'
                        : `<span class="badge badge-orange">${U.number(stock)} en stock</span>`}
                </div>
            </div>
        `;
    });
}


function renderExpiry(lots) {

    renderList("expiryList", lots, "Aucun lot à surveiller.", lot => {

        const jours = Number(lot.joursRestants);

        return `
            <div class="ph-list-item">
                <div class="ph-list-main">
                    <strong>${U.escape(lot.produit)}</strong>
                    <small>Lot ${U.escape(lot.numeroLot)} · ${U.number(lot.quantite)} unités · ${U.date(lot.dateExpiration)}</small>
                </div>
                <div class="ph-list-side">
                    <span class="badge ${jours < 0 ? "badge-red" : jours <= 7 ? "badge-orange" : "badge-purple"}">
                        ${U.daysLabel(jours)}
                    </span>
                </div>
            </div>
        `;
    });
}


function renderMovements(mouvements) {

    renderList("movementList", mouvements, "Aucun mouvement enregistré.", mouvement => {

        const sens = U.MOUVEMENTS[mouvement.type] ? U.MOUVEMENTS[mouvement.type][2] : 0;

        const quantite = sens > 0
            ? `<span class="qty-positive">+${U.number(mouvement.quantite)}</span>`
            : sens < 0
                ? `<span class="qty-negative">−${U.number(mouvement.quantite)}</span>`
                : `<span>±${U.number(mouvement.quantite)}</span>`;

        return `
            <div class="ph-list-item">
                <div class="ph-list-main">
                    <strong>${U.escape(mouvement.produit)} ${U.badge(U.MOUVEMENTS, mouvement.type)}</strong>
                    <small>${U.escape(mouvement.motif || "")} · ${U.escape(mouvement.prenom)} ${U.escape(mouvement.nom)} · ${U.dateTime(mouvement.dateHeure)}</small>
                </div>
                <div class="ph-list-side">${quantite}</div>
            </div>
        `;
    });
}


function renderOrders(commandes) {

    renderList("orderList", commandes, "Aucune commande en cours.", commande => `
        <div class="ph-list-item">
            <div class="ph-list-main">
                <strong>Commande #${U.escape(commande.id)} · ${U.escape(commande.fournisseur)}</strong>
                <small>${U.date(commande.dateCommande)} · ${U.currency(commande.montantTotal)}</small>
            </div>
            <div class="ph-list-side">
                ${U.badge(U.COMMANDES, commande.statut)}
                <a class="mini-button" href="stock.html?action=reception&commande=${encodeURIComponent(commande.id)}" style="text-decoration:none;margin-left:6px;">Réceptionner</a>
            </div>
        </div>
    `);
}


function renderList(id, items, emptyText, template) {

    const container = document.getElementById(id);

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = `<div class="ph-empty">${emptyText}</div>`;
        return;
    }

    container.innerHTML = items.map(template).join("");
}


function setText(id, value) {

    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}
