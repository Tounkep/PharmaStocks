const API_URL = "../../backend/routes/medicaments.php";
let currentProductId = null;
let currentLots = [];


document.addEventListener("DOMContentLoaded", () => {

    const params = new URLSearchParams(
        window.location.search
    );

    const id = params.get("id");


    if (!id) {

        alert("Aucun médicament sélectionné.");

        window.location.href = "medicaments.html";

        return;
    }


    loadMedicament(id);

    setupEvents();

});


/* =========================
   CHARGER LE MÉDICAMENT
========================= */

async function loadMedicament(id) {

    currentProductId = Number(id);

    try {

        const response = await fetch(
            `${API_URL}?id=${encodeURIComponent(id)}`,
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


        const result = await response.json();


        if (!result.success) {

            throw new Error(
                result.message ||
                "Impossible de récupérer le médicament."
            );
        }


        const medicament = result.data?.produit
            ? {
                ...result.data.produit,
                lots: result.data.lots || [],
                mouvements: result.data.historique || []
            }
            : result.data || {};


        displayMedicament(medicament);


    } catch (error) {

        console.error(error);

        alert(
            "Impossible de charger le médicament."
        );

    }

}


/* =========================
   AFFICHER LE MÉDICAMENT
========================= */

function displayMedicament(medicament) {

    document.getElementById(
        "medicamentNom"
    ).textContent =
        `${medicament.nom} ${medicament.dosage || ""}`;


    document.getElementById(
        "medicamentReference"
    ).textContent =
        medicament.reference || "-";


    const etat = document.getElementById(
        "medicamentEtat"
    );

    etat.textContent =
        formatEtat(medicament.etat);

    etat.className =
        `status-badge ${getEtatClass(medicament.etat)}`;


    /* statistiques */

    document.getElementById(
        "stockTotal"
    ).textContent =
        formatNumber(medicament.stockTotal);


    document.getElementById(
        "prixVente"
    ).textContent =
        formatCurrency(medicament.prixVente);


    document.getElementById(
        "seuilMinimum"
    ).textContent =
        formatNumber(medicament.seuilMinimum);


    document.getElementById(
        "expiration"
    ).textContent =
        formatDate(medicament.prochaineExpiration);


    /* informations */

    document.getElementById(
        "infoReference"
    ).textContent =
        medicament.reference || "-";


    document.getElementById(
        "infoCategorie"
    ).textContent =
        medicament.categorie || "-";


    document.getElementById(
        "infoDosage"
    ).textContent =
        medicament.dosage || "-";


    document.getElementById(
        "infoForme"
    ).textContent =
        medicament.forme || "-";


    document.getElementById(
        "infoPrixAchat"
    ).textContent =
        formatCurrency(medicament.prixAchat);


    document.getElementById(
        "infoPrixVente"
    ).textContent =
        formatCurrency(medicament.prixVente);


    document.getElementById(
        "infoSeuil"
    ).textContent =
        formatNumber(medicament.seuilMinimum);


    document.getElementById(
        "description"
    ).textContent =
        medicament.description ||
        "Aucune description disponible.";


    displayLots(
        medicament.lots || []
    );


    displayMouvements(
        medicament.mouvements || []
    );
}


/* =========================
   LOTS
========================= */

function displayLots(lots) {

    currentLots = Array.isArray(lots) ? lots : [];

    const tbody =
        document.getElementById(
            "lotsTable"
        );


    if (!currentLots.length) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    Aucun lot enregistré pour ce médicament.
                </td>
            </tr>
        `;

        populateMovementLotSelector();
        return;
    }


    tbody.innerHTML = currentLots.map(lot => {

        return `
            <tr>

                <td>
                    <strong>
                        ${escapeHtml(lot.numeroLot)}
                    </strong>
                </td>

                <td>
                    ${formatNumber(lot.quantite)}
                </td>

                <td>
                    ${formatDate(lot.dateReception)}
                </td>

                <td>
                    ${formatDate(lot.dateExpiration)}
                </td>

                <td>
                    <span class="lot-status ${getLotClass(lot.statut)}">
                        ${formatLotStatus(lot.statut)}
                    </span>
                </td>

                <td>
                    <button
                        type="button"
                        class="mini-button"
                        data-action="edit-lot"
                        data-lot-id="${lot.id}"
                    >
                        Modifier
                    </button>
                </td>

            </tr>
        `;

    }).join("");

    populateMovementLotSelector();
}


/* =========================
   MOUVEMENTS
========================= */

function displayMouvements(mouvements) {

    const tbody =
        document.getElementById(
            "mouvementsTable"
        );


    if (!mouvements.length) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    Aucun mouvement de stock.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML = mouvements.map(mouvement => {

        return `
            <tr>

                <td class="${getMovementClass(mouvement.type)}">
                    ${formatMovement(mouvement.type)}
                </td>

                <td>
                    ${formatNumber(mouvement.quantite)}
                </td>

                <td>
                    ${escapeHtml(mouvement.numeroLot || "-")}
                </td>

                <td>
                    ${formatDateTime(mouvement.dateHeure)}
                </td>

                <td>
                    ${escapeHtml(mouvement.motif || "-")}
                </td>

                <td>
                    ${escapeHtml(mouvement.utilisateur || "-")}
                </td>

            </tr>
        `;

    }).join("");
}


/* =========================
   EVENEMENTS
========================= */

function setupEvents() {

    document.getElementById(
        "backButton"
    ).addEventListener(
        "click",
        () => {

            window.location.href =
                "medicaments.html";

        }
    );


    document.getElementById(
        "editButton"
    ).addEventListener(
        "click",
        () => {

            const id =
                new URLSearchParams(
                    window.location.search
                ).get("id");

            window.location.href =
                `medicaments.html?edit=${id}`;

        }
    );


    document.getElementById("addLotBtn").addEventListener("click", () => {
        document.getElementById("lotDrawerTitle").textContent = "Ajouter un lot";
        resetLotForm();
        openLotDrawer();
    });


    document.getElementById("closeLotDrawerBtn").addEventListener("click", closeLotDrawer);
    document.getElementById("cancelLotDrawerBtn").addEventListener("click", closeLotDrawer);

    document.getElementById("lotDrawerOverlay").addEventListener("click", event => {
        if (event.target === event.currentTarget) {
            closeLotDrawer();
        }
    });


    document.getElementById("lotForm").addEventListener("submit", async event => {
        event.preventDefault();

        const lotId = document.getElementById("lotId").value;
        const payload = {
            numeroLot: document.getElementById("lotNumber").value.trim(),
            quantite: Number(document.getElementById("lotQuantity").value || 0),
            dateReception: document.getElementById("lotReception").value,
            dateExpiration: document.getElementById("lotExpiration").value,
            produit_id: currentProductId
        };

        if (!payload.numeroLot || !payload.dateReception || !payload.dateExpiration) {
            alert("Merci de remplir les champs du lot.");
            return;
        }

        try {
            const method = lotId ? "PUT" : "POST";

            if (lotId) {
                payload.action = "updateLot";
                payload.lot_id = Number(lotId);
            } else {
                payload.action = "createLot";
            }

            const result = await requestJson(method, payload);

            if (!result.success) {
                throw new Error(result.message || "Erreur lors de l'enregistrement du lot.");
            }

            closeLotDrawer();
            resetLotForm();
            await loadMedicament(currentProductId);
            alert(lotId ? "Lot modifié avec succès." : "Lot ajouté avec succès.");

        } catch (error) {
            console.error(error);
            alert(error.message || "Impossible d'enregistrer le lot.");
        }
    });


    document.getElementById("movementForm").addEventListener("submit", async event => {
        event.preventDefault();

        const lotId = Number(document.getElementById("movementLotId").value);
        const type = document.getElementById("movementType").value;
        const quantite = Number(document.getElementById("movementQuantity").value || 0);

        if (!lotId || !type || quantite <= 0) {
            alert("Sélectionnez un lot et une quantité valide.");
            return;
        }

        try {
            const result = await requestJson("POST", {
                action: "stockMovement",
                lot_id: lotId,
                type,
                quantite,
                motif: document.getElementById("movementMotif").value.trim() || "Mouvement manuel"
            });

            if (!result.success) {
                throw new Error(result.message || "Erreur lors du mouvement de stock.");
            }

            document.getElementById("movementForm").reset();
            document.getElementById("movementQuantity").value = "1";
            await loadMedicament(currentProductId);
            alert("Mouvement enregistré avec succès.");

        } catch (error) {
            console.error(error);
            alert(error.message || "Impossible d'enregistrer le mouvement.");
        }
    });


    document.addEventListener("click", event => {
        const button = event.target.closest("[data-action='edit-lot']");

        if (!button) {
            return;
        }

        const lot = currentLots.find(item => Number(item.id) === Number(button.dataset.lotId));

        if (!lot) {
            return;
        }

        setLotForm(lot);
    });


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


function setLotForm(lot) {
    document.getElementById("lotDrawerTitle").textContent = "Modifier le lot";
    document.getElementById("lotId").value = lot.id || "";
    document.getElementById("lotNumber").value = lot.numeroLot || "";
    document.getElementById("lotQuantity").value = lot.quantite ?? 0;
    document.getElementById("lotReception").value = lot.dateReception || "";
    document.getElementById("lotExpiration").value = lot.dateExpiration || "";
    openLotDrawer();
}


function resetLotForm() {
    document.getElementById("lotForm").reset();
    document.getElementById("lotId").value = "";
    document.getElementById("lotQuantity").value = "0";
}


function openLotDrawer() {
    document.getElementById("lotDrawerOverlay").classList.remove("hidden");
}


function closeLotDrawer() {
    document.getElementById("lotDrawerOverlay").classList.add("hidden");
}


function populateMovementLotSelector() {
    const select = document.getElementById("movementLotId");

    if (!select) {
        return;
    }

    if (!currentLots.length) {
        select.innerHTML = '<option value="">Aucun lot disponible</option>';
        return;
    }

    select.innerHTML = currentLots.map(lot => `
        <option value="${lot.id}">
            ${escapeHtml(lot.numeroLot)} — ${formatNumber(lot.quantite)} unité(s)
        </option>
    `).join("");
}


async function requestJson(method, payload) {
    const response = await fetch(API_URL, {
        method,
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        credentials: "include",
        body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Une erreur est survenue.");
    }

    return result;
}


/* =========================
   OUTILS
========================= */

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


function formatDate(date) {

    if (!date) {
        return "-";
    }

    const parts =
        String(date).split("-");

    if (parts.length !== 3) {
        return date;
    }

    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}


function formatDateTime(date) {

    if (!date) {
        return "-";
    }

    return String(date)
        .replace("T", " ")
        .substring(0, 16);
}


function formatEtat(etat) {

    const values = {

        NORMAL: "Normal",

        FAIBLE: "Faible",

        RUPTURE: "Rupture",

        NON_APPROVISIONNE: "Non approvisionné"

    };

    return values[etat] || etat;
}


function getEtatClass(etat) {

    const classes = {

        NORMAL: "status-normal",

        FAIBLE: "status-faible",

        RUPTURE: "status-rupture",

        NON_APPROVISIONNE: "status-non-approvisionne"

    };

    return classes[etat] || "";
}


function formatLotStatus(statut) {

    const values = {

        DISPONIBLE: "Disponible",

        EPUISE: "Épuisé",

        PERIME: "Périmé"

    };

    return values[statut] || statut;
}


function getLotClass(statut) {

    const classes = {

        DISPONIBLE: "lot-disponible",

        EPUISE: "lot-epuise",

        PERIME: "lot-perime"

    };

    return classes[statut] || "";
}


function formatMovement(type) {

    const values = {

        ENTREE: "Entrée",

        SORTIE: "Sortie",

        PERTE: "Perte",

        RETOUR: "Retour",

        AJUSTEMENT: "Ajustement"

    };

    return values[type] || type;
}


function getMovementClass(type) {

    const classes = {

        ENTREE: "movement-entry",

        SORTIE: "movement-sortie",

        PERTE: "movement-perte",

        RETOUR: "movement-retour",

        AJUSTEMENT: "movement-ajustement"

    };

    return classes[type] || "";
}


function escapeHtml(value) {

    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}