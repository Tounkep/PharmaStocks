// =========================================================
// FRONTEND JAVASCRIPT : Interactions dynamiques
// =========================================================

document.addEventListener('DOMContentLoaded', function () {
    loadEmployeeDashboard();

    // 1. Filtrage dynamique de la liste des ventes via la recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.recent-sale-item');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                const query = globalSearch.value.trim();
                if (query) {
                    window.location.href =
                        `medicaments.html?search=${encodeURIComponent(query)}`;
                }
            }
        });
    }

    // 2. Gestion de la fenêtre modale de vente
    const saleModal = document.getElementById('saleModal');
    const btnNewSale = document.getElementById('btnNewSale');
    const btnCancelModal = document.getElementById('btnCancelModal');

    // Ouverture de la modale au clic sur "Nouvelle vente"
    if (btnNewSale && saleModal) {
        btnNewSale.addEventListener('click', function (e) {
            e.preventDefault();
            saleModal.style.display = 'flex';
        });
    }

    // Fermeture de la modale au clic sur "Annuler"
    if (btnCancelModal && saleModal) {
        btnCancelModal.addEventListener('click', function () {
            saleModal.style.display = 'none';
        });
    }

    // Déconnexion
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', deconnexion);
    }

    // Fermeture de la modale en cliquant en dehors du contenu
    window.addEventListener('click', function (e) {
        if (e.target === saleModal) {
            saleModal.style.display = 'none';
        }
    });
});

async function loadEmployeeDashboard() {
    const salesList = document.getElementById('recentSalesList');
    const emptyMessage = document.getElementById('salesEmptyMessage');

    try {
        const response = await fetch('../../backend/routes/employee.php', {
            headers: { Accept: 'application/json' },
            credentials: 'include'
        });

        const result = await response.json();
        if (!response.ok || !result.success || !result.data) {
            throw new Error(result.message || `Erreur HTTP ${response.status}`);
        }

        const quickInfo = result.data.informationsRapides || {};
        setDashboardCount('lowStockCount', quickInfo.low_stock);
        setDashboardCount('outOfStockCount', quickInfo.out_of_stock);
        setDashboardCount('expirationCount', quickInfo.expiring_soon);

        const sales = Array.isArray(result.data.ventesRecentes)
            ? result.data.ventesRecentes
            : [];

        salesList.replaceChildren();
        if (sales.length === 0) {
            emptyMessage.textContent = 'Aucune vente enregistrée pour le moment.';
            return;
        }

        emptyMessage.hidden = true;
        sales.forEach(sale => salesList.appendChild(createSaleItem(sale)));
    } catch (error) {
        console.error('Erreur de chargement du tableau de bord employé :', error);
        emptyMessage.textContent = 'Impossible de charger les ventes. Veuillez réessayer plus tard.';
    }
}

function setDashboardCount(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = Number.isFinite(Number(value)) ? String(Number(value)) : '—';
    }
}

function createSaleItem(sale) {
    const item = document.createElement('div');
    item.className = 'activity-item';

    const icon = document.createElement('div');
    icon.className = 'activity-icon';
    icon.textContent = '🛒';

    const content = document.createElement('div');
    content.className = 'activity-content';

    const title = document.createElement('strong');
    title.textContent = `Vente #${sale.id}`;

    const amount = document.createElement('small');
    amount.textContent = new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(Number(sale.montantTotal) || 0);

    const date = document.createElement('span');
    date.className = 'activity-date';
    date.textContent = formatSaleDate(sale.dateVente);

    content.append(title, amount);
    item.append(icon, content, date);
    return item;
}

function formatSaleDate(value) {
    if (!value) {
        return '';
    }

    const parts = String(value).slice(0, 10).split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(value);
}

// =========================================================
// DÉCONNEXION
// =========================================================

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
