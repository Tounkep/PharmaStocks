/* =========================================================
   PHARMASTOCK - GESTION DU STOCK
========================================================= */

const STOCK_URL = "../../backend/routes/stock.php";

const U = PharmaUtils;

const state = {
    lots: [],
    mouvements: [],
    produits: null,
    commandes: [],
    receptions: [],
    receptionsLoaded: false,
    delaiMinimumMois: 6,
    canEdit: false
};


document.addEventListener("DOMContentLoaded", async () => {

    bindEvents();

    const params = new URLSearchParams(window.location.search);

    if (params.get("q")) {
        document.getElementById("lotSearch").value = params.get("q");
        document.getElementById("globalSearch").value = params.get("q");
    }

    if (params.get("filtre") === "expiration") {
        document.getElementById("lotExpiryFilter").value = "30";
    }

    const user = await window.PharmaSession.ready;

    state.canEdit = window.PharmaSession.canEdit();

    if (!state.canEdit) {
        document
            .querySelectorAll(".edit-only")
            .forEach(element => element.classList.add("hidden"));
    }

    await loadLots();

    if (params.get("onglet") === "mouvements" || params.get("onglet") === "receptions") {
        showTab(params.get("onglet"));
    }

    if (user && state.canEdit) {

        if (params.get("action") === "lot") {
            openLotModal();
        }

        if (params.get("action") === "reception") {
            openReceptionModal(params.get("commande"));
        }
    }
});


/* =========================================================
   ÉVÉNEMENTS
========================================================= */

function bindEvents() {

    document.querySelectorAll(".tab-button").forEach(button => {
        button.addEventListener("click", () => showTab(button.dataset.tab));
    });

    ["lotSearch", "lotStatusFilter", "lotExpiryFilter"].forEach(id => {
        document.getElementById(id).addEventListener("input", renderLots);
    });

    document.getElementById("globalSearch").addEventListener("input", event => {
        document.getElementById("lotSearch").value = event.target.value;
        document.getElementById("movementSearch").value = event.target.value;
        renderLots();
        renderMovements();
    });

    document.getElementById("movementSearch").addEventListener("input", renderMovements);

    ["movementTypeFilter", "movementFrom", "movementTo"].forEach(id => {
        document.getElementById(id).addEventListener("change", loadMovements);
    });

    document.getElementById("openLotBtn").addEventListener("click", () => openLotModal());
    document.getElementById("openReceptionBtn").addEventListener("click", () => openReceptionModal());

    document.getElementById("lotForm").addEventListener("submit", submitLot);
    document.getElementById("movementForm").addEventListener("submit", submitMovement);
    document.getElementById("receptionForm").addEventListener("submit", submitReception);

    document.getElementById("movementType").addEventListener("change", updateMovementForm);
    document.getElementById("receptionOrder").addEventListener("change", renderReceptionLines);

    document.getElementById("receptionSearch").addEventListener("input", renderReceptions);
    document.getElementById("receptionFilter").addEventListener("change", renderReceptions);

    document.getElementById("receptionsTableBody").addEventListener("click", event => {

        const button = event.target.closest("button[data-reception]");

        if (button) {
            openReceptionDetail(button.dataset.reception);
        }
    });

    /*
     * Boutons d'action du tableau des lots.
     */
    document.getElementById("lotsTableBody").addEventListener("click", event => {

        const button = event.target.closest("button[data-action]");

        if (!button) {
            return;
        }

        const lot = state.lots.find(item => String(item.id) === button.dataset.id);

        if (!lot) {
            return;
        }

        if (button.dataset.action === "movement") {
            openMovementModal(lot);
        }

        if (button.dataset.action === "remove") {
            removeLot(lot);
        }
    });

    /*
     * Fermeture des fenêtres.
     */
    document.querySelectorAll(".modal-overlay").forEach(overlay => {

        overlay.addEventListener("click", event => {
            if (event.target === overlay || event.target.closest("[data-close]")) {
                closeModal(overlay);
            }
        });
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            document
                .querySelectorAll(".modal-overlay:not(.hidden)")
                .forEach(closeModal);
        }
    });
}


