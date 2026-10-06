/* =========================================================
   PHARMASTOCK - ALERTES DE STOCK
========================================================= */

const ALERTES_URL = "../../backend/routes/alertes.php";
const STOCK_URL = "../../backend/routes/stock.php";

const U = PharmaUtils;

const STATUTS = {
    NOUVELLE: ["À traiter", "badge-orange"],
    IGNOREE: ["Ignorée", "badge-grey"],
    TRAITEE: ["Résolue", "badge-green"]
};

const state = {
    alertes: [],
    type: "",
    canEdit: false
};


document.addEventListener("DOMContentLoaded", async () => {

    const params = new URLSearchParams(window.location.search);

    if (params.get("type")) {
        setType(params.get("type"));
    }

    bindEvents();

    await window.PharmaSession.ready;

    state.canEdit = window.PharmaSession.canEdit();

    if (!state.canEdit) {
        document
            .querySelectorAll(".edit-only")
            .forEach(element => element.classList.add("hidden"));
    }

    loadAlertes();
});


/* =========================================================
   ÉVÉNEMENTS
========================================================= */

function bindEvents() {

    document.getElementById("typeChips").addEventListener("click", event => {

        const chip = event.target.closest(".chip");

        if (chip) {
            setType(chip.dataset.type);
            render();
        }
    });

    document.getElementById("statusFilter").addEventListener("change", loadAlertes);
    document.getElementById("refreshButton").addEventListener("click", loadAlertes);
    document.getElementById("globalSearch").addEventListener("input", render);

    document.getElementById("alertsTableBody").addEventListener("click", event => {

        const button = event.target.closest("button[data-action]");

        if (!button) {
            return;
        }

        const alerte = state.alertes.find(item => String(item.id) === button.dataset.id);

        if (!alerte) {
            return;
        }

        if (button.dataset.action === "ignore") {
            changeStatus(alerte, "IGNOREE");
        }

        if (button.dataset.action === "reopen") {
            changeStatus(alerte, "NOUVELLE");
        }

        if (button.dataset.action === "remove") {
            removeLot(alerte);
        }
    });
}


function setType(type) {

    state.type = type || "";

    document.querySelectorAll("#typeChips .chip").forEach(chip => {
        chip.classList.toggle("active", chip.dataset.type === state.type);
    });
}


/* =========================================================
   CHARGEMENT
========================================================= */

async function loadAlertes() {

    const statut = document.getElementById("statusFilter").value;

    const url = statut
        ? `${ALERTES_URL}?statut=${encodeURIComponent(statut)}`
        : ALERTES_URL;

    try {

        const { data } = await U.api(url);

        state.alertes = data.alertes;

        renderCounters(data.compteurs);
        render();

        if (window.PharmaSession.updateAlertCount) {
            window.PharmaSession.updateAlertCount(Number(data.compteurs.total || 0));
        }

    } catch (error) {

        document.getElementById("alertsTableBody").innerHTML =
            `<tr><td colspan="5" class="table-empty">${U.escape(error.message)}</td></tr>`;
    }
}


function renderCounters(compteurs) {

    document.querySelectorAll("[data-count]").forEach(element => {

        const value = Number(compteurs[element.dataset.count] || 0);

        element.textContent = value > 0 ? ` (${value})` : "";
    });
}


/* =========================================================
   AFFICHAGE
========================================================= */

function render() {

    const tbody = document.getElementById("alertsTableBody");
    const search = document.getElementById("globalSearch").value.trim().toLowerCase();

    const alertes = state.alertes.filter(alerte => {

        if (state.type && alerte.type !== state.type) {
            return false;
        }

        if (search) {
            return [alerte.message, alerte.produit, alerte.reference, alerte.numeroLot]
                .join(" ")
                .toLowerCase()
                .includes(search);
        }

        return true;
    });

    document.getElementById("alertsCount").textContent =
        `${alertes.length} alerte${alertes.length > 1 ? "s" : ""}`;

    if (alertes.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="5" class="table-empty">Aucune alerte. Tout est en ordre 👍</td></tr>';
        return;
    }

    tbody.innerHTML = alertes.map(alerte => `
        <tr class="${alerte.statut === "NOUVELLE" && (alerte.type === "RUPTURE" || alerte.type === "EXPIRE") ? "row-danger" : ""}">
            <td>${U.badge(U.ALERTES, alerte.type)}</td>
            <td>
                <strong>${U.escape(alerte.message)}</strong>
                ${alerte.reference ? `<div class="muted">Réf. ${U.escape(alerte.reference)}</div>` : ""}
            </td>
            <td>${U.dateTime(alerte.dateCreation)}</td>
            <td>${U.badge(STATUTS, alerte.statut)}</td>
            ${state.canEdit ? `<td><div class="table-actions">${actionsFor(alerte)}</div></td>` : ""}
        </tr>
    `).join("");
}


function actionsFor(alerte) {

    if (alerte.statut === "TRAITEE") {
        return '<span class="muted">—</span>';
    }

    const id = alerte.id;
    const buttons = [];

    if (alerte.type === "RUPTURE" || alerte.type === "STOCK_FAIBLE") {
        buttons.push('<a class="mini-button" href="commandes.html" style="text-decoration:none;">Commander</a>');
    }

    if (alerte.type === "EXPIRE" && Number(alerte.quantiteLot) > 0) {
        buttons.push(`<button class="mini-button danger" type="button" data-action="remove" data-id="${id}">Retirer le lot</button>`);
    }

    if (alerte.type === "EXPIRATION_PROCHE" && alerte.numeroLot) {
        buttons.push(`<a class="mini-button" href="stock.html?q=${encodeURIComponent(alerte.numeroLot)}" style="text-decoration:none;">Voir le lot</a>`);
    }

    if (alerte.produit_id) {
        buttons.push(`<a class="mini-button muted" href="medicament-detail.html?id=${encodeURIComponent(alerte.produit_id)}" style="text-decoration:none;">Fiche</a>`);
    }

    buttons.push(
        alerte.statut === "IGNOREE"
            ? `<button class="mini-button" type="button" data-action="reopen" data-id="${id}">Réactiver</button>`
            : `<button class="mini-button muted" type="button" data-action="ignore" data-id="${id}">Ignorer</button>`
    );

    return buttons.join("");
}


/* =========================================================
   ACTIONS
========================================================= */

async function changeStatus(alerte, statut) {

    try {

        await U.api(ALERTES_URL, {
            method: "PATCH",
            body: {
                id: alerte.id,
                statut
            }
        });

        U.toast(statut === "IGNOREE" ? "Alerte ignorée." : "Alerte réactivée.", "success");

        await loadAlertes();

    } catch (error) {

        U.toast(error.message, "error");
    }
}


async function removeLot(alerte) {

    const ok = confirm(
        `Retirer le lot ${alerte.numeroLot} (${alerte.produit}) ?\n\n` +
        `${alerte.quantiteLot} unité(s) seront enregistrées comme perte.`
    );

    if (!ok) {
        return;
    }

    try {

        const result = await U.api(STOCK_URL, {
            method: "POST",
            body: {
                action: "retirerLot",
                lot_id: alerte.lot_id
            }
        });

        U.toast(result.message, "success");

        await loadAlertes();

    } catch (error) {

        U.toast(error.message, "error");
    }
}
