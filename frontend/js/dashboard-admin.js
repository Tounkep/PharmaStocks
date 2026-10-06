document.addEventListener("DOMContentLoaded", () => {
    loadDashboard();
    setupEvents();
});


let salesChart = null;


/**
 * =====================================================
 * URL DE L'API
 * =====================================================
 */

const API_URL = "../../backend/routes/dashboard.php";


/**
 * =====================================================
 * CHARGEMENT DU DASHBOARD
 * =====================================================
 */

async function loadDashboard() {

    try {

        showLoading();


        const response = await fetch(
            API_URL,
            {
                method: "GET",

                headers: {
                    "Accept": "application/json"
                },

                credentials: "include"
            }
        );


        if (!response.ok) {

            throw new Error(
                `Erreur HTTP ${response.status}`
            );
        }


        const result =
            await response.json();


        if (!result.success) {

            /*
             * Si la session n'existe plus,
             * retour vers la connexion.
             */

            if (response.status === 401) {

                window.location.href =
                    "connexion.html";

                return;
            }


            throw new Error(
                result.message ||
                "Erreur lors du chargement du dashboard."
            );
        }


        const data = result.data;


        /*
         * =====================================================
         * INFORMATIONS DE L'ADMIN CONNECTÉ
         * =====================================================
         */

        updateAdminInfo(
            data.utilisateur
        );


        /*
         * =====================================================
         * STATISTIQUES PRINCIPALES
         * =====================================================
         */

        updateStats(
            data.statistiques
        );


        /*
         * =====================================================
         * ALERTES
         * =====================================================
         */

        updateAlerts(
            data.statistiques,
            data.alertes
        );


        /*
         * =====================================================
         * GRAPHIQUE DES VENTES
         * =====================================================
         */

        updateChart(
            data.ventesDerniersJours
        );


        /*
         * =====================================================
         * ACTIVITÉ RÉCENTE
         * =====================================================
         */

        updateActivities(
            data.activiteRecente
        );


        /*
         * =====================================================
         * DERNIÈRES VENTES
         * =====================================================
         */

        updateRecentSales(
            data.dernieresVentes
        );


        /*
         * =====================================================
         * BADGE DES NOTIFICATIONS
         * =====================================================
         */

        updateNotificationBadge(
            data
        );


    } catch (error) {

        console.error(
            "Erreur dashboard :",
            error
        );


        showError(
            "Impossible de charger les données du dashboard."
        );
    }
}


/**
 * =====================================================
 * INFORMATIONS DE L'ADMIN CONNECTÉ
 * =====================================================
 */

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


/**
 * =====================================================
 * STATISTIQUES PRINCIPALES
 * =====================================================
 */

function updateStats(stats) {

    if (!stats) {
        return;
    }


    /*
     * Nombre de produits
     */

    const totalMedicaments =
        document.getElementById(
            "totalMedicaments"
        );


    if (totalMedicaments) {

        totalMedicaments.textContent =
            formatNumber(
                stats.totalProduits
            );
    }


    /*
     * Stock total
     */

    const totalStock =
        document.getElementById(
            "totalStock"
        );


    if (totalStock) {

        totalStock.textContent =
            formatNumber(
                stats.stockTotal
            );
    }


    /*
     * Chiffre d'affaires
     */

    const ventesMois =
        document.getElementById(
            "ventesMois"
        );


    if (ventesMois) {

        ventesMois.textContent =
            formatCurrency(
                stats.chiffreAffaires
            );
    }


    /*
     * Nombre total d'utilisateurs
     */

    const totalUtilisateurs =
        document.getElementById(
            "totalUtilisateurs"
        );


    if (totalUtilisateurs) {

        totalUtilisateurs.textContent =
            formatNumber(
                stats.totalUtilisateurs
            );
    }


    /*
     * Fournisseurs
     */

    const totalFournisseurs =
        document.getElementById(
            "totalFournisseurs"
        );


    if (totalFournisseurs) {

        totalFournisseurs.textContent =
            formatNumber(
                stats.totalFournisseurs
            );
    }


    /*
     * Ventes
     */

    const totalVentes =
        document.getElementById(
            "totalVentes"
        );


    if (totalVentes) {

        totalVentes.textContent =
            formatNumber(
                stats.totalVentes
            );
    }


    /*
     * Stock faible
     */

    const stockFaible =
        document.getElementById(
            "stockFaible"
        );


    if (stockFaible) {

        stockFaible.textContent =
            formatNumber(
                stats.stockFaible
            );
    }


    /*
     * Expirations proches
     */

    const expirationsProches =
        document.getElementById(
            "expirationsProches"
        );


    if (expirationsProches) {

        expirationsProches.textContent =
            formatNumber(
                stats.expirationsProches
            );
    }


    /*
     * Lots expirés
     */

    const lotsExpires =
        document.getElementById(
            "lotsExpires"
        );


    if (lotsExpires) {

        lotsExpires.textContent =
            formatNumber(
                stats.lotsExpires
            );
    }


    /*
     * Commandes en attente
     */

    const commandesEnAttente =
        document.getElementById(
            "commandesEnAttente"
        );


    if (commandesEnAttente) {

        commandesEnAttente.textContent =
            formatNumber(
                stats.commandesEnAttente
            );
    }
}