function showTab(name) {

    document.querySelectorAll(".tab-button").forEach(button => {
        button.classList.toggle("active", button.dataset.tab === name);
    });

    document.querySelectorAll(".tab-panel").forEach(panel => {
        panel.classList.toggle("hidden", panel.id !== `tab-${name}`);
    });

    if (name === "mouvements" && state.mouvements.length === 0) {
        loadMovements();
    }

    if (name === "receptions" && !state.receptionsLoaded) {
        loadReceptions();
    }
}


/* =========================================================
   LOTS
========================================================= */

async function loadLots() {

    try {

        const { data } = await U.api(STOCK_URL);

        state.lots = data.lots;

        renderResume(data.resume);
        renderLots();

    } catch (error) {

        document.getElementById("lotsTableBody").innerHTML =
            `<tr><td colspan="7" class="table-empty">${U.escape(error.message)}</td></tr>`;
    }
}


function renderResume(resume) {

    document.getElementById("resumeUnites").textContent = U.number(resume.unitesDisponibles);
    document.getElementById("resumeLots").textContent = `${U.number(resume.totalLots)} lots enregistrés`;
    document.getElementById("resumeValeur").textContent = U.currency(resume.valeurStock);
    document.getElementById("resumeExpiration").textContent = U.number(resume.lotsExpirationProche);
    document.getElementById("resumePerimes").textContent = U.number(resume.lotsPerimes);
}


function filteredLots() {

    const search = document.getElementById("lotSearch").value.trim().toLowerCase();
    const statut = document.getElementById("lotStatusFilter").value;
    const expiry = document.getElementById("lotExpiryFilter").value;

    return state.lots.filter(lot => {

        if (statut === "ACTIFS" && lot.quantite === 0) {
            return false;
        }

        if (statut && statut !== "ACTIFS" && lot.statut !== statut) {
            return false;
        }

        if (expiry && (lot.joursRestants > Number(expiry) || lot.quantite === 0)) {
            return false;
        }

        if (search) {

            const text = [
                lot.produit,
                lot.reference,
                lot.numeroLot,
                lot.categorie,
                lot.dosage
            ].join(" ").toLowerCase();

            if (!text.includes(search)) {
                return false;
            }
        }

        return true;
    });
}


function renderLots() {

    const tbody = document.getElementById("lotsTableBody");
    const lots = filteredLots();

    document.getElementById("lotsCount").textContent =
        `${lots.length} lot${lots.length > 1 ? "s" : ""} affiché${lots.length > 1 ? "s" : ""}`;

    if (lots.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="table-empty">Aucun lot ne correspond aux filtres.</td></tr>';
        return;
    }

    tbody.innerHTML = lots.map(lot => {

        const jours = lot.joursRestants;
        const actif = lot.quantite > 0;

        let rowClass = "";

        if (actif && jours < 0) {
            rowClass = "row-danger";
        } else if (actif && jours <= 30) {
            rowClass = "row-warning";
        }

        const expirationNote = actif && jours <= 90
            ? `<div class="muted">${U.daysLabel(jours)}</div>`
            : "";

        const actions = state.canEdit
            ? `
                <td>
                    <div class="table-actions">
                        <button class="mini-button" type="button" data-action="movement" data-id="${lot.id}">
                            Mouvement
                        </button>
                        ${actif && jours < 0
                            ? `<button class="mini-button danger" type="button" data-action="remove" data-id="${lot.id}">Retirer</button>`
                            : ""}
                    </div>
                </td>
            `
            : "";

        return `
            <tr class="${rowClass}">
                <td>
                    <a class="product-name" href="medicament-detail.html?id=${encodeURIComponent(lot.produit_id)}" style="color:inherit;text-decoration:none;">
                        ${U.escape(lot.produit)}
                    </a>
                    <div class="muted">${U.escape(lot.reference)} · ${U.escape(lot.categorie)}${lot.dosage ? " · " + U.escape(lot.dosage) : ""}</div>
                </td>
                <td>${U.escape(lot.numeroLot)}</td>
                <td class="${lot.quantite === 0 ? "stock-zero" : ""}"><strong>${U.number(lot.quantite)}</strong></td>
                <td>${U.date(lot.dateReception)}</td>
                <td>${U.date(lot.dateExpiration)}${expirationNote}</td>
                <td>${U.badge(U.LOT_STATUTS, lot.statut)}</td>
                ${actions}
            </tr>
        `;
    }).join("");
}


