/* =========================================================
   PHARMASTOCK - OUTILS COMMUNS (ESPACE PHARMACIEN)
========================================================= */

const PharmaUtils = {

    /* =====================================================
       APPEL À L'API
    ====================================================== */

    async api(url, options = {}) {

        const config = {
            method: options.method || "GET",
            credentials: "include",
            headers: {
                "Accept": "application/json"
            }
        };

        if (options.body !== undefined) {
            config.headers["Content-Type"] = "application/json";
            config.body = JSON.stringify(options.body);
        }

        const response = await fetch(url, config);

        if (response.status === 401) {
            window.location.href = "connexion.html";
            throw new Error("Session expirée.");
        }

        let result;

        try {
            result = await response.json();
        } catch (error) {
            throw new Error(`Réponse invalide du serveur (HTTP ${response.status}).`);
        }

        if (!response.ok || !result.success) {
            throw new Error(result.message || `Erreur HTTP ${response.status}`);
        }

        return result;
    },


    /* =====================================================
       FORMATAGE
    ====================================================== */

    number(value) {
        return new Intl.NumberFormat("fr-FR").format(Number(value || 0));
    },

    currency(value) {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR"
        }).format(Number(value || 0));
    },

    date(value) {

        if (!value) {
            return "-";
        }

        const parts = String(value).slice(0, 10).split("-");

        return parts.length === 3
            ? `${parts[2]}/${parts[1]}/${parts[0]}`
            : String(value);
    },

    dateTime(value) {

        if (!value) {
            return "-";
        }

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString("fr-FR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    },

    today() {

        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;

        return new Date(now - offset).toISOString().slice(0, 10);
    },

    daysLabel(days) {

        days = Number(days);

        if (days < 0) {
            return `périmé depuis ${Math.abs(days)} j`;
        }

        if (days === 0) {
            return "expire aujourd'hui";
        }

        return `dans ${days} j`;
    },

    escape(value) {

        if (value === null || value === undefined) {
            return "";
        }

        return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    },


    /* =====================================================
       LIBELLÉS ET BADGES
    ====================================================== */

    LOT_STATUTS: {
        DISPONIBLE: ["Disponible", "badge-green"],
        EPUISE: ["Épuisé", "badge-grey"],
        PERIME: ["Périmé", "badge-red"]
    },

    MOUVEMENTS: {
        ENTREE: ["Entrée", "badge-green", +1],
        RETOUR: ["Retour", "badge-blue", +1],
        SORTIE: ["Sortie", "badge-orange", -1],
        PERTE: ["Perte", "badge-red", -1],
        AJUSTEMENT: ["Ajustement", "badge-purple", 0]
    },

    ALERTES: {
        RUPTURE: ["Rupture", "badge-red", "⛔"],
        EXPIRE: ["Lot périmé", "badge-red", "☠"],
        STOCK_FAIBLE: ["Stock faible", "badge-orange", "⚠"],
        EXPIRATION_PROCHE: ["Expiration proche", "badge-purple", "⏳"]
    },

    COMMANDES: {
        EN_ATTENTE: ["En attente", "badge-orange"],
        VALIDEE: ["Validée", "badge-blue"],
        RECUE: ["Reçue", "badge-green"],
        ANNULEE: ["Annulée", "badge-grey"]
    },

    badge(map, key) {

        const entry = map[key] || [key, "badge-grey"];

        return `<span class="badge ${entry[1]}">${this.escape(entry[0])}</span>`;
    },


    /* =====================================================
       NOTIFICATIONS
    ====================================================== */

    toast(message, type = "info") {

        let container = document.querySelector(".toast-container");

        if (!container) {
            container = document.createElement("div");
            container.className = "toast-container";
            document.body.appendChild(container);
        }

        const toast = document.createElement("div");

        toast.className = `toast ${type}`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => toast.remove(), 4000);
    }
};