/**
 * =====================================================
 * ALERTES
 * =====================================================
 */

function updateAlerts(stats, alertes) {

    let ruptures = 0;


    if (Array.isArray(alertes)) {

        ruptures =
            alertes.filter(
                alerte =>
                    alerte.type === "RUPTURE"
            ).length;
    }


    /*
     * Stock faible
     */

    const stocksFaiblesCount =
        document.getElementById(
            "stocksFaiblesCount"
        );


    if (stocksFaiblesCount) {

        stocksFaiblesCount.textContent =
            formatNumber(
                stats?.stockFaible || 0
            );
    }


    /*
     * Expirations proches
     */

    const expirationsCount =
        document.getElementById(
            "expirationsCount"
        );


    if (expirationsCount) {

        expirationsCount.textContent =
            formatNumber(
                stats?.expirationsProches || 0
            );
    }


    /*
     * Ruptures
     */

    const rupturesCount =
        document.getElementById(
            "rupturesCount"
        );


    if (rupturesCount) {

        rupturesCount.textContent =
            formatNumber(
                ruptures
            );
    }
}


/**
 * =====================================================
 * GRAPHIQUE DES VENTES
 * =====================================================
 */

function updateChart(ventes) {

    const canvas =
        document.getElementById(
            "salesChart"
        );


    if (!canvas) {
        return;
    }


    if (!Array.isArray(ventes)) {

        ventes = [];
    }


    const labels =
        ventes.map(
            vente =>
                formatDate(
                    vente.dateVente
                )
        );


    const values =
        ventes.map(
            vente =>
                Number(
                    vente.total || 0
                )
        );


    if (salesChart) {

        salesChart.destroy();
    }


    salesChart =
        new Chart(
            canvas,
            {
                type: "line",

                data: {

                    labels: labels,

                    datasets: [
                        {
                            label: "Ventes (€)",

                            data: values,

                            borderWidth: 3,

                            fill: true,

                            tension: 0.4,

                            pointRadius: 4,

                            pointHoverRadius: 6
                        }
                    ]
                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,


                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            callbacks: {

                                label: function(context) {

                                    return formatCurrency(
                                        context.parsed.y
                                    );
                                }
                            }
                        }
                    },


                    scales: {

                        y: {

                            beginAtZero: true,

                            ticks: {

                                callback: function(value) {

                                    return value + " €";
                                }
                            }
                        }
                    }
                }
            }
        );
}


/**
 * =====================================================
 * ACTIVITÉS RÉCENTES
 * =====================================================
 */

function updateActivities(activities) {

    const container =
        document.getElementById(
            "activityList"
        );


    if (!container) {
        return;
    }


    if (
        !activities ||
        activities.length === 0
    ) {

        container.innerHTML = `
            <div class="empty-state">
                Aucune activité récente.
            </div>
        `;

        return;
    }


    container.innerHTML =
        activities.map(
            activity => {

                const name =
                    `${activity.prenom || ""} ${activity.nom || ""}`
                    .trim();


                return `

                    <div class="activity-item">

                        <div class="activity-icon">
                            ✓
                        </div>


                        <div class="activity-content">

                            <strong>
                                ${escapeHtml(
                                    activity.action
                                )}
                            </strong>


                            <span>
                                ${escapeHtml(
                                    activity.description || ""
                                )}
                            </span>


                            <small>
                                ${escapeHtml(
                                    name
                                )}
                            </small>

                        </div>


                        <div class="activity-date">

                            ${formatDateTime(
                                activity.dateHeure
                            )}

                        </div>

                    </div>

                `;
            }
        ).join("");
}


/**
 * =====================================================
 * DERNIÈRES VENTES
 * =====================================================
 */

function updateRecentSales(sales) {

    const container =
        document.getElementById(
            "recentSalesList"
        );


    if (!container) {
        return;
    }


    if (
        !sales ||
        sales.length === 0
    ) {

        container.innerHTML = `
            <div class="empty-state">
                Aucune vente récente.
            </div>
        `;

        return;
    }


    container.innerHTML =
        sales.map(
            vente => {

                const name =
                    `${vente.prenom || ""} ${vente.nom || ""}`
                    .trim();


                return `

                    <div class="recent-sale-item">

                        <div class="sale-icon">
                            🛒
                        </div>


                        <div class="sale-info">

                            <strong>
                                Vente #${escapeHtml(
                                    vente.id
                                )}
                            </strong>


                            <small>
                                ${escapeHtml(
                                    name
                                )}
                            </small>


                            <small>
                                ${formatDate(
                                    vente.dateVente
                                )}
                            </small>

                        </div>


                        <div class="sale-amount">

                            ${formatCurrency(
                                vente.montantTotal
                            )}

                        </div>

                    </div>

                `;
            }
        ).join("");
}