async function removeLot(lot) {

    const ok = confirm(
        `Retirer le lot ${lot.numeroLot} (${lot.produit}) ?\n\n` +
        `${lot.quantite} unité(s) seront enregistrées comme perte.`
    );

    if (!ok) {
        return;
    }

    try {

        const result = await U.api(STOCK_URL, {
            method: "POST",
            body: {
                action: "retirerLot",
                lot_id: lot.id
            }
        });

        U.toast(result.message, "success");

        await reloadAll();

    } catch (error) {

        U.toast(error.message, "error");
    }
}


/* =========================================================
   MOUVEMENTS
========================================================= */

async function loadMovements() {

    const params = new URLSearchParams({ action: "mouvements" });

    const type = document.getElementById("movementTypeFilter").value;
    const from = document.getElementById("movementFrom").value;
    const to = document.getElementById("movementTo").value;

    if (type) params.set("type", type);
    if (from) params.set("dateDebut", from);
    if (to) params.set("dateFin", to);

    try {

        const { data } = await U.api(`${STOCK_URL}?${params}`);

        state.mouvements = data.mouvements;

        renderMovements();

    } catch (error) {

        document.getElementById("movementsTableBody").innerHTML =
            `<tr><td colspan="7" class="table-empty">${U.escape(error.message)}</td></tr>`;
    }
}


function renderMovements() {

    const tbody = document.getElementById("movementsTableBody");
    const search = document.getElementById("movementSearch").value.trim().toLowerCase();

    const mouvements = state.mouvements.filter(mouvement => {

        if (!search) {
            return true;
        }

        return [
            mouvement.produit,
            mouvement.numeroLot,
            mouvement.motif,
            mouvement.prenom,
            mouvement.nom
        ].join(" ").toLowerCase().includes(search);
    });

    document.getElementById("movementsCount").textContent =
        `${mouvements.length} mouvement${mouvements.length > 1 ? "s" : ""}`;

    if (mouvements.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="table-empty">Aucun mouvement trouvé.</td></tr>';
        return;
    }

    tbody.innerHTML = mouvements.map(mouvement => {

        const sens = U.MOUVEMENTS[mouvement.type] ? U.MOUVEMENTS[mouvement.type][2] : 0;

        const quantite = sens > 0
            ? `<span class="qty-positive">+${U.number(mouvement.quantite)}</span>`
            : sens < 0
                ? `<span class="qty-negative">−${U.number(mouvement.quantite)}</span>`
                : `±${U.number(mouvement.quantite)}`;

        return `
            <tr>
                <td>${U.dateTime(mouvement.dateHeure)}</td>
                <td class="product-name">${U.escape(mouvement.produit)}</td>
                <td>${U.escape(mouvement.numeroLot)}</td>
                <td>${U.badge(U.MOUVEMENTS, mouvement.type)}</td>
                <td>${quantite}</td>
                <td>${U.escape(mouvement.motif || "-")}</td>
                <td>${U.escape(`${mouvement.prenom} ${mouvement.nom}`)}</td>
            </tr>
        `;
    }).join("");
}


/* =========================================================
   FENÊTRES
========================================================= */

function openModal(id) {

    const modal = document.getElementById(id);

    modal.classList.remove("hidden");

    modal.querySelectorAll(".form-error").forEach(error => error.classList.add("hidden"));

    return modal;
}


function closeModal(modal) {
    modal.classList.add("hidden");
}


function showFormError(id, message) {

    const element = document.getElementById(id);

    element.textContent = message;
    element.classList.remove("hidden");
}


/* =========================================================
   AJOUT D'UN LOT
========================================================= */

async function openLotModal() {

    document.getElementById("lotForm").reset();
    document.getElementById("lotReception").value = U.today();

    openModal("lotModal");

    const select = document.getElementById("lotProduct");

    if (state.produits === null) {

        select.innerHTML = '<option value="">Chargement...</option>';

        try {

            const { data } = await U.api(`${STOCK_URL}?action=produits`);

            state.produits = data.produits;

        } catch (error) {

            showFormError("lotError", error.message);
            return;
        }
    }

    select.innerHTML =
        '<option value="">Choisir un médicament</option>' +
        state.produits.map(produit => `
            <option value="${produit.id}">
                ${U.escape(produit.nom)}${produit.dosage ? " " + U.escape(produit.dosage) : ""} (${U.escape(produit.reference)})
            </option>
        `).join("");
}


async function submitLot(event) {

    event.preventDefault();

    const body = {
        action: "creerLot",
        produit_id: Number(document.getElementById("lotProduct").value),
        numeroLot: document.getElementById("lotNumber").value.trim(),
        quantite: Number(document.getElementById("lotQuantity").value),
        dateReception: document.getElementById("lotReception").value,
        dateExpiration: document.getElementById("lotExpiration").value,
        motif: document.getElementById("lotReason").value.trim()
    };

    if (body.dateExpiration <= body.dateReception) {
        showFormError("lotError", "La date d'expiration doit être postérieure à la date de réception.");
        return;
    }

    try {

        const result = await U.api(STOCK_URL, { method: "POST", body });

        closeModal(document.getElementById("lotModal"));

        U.toast(result.message, "success");

        await reloadAll();

    } catch (error) {

        showFormError("lotError", error.message);
    }
}


/* =========================================================
   MOUVEMENT SUR UN LOT
========================================================= */

function openMovementModal(lot) {

    document.getElementById("movementForm").reset();
    document.getElementById("movementLotId").value = lot.id;

    document.getElementById("movementLotInfo").innerHTML = `
        <strong>${U.escape(lot.produit)}</strong> · lot ${U.escape(lot.numeroLot)}<br>
        Stock actuel : <strong id="movementCurrent" data-value="${lot.quantite}">${U.number(lot.quantite)}</strong> unité(s)
        · expire le ${U.date(lot.dateExpiration)}
    `;

    updateMovementForm();

    openModal("movementModal");
}


function updateMovementForm() {

    const type = document.getElementById("movementType").value;
    const current = Number(document.getElementById("movementCurrent")?.dataset.value || 0);
    const input = document.getElementById("movementQuantity");
    const label = document.getElementById("movementQuantityLabel");
    const hint = document.getElementById("movementHint");

    if (type === "AJUSTEMENT") {

        label.textContent = "Quantité réelle comptée *";
        hint.textContent = `Le stock passera de ${current} à la valeur saisie.`;
        input.min = "0";
        input.removeAttribute("max");

    } else if (type === "SORTIE" || type === "PERTE") {

        label.textContent = "Quantité à retirer *";
        hint.textContent = `Maximum : ${current}.`;
        input.min = "1";
        input.max = String(current);

    } else {

        label.textContent = "Quantité à ajouter *";
        hint.textContent = "";
        input.min = "1";
        input.removeAttribute("max");
    }
}


async function submitMovement(event) {

    event.preventDefault();

    const body = {
        action: "mouvement",
        lot_id: Number(document.getElementById("movementLotId").value),
        type: document.getElementById("movementType").value,
        quantite: Number(document.getElementById("movementQuantity").value),
        motif: document.getElementById("movementReason").value.trim()
    };

    try {

        const result = await U.api(STOCK_URL, { method: "POST", body });

        closeModal(document.getElementById("movementModal"));

        U.toast(
            `${result.message} Nouveau stock du lot : ${result.data.nouvelleQuantite}.`,
            "success"
        );

        await reloadAll();

    } catch (error) {

        showFormError("movementError", error.message);
    }
}


