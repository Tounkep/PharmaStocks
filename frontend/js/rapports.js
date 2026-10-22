(() => {
    const endpoint = "../../backend/routes/rapports.php";
    const byId = (id) => document.getElementById(id);
    let chart = null;
    let revenueChart = null;

    function escapeHtml(value) {
        return String(value ?? "-").replace(/[&<>"']/g, (char) => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
        })[char]);
    }

    function number(value) {
        return new Intl.NumberFormat("fr-FR").format(Number(value || 0));
    }

    function render(data) {
        const stats = data.statistiques || {};
        byId("chiffreAffaires").textContent = `${number(stats.chiffreAffaires)} €`;
        byId("nombreVentes").textContent = number(stats.nombreVentes);
        byId("quantiteStock").textContent = number(stats.quantiteStock);
        byId("commandesEnAttente").textContent = number(stats.commandesEnAttente);
        byId("stockFaible").textContent = number(stats.stockFaible);
        byId("expirationsProches").textContent = number(stats.expirationsProches);
        byId("lotsExpires").textContent = number(stats.lotsExpires);

        const rows = data.evolutionMouvements || [];
        const canvas = byId("salesChart");
        if (canvas && window.Chart) {
            if (chart) chart.destroy();
            chart = new Chart(canvas, {
                type: "line",
                data: {
                    labels: rows.map((row) => row.mois),
                    datasets: [
                        { label: "Entrées", data: rows.map((row) => Number(row.entrees)), borderColor: "#15966b", backgroundColor: "rgba(21,150,107,.12)", tension: .3, fill: true },
                        { label: "Sorties", data: rows.map((row) => Number(row.sorties)), borderColor: "#e17c35", backgroundColor: "rgba(225,124,53,.10)", tension: .3, fill: true }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: "index", intersect: false }, scales: { y: { beginAtZero: true } } }
            });
        }

        const revenueCanvas = byId("revenueChart");
        if (revenueCanvas && window.Chart) {
            if (revenueChart) revenueChart.destroy();
            const sales = data.evolutionVentes || [];
            revenueChart = new Chart(revenueCanvas, {
                type: "line",
                data: { labels: sales.map((row) => row.mois), datasets: [{
                    label: "Chiffre d'affaires (€)", data: sales.map((row) => Number(row.chiffreAffaires)),
                    borderColor: "#5865c5", backgroundColor: "rgba(88,101,197,.12)", tension: .3, fill: true
                }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        }

        const movementBody = byId("mouvementsTableBody");
        const movements = data.derniersMouvements || [];
        movementBody.innerHTML = movements.length ? movements.map((item) => `<tr>
            <td>${escapeHtml(item.dateHeure)}</td><td>${escapeHtml(item.type)}</td>
            <td>${escapeHtml(item.produit)}</td><td>${escapeHtml(item.numeroLot)}</td>
            <td>${number(item.quantite)}</td><td>${escapeHtml(item.motif)}</td>
            <td>${escapeHtml(`${item.prenom || ""} ${item.nom || ""}`.trim())}</td></tr>`).join("")
            : '<tr><td colspan="7">Aucun mouvement enregistré.</td></tr>';

        const statusBody = byId("commandesTableBody");
        const statuses = data.commandesParStatut || [];
        statusBody.innerHTML = statuses.length ? statuses.map((item) => `<tr><td>${escapeHtml(item.statut)}</td><td>${number(item.nombre)}</td></tr>`).join("")
            : '<tr><td colspan="2">Aucune commande enregistrée.</td></tr>';
    }

    async function loadReports() {
        const errorMessage = byId("reportError");
        if (errorMessage) {
            errorMessage.hidden = true;
            errorMessage.textContent = "";
        }
        try {
            const response = await fetch(endpoint, { credentials: "same-origin" });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Impossible de charger les rapports.");
            render(result.data);
        } catch (error) {
            console.error("Chargement des rapports impossible:", error);
            if (errorMessage) {
                errorMessage.textContent = `Les données des rapports n'ont pas pu être chargées : ${error.message}`;
                errorMessage.hidden = false;
            }
            const body = byId("mouvementsTableBody");
            if (body) body.innerHTML = `<tr><td colspan="7">${escapeHtml(error.message)}</td></tr>`;
        }
    }

    loadReports();
})();