/**
 * =====================================================
 * BADGE DES NOTIFICATIONS
 * =====================================================
 */

function updateNotificationBadge(data) {

    const badge =
        document.getElementById(
            "notificationBadge"
        );


    if (!badge) {
        return;
    }


    const stats =
        data.statistiques || {};


    const alertes =
        Array.isArray(data.alertes)
            ? data.alertes
            : [];


    let totalAlertes =
        alertes.length;


    if (totalAlertes === 0) {

        totalAlertes =
            Number(
                stats.stockFaible || 0
            ) +
            Number(
                stats.expirationsProches || 0
            ) +
            Number(
                stats.lotsExpires || 0
            );
    }


    badge.textContent =
        formatNumber(
            totalAlertes
        );
}


/**
 * =====================================================
 * ÉVÉNEMENTS
 * =====================================================
 */

function setupEvents() {


    /*
     * Actualiser
     */

    const refreshButton =
        document.getElementById(
            "refreshButton"
        );


    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            async () => {

                refreshButton.disabled =
                    true;


                const originalText =
                    refreshButton.textContent;


                refreshButton.textContent =
                    "Actualisation...";


                try {

                    await loadDashboard();

                } finally {

                    refreshButton.disabled =
                        false;


                    refreshButton.textContent =
                        originalText ||
                        "↻ Actualiser";
                }
            }
        );
    }


    /*
     * =====================================================
     * DÉCONNEXION
     * =====================================================
     */

    const logout =
        document.getElementById(
            "logoutBtn"
        );


    if (logout) {

        logout.addEventListener(
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

                            body:
                                "action=logout",

                            credentials:
                                "include"
                        }
                    );


                } catch (error) {

                    console.error(
                        "Erreur de déconnexion :",
                        error
                    );


                } finally {

                    /*
                     * Retour à la page de connexion.
                     */

                    window.location.href =
                        "connexion.html";
                }
            }
        );
    }
}


/**
 * =====================================================
 * AFFICHAGE DU CHARGEMENT
 * =====================================================
 */

function showLoading() {

    const activity =
        document.getElementById(
            "activityList"
        );


    const sales =
        document.getElementById(
            "recentSalesList"
        );


    if (activity) {

        activity.innerHTML = `
            <div class="loading">
                Chargement...
            </div>
        `;
    }


    if (sales) {

        sales.innerHTML = `
            <div class="loading">
                Chargement...
            </div>
        `;
    }
}


/**
 * =====================================================
 * AFFICHAGE D'UNE ERREUR
 * =====================================================
 */

function showError(message) {

    const activity =
        document.getElementById(
            "activityList"
        );


    if (activity) {

        activity.innerHTML = `
            <div class="error-state">
                ${escapeHtml(message)}
            </div>
        `;
    }
}


/**
 * =====================================================
 * FORMAT NOMBRE
 * =====================================================
 */

function formatNumber(value) {

    return new Intl.NumberFormat(
        "fr-FR"
    ).format(
        Number(
            value || 0
        )
    );
}


/**
 * =====================================================
 * FORMAT MONNAIE
 * =====================================================
 */

function formatCurrency(value) {

    return new Intl.NumberFormat(
        "fr-FR",
        {
            style: "currency",
            currency: "EUR"
        }
    ).format(
        Number(
            value || 0
        )
    );
}


/**
 * =====================================================
 * FORMAT DATE
 * =====================================================
 */

function formatDate(date) {

    if (!date) {
        return "-";
    }


    const parts =
        String(date).split("-");


    if (parts.length === 3) {

        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }


    return String(date);
}


/**
 * =====================================================
 * FORMAT DATE + HEURE
 * =====================================================
 */

function formatDateTime(date) {

    if (!date) {
        return "-";
    }


    const value =
        String(date);


    const d =
        new Date(
            value.replace(
                " ",
                "T"
            )
        );


    if (
        Number.isNaN(
            d.getTime()
        )
    ) {

        return value;
    }


    return d.toLocaleString(
        "fr-FR",
        {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }
    );
}


/**
 * =====================================================
 * PROTECTION XSS
 * =====================================================
 */

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";
    }


    return String(value)

        .replaceAll(
            "&",
            "&amp;"
        )

        .replaceAll(
            "<",
            "&lt;"
        )

        .replaceAll(
            ">",
            "&gt;"
        )

        .replaceAll(
            '"',
            "&quot;"
        )

        .replaceAll(
            "'",
            "&#039;"
        );
}