/* =========================================================
   RÉCEPTION D'UNE COMMANDE (CONTRÔLE DES PRODUITS LIVRÉS)
========================================================= */

const MOTIFS_REFUS = [
    "Péremption trop proche",
    "Produit périmé",
    "Produit abîmé",
    "Non conforme à la commande",
    "Erreur de livraison",
    "Autre"
];


async function openReceptionModal(commandeId = null) {

    document.getElementById("receptionForm").reset();
    document.getElementById("receptionLines").innerHTML = "";

    openModal("receptionModal");

    const select = document.getElementById("receptionOrder");

    select.innerHTML = '<option value="">Chargement...</option>';

    try {

        const { data } = await U.api(`${STOCK_URL}?action=commandes`);

        state.commandes = data.commandes;
        state.delaiMinimumMois = Number(data.delaiMinimumMois || 6);

        document.getElementById("receptionDelay").textContent = state.delaiMinimumMois;

    } catch (error) {

        showFormError("receptionError", error.message);
        return;
    }

    if (state.commandes.length === 0) {

        select.innerHTML = '<option value="">Aucune commande en attente ou validée</option>';
        document.getElementById("receptionSubmit").disabled = true;
        return;
    }

    document.getElementById("receptionSubmit").disabled = false;

    select.innerHTML =
        '<option value="">Choisir une commande</option>' +
        state.commandes.map(commande => `
            <option value="${commande.id}">
                #${commande.id} · ${U.escape(commande.fournisseur)} · ${U.date(commande.dateCommande)}
                · ${U.currency(commande.montantTotal)} (${U.COMMANDES[commande.statut][0]})
            </option>
        `).join("");

    if (commandeId && state.commandes.some(commande => String(commande.id) === String(commandeId))) {
        select.value = String(commandeId);
        renderReceptionLines();
    }
}


function renderReceptionLines() {

    const id = document.getElementById("receptionOrder").value;
    const tbody = document.getElementById("receptionLines");
    const commande = state.commandes.find(item => String(item.id) === id);

    document.getElementById("receptionError").classList.add("hidden");

    if (!commande) {
        tbody.innerHTML = "";
        return;
    }

    const motifs = MOTIFS_REFUS
        .map(motif => `<option value="${U.escape(motif)}">${U.escape(motif)}</option>`)
        .join("");

    tbody.innerHTML = commande.details.map(detail => `
        <tr class="line-main" data-detail="${detail.id}" data-ordered="${detail.quantite}">
            <td>
                <strong>${U.escape(detail.produit)}</strong>
                <div class="muted">Réf. ${U.escape(detail.reference)} · commandé : ${U.number(detail.quantite)}</div>
            </td>
            <td><input type="text" name="numeroLot" maxlength="100" placeholder="N° de lot"></td>
            <td><input type="date" name="dateExpiration"></td>
            <td><input type="number" name="quantiteAcceptee" min="0" step="1" value="${detail.quantite}"></td>
            <td><input type="number" name="quantiteRefusee" min="0" step="1" value="0"></td>
            <td>
                <select name="motifRefus" disabled>
                    <option value="">—</option>
                    ${motifs}
                </select>
            </td>
        </tr>
        <tr class="line-note" data-note-for="${detail.id}">
            <td colspan="6"><span class="note-text muted">Saisissez la date d'expiration indiquée sur les boîtes.</span></td>
        </tr>
    `).join("");

    tbody.querySelectorAll("tr.line-main").forEach(row => {

        row.querySelector('[name="dateExpiration"]')
            .addEventListener("change", () => checkExpiry(row));

        row.querySelectorAll('[name="quantiteAcceptee"], [name="quantiteRefusee"]')
            .forEach(input => input.addEventListener("input", () => updateLine(row)));

        updateLine(row);
    });
}


/*
 * Contrôle de la date d'expiration d'une ligne :
 * - périmé           : acceptation impossible, tout est refusé
 * - moins de N mois  : tout est refusé par défaut, le pharmacien
 *                      peut cocher « Accepter quand même »
 * - sinon            : ligne normale
 */
function checkExpiry(row) {

    const expiry = row.querySelector('[name="dateExpiration"]').value;
    const accepted = row.querySelector('[name="quantiteAcceptee"]');
    const refused = row.querySelector('[name="quantiteRefusee"]');
    const motif = row.querySelector('[name="motifRefus"]');

    const status = expiryStatus(expiry);
    const forced = row.dataset.forced === "1";

    const autoRefuse = reason => {

        if (row.dataset.autoRefused !== "1") {
            row.dataset.autoRefused = "1";
            refused.value = Number(refused.value || 0) + Number(accepted.value || 0);
            accepted.value = 0;
        }

        motif.value = reason;
    };

    const restore = () => {

        if (row.dataset.autoRefused === "1") {
            accepted.value = Number(accepted.value || 0) + Number(refused.value || 0);
            refused.value = 0;
            motif.value = "";
            row.dataset.autoRefused = "";
        }
    };

    accepted.disabled = false;

    if (status.state === "expired") {

        row.dataset.forced = "";
        autoRefuse("Produit périmé");
        accepted.disabled = true;

    } else if (status.state === "short" && !forced) {

        autoRefuse("Péremption trop proche");

    } else {

        restore();
    }

    updateLine(row);
}


function expiryStatus(expiry) {

    if (!expiry) {
        return { state: "empty" };
    }

    const today = U.today();
    const limit = new Date(today + "T00:00:00");

    limit.setMonth(limit.getMonth() + (state.delaiMinimumMois || 6));

    const limitIso = new Date(limit - limit.getTimezoneOffset() * 60000).toISOString().slice(0, 10);

    const days = Math.round(
        (new Date(expiry + "T00:00:00") - new Date(today + "T00:00:00")) / 86400000
    );

    if (expiry <= today) {
        return { state: "expired", days };
    }

    if (expiry < limitIso) {
        return { state: "short", days };
    }

    return { state: "ok", days };
}


/*
 * Met à jour la ligne de note sous le produit : avertissement
 * de péremption, case « Accepter quand même », quantité manquante.
 */
function updateLine(row) {

    const note = document.querySelector(`tr[data-note-for="${row.dataset.detail}"]`);
    const ordered = Number(row.dataset.ordered);
    const expiry = row.querySelector('[name="dateExpiration"]').value;
    const accepted = Number(row.querySelector('[name="quantiteAcceptee"]').value || 0);
    const refused = Number(row.querySelector('[name="quantiteRefusee"]').value || 0);
    const motif = row.querySelector('[name="motifRefus"]');

    motif.disabled = refused === 0;

    if (refused === 0) {
        motif.value = "";
    }

    const status = expiryStatus(expiry);
    const parts = [];

    if (status.state === "empty") {

        parts.push('<span class="muted">Saisissez la date d\'expiration indiquée sur les boîtes.</span>');

    } else if (status.state === "expired") {

        parts.push('<span class="line-warning-text">⛔ Produit déjà périmé : il ne peut pas être accepté.</span>');

    } else if (status.state === "short") {

        parts.push(
            `<span class="line-warning-text">⚠ Expire le ${U.date(expiry)}, dans ${status.days} jours `
            + `(moins de ${state.delaiMinimumMois} mois).</span>`
            + `<label class="force-label"><input type="checkbox" data-force ${row.dataset.forced === "1" ? "checked" : ""}>`
            + "Accepter quand même</label>"
        );

    } else {

        parts.push(`<span class="line-ok-text">✓ Expire le ${U.date(expiry)}, dans ${Math.floor(status.days / 30)} mois.</span>`);
    }

    const missing = ordered - accepted - refused;

    if (missing > 0) {
        parts.push(`<span class="muted"> · ${missing} non livrée(s) (manquant)</span>`);
    } else if (missing < 0) {
        parts.push(`<span class="line-warning-text"> · ${-missing} de plus que la quantité commandée</span>`);
    }

    note.firstElementChild.innerHTML = parts.join("");

    const warning = status.state === "expired" || (status.state === "short" && row.dataset.forced !== "1");

    row.classList.toggle("line-warning", warning);
    note.classList.toggle("line-warning", warning);

    const force = note.querySelector("[data-force]");

    if (force) {
        force.addEventListener("change", () => {
            row.dataset.forced = force.checked ? "1" : "";
            checkExpiry(row);
        });
    }
}


async function submitReception(event) {

    event.preventDefault();

    const commandeId = Number(document.getElementById("receptionOrder").value);
    const rows = [...document.querySelectorAll("#receptionLines tr.line-main")];

    if (!commandeId || rows.length === 0) {
        showFormError("receptionError", "Choisissez une commande à réceptionner.");
        return;
    }

    const lignes = [];

    for (const row of rows) {

        const produit = row.querySelector("strong").textContent;

        const ligne = {
            detail_id: Number(row.dataset.detail),
            numeroLot: row.querySelector('[name="numeroLot"]').value.trim(),
            dateExpiration: row.querySelector('[name="dateExpiration"]').value,
            quantiteAcceptee: Number(row.querySelector('[name="quantiteAcceptee"]').value || 0),
            quantiteRefusee: Number(row.querySelector('[name="quantiteRefusee"]').value || 0),
            motifRefus: row.querySelector('[name="motifRefus"]').value,
            forcer: row.dataset.forced === "1"
        };

        if (ligne.quantiteAcceptee > 0 && (!ligne.numeroLot || !ligne.dateExpiration)) {
            showFormError("receptionError", `${produit} : le numéro de lot et la date d'expiration sont obligatoires pour accepter le produit.`);
            return;
        }

        if (ligne.quantiteRefusee > 0 && !ligne.motifRefus) {
            showFormError("receptionError", `${produit} : choisissez le motif du refus.`);
            return;
        }

        lignes.push(ligne);
    }

    const total = lignes.reduce((sum, ligne) => sum + ligne.quantiteAcceptee + ligne.quantiteRefusee, 0);

    if (total === 0) {
        showFormError("receptionError", "Aucun produit reçu : indiquez au moins une quantité acceptée ou refusée.");
        return;
    }

    const button = document.getElementById("receptionSubmit");

    button.disabled = true;

    try {

        const result = await U.api(STOCK_URL, {
            method: "POST",
            body: {
                action: "receptionnerCommande",
                commande_id: commandeId,
                lignes
            }
        });

        closeModal(document.getElementById("receptionModal"));

        U.toast(result.message, "success");

        await reloadAll();

    } catch (error) {

        showFormError("receptionError", error.message);

    } finally {

        button.disabled = false;
    }
}


/* =========================================================
   HISTORIQUE DES RÉCEPTIONS
========================================================= */

async function loadReceptions() {

    try {

        const { data } = await U.api(`${STOCK_URL}?action=receptions`);

        state.receptions = data.receptions;
        state.receptionsLoaded = true;

        renderReceptions();

    } catch (error) {

        document.getElementById("receptionsTableBody").innerHTML =
            `<tr><td colspan="8" class="table-empty">${U.escape(error.message)}</td></tr>`;
    }
}


function renderReceptions() {

    const tbody = document.getElementById("receptionsTableBody");
    const search = document.getElementById("receptionSearch").value.trim().toLowerCase();
    const filter = document.getElementById("receptionFilter").value;

    const receptions = state.receptions.filter(reception => {

        if (filter === "refus" && reception.totalRefuse === 0) {
            return false;
        }

        if (filter === "manquant" && reception.totalManquant === 0) {
            return false;
        }

        if (search) {

            const text = [
                "#" + reception.id,
                "commande " + reception.id,
                reception.fournisseur,
                reception.receptionnePar,
                ...reception.produits.flatMap(produit => [
                    produit.produit,
                    produit.reference,
                    ...produit.acceptes.map(item => item.numeroLot),
                    ...produit.refus.map(item => item.numeroLot)
                ])
            ].join(" ").toLowerCase();

            return text.includes(search);
        }

        return true;
    });

    document.getElementById("receptionsCount").textContent =
        `${receptions.length} réception${receptions.length > 1 ? "s" : ""}`;

    if (receptions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="table-empty">Aucune réception enregistrée.</td></tr>';
        return;
    }

    tbody.innerHTML = receptions.map(reception => `
        <tr class="${reception.totalRefuse > 0 ? "row-warning" : ""}">
            <td>${U.dateTime(reception.dateReception)}</td>
            <td class="product-name">#${U.escape(reception.id)}</td>
            <td>${U.escape(reception.fournisseur)}</td>
            <td>${U.escape(reception.receptionnePar)}</td>
            <td><span class="qty-positive">${U.number(reception.totalAccepte)}</span></td>
            <td>${reception.totalRefuse > 0 ? `<span class="qty-negative">${U.number(reception.totalRefuse)}</span>` : "0"}</td>
            <td>${reception.totalManquant > 0 ? `<span class="badge badge-orange">${U.number(reception.totalManquant)}</span>` : "0"}</td>
            <td><button class="mini-button" type="button" data-reception="${reception.id}">Détails</button></td>
        </tr>
    `).join("");
}


function openReceptionDetail(id) {

    const reception = state.receptions.find(item => String(item.id) === String(id));

    if (!reception) {
        return;
    }

    document.getElementById("receptionDetailTitle").textContent = `Réception de la commande #${reception.id}`;

    document.getElementById("receptionDetailInfo").innerHTML = `
        <strong>${U.escape(reception.fournisseur)}</strong> · commandée le ${U.date(reception.dateCommande)}
        · réceptionnée le ${U.dateTime(reception.dateReception)} par ${U.escape(reception.receptionnePar)}<br>
        ${U.number(reception.totalAccepte)} acceptée(s), ${U.number(reception.totalRefuse)} refusée(s),
        ${U.number(reception.totalManquant)} manquante(s)
    `;

    const list = items => items.length === 0
        ? '<span class="muted">—</span>'
        : `<ul class="reception-detail-list">${items.join("")}</ul>`;

    document.getElementById("receptionDetailLines").innerHTML = reception.produits.map(produit => `
        <tr>
            <td>
                <strong>${U.escape(produit.produit)}</strong>
                <div class="muted">Réf. ${U.escape(produit.reference)}</div>
            </td>
            <td>${U.number(produit.quantiteCommandee)}</td>
            <td>${list(produit.acceptes.map(item => `
                <li>
                    <span class="qty-positive">${U.number(item.quantite)}</span>
                    · lot ${U.escape(item.numeroLot)} · exp. ${U.date(item.dateExpiration)}
                    ${item.motif ? `<br><span class="line-warning-text">${U.escape(item.motif)}</span>` : ""}
                </li>
            `))}</td>
            <td>${list(produit.refus.map(item => `
                <li>
                    <span class="qty-negative">${U.number(item.quantite)}</span>
                    · ${U.escape(item.motif)}
                    ${item.dateExpiration ? `<br><span class="muted">exp. ${U.date(item.dateExpiration)}${item.numeroLot ? " · lot " + U.escape(item.numeroLot) : ""}</span>` : ""}
                </li>
            `))}</td>
            <td>${produit.quantiteManquante > 0 ? `<span class="badge badge-orange">${U.number(produit.quantiteManquante)}</span>` : "0"}</td>
        </tr>
    `).join("");

    openModal("receptionDetailModal");
}


/* =========================================================
   RECHARGEMENT
========================================================= */

async function reloadAll() {

    await loadLots();

    if (!document.getElementById("tab-mouvements").classList.contains("hidden") || state.mouvements.length > 0) {
        await loadMovements();
    }

    if (state.receptionsLoaded) {
        await loadReceptions();
    }
}
