/* =========================================================
   PHARMASTOCK - MISE EN PAGE SELON LE RÔLE
   ---------------------------------------------------------
   À inclure sur les pages partagées entre les rôles
   (avant le script propre à la page).

   - récupère l'utilisateur connecté (session.php)
   - construit le menu latéral du pharmacien
   - affiche le nom, l'avatar et le rôle
   - met à jour la cloche des alertes
   - masque les actions interdites au pharmacien

   Les autres scripts peuvent attendre l'utilisateur avec :
       const user = await window.PharmaSession.ready;
========================================================= */

(function () {

    const SESSION_URL = "../../backend/auth/session.php";
    const ALERTES_URL = "../../backend/routes/alertes.php";


    const ROLE_LABELS = {
        ADMIN: "Administrateur",
        PHARMACIEN: "Pharmacien",
        EMPLOYE: "Employé"
    };


    const MENUS = {

        PHARMACIEN: [
            { href: "dashboard-pharmacien.html", icon: "⌂", label: "Dashboard" },
            { href: "medicaments.html", icon: "💊", label: "Médicaments", also: ["medicament-detail.html"] },
            { href: "stock.html", icon: "📦", label: "Stock" },
            { href: "alertes.html", icon: "🔔", label: "Alertes", counter: true },
            { href: "ventes.html", icon: "🛒", label: "Ventes" },
            { href: "commandes.html", icon: "📒", label: "Commandes" },
            { href: "fournisseurs.html", icon: "🚚", label: "Fournisseurs" }
        ],

        ADMIN: [
            { href: "dashboard-admin.html", icon: "⌂", label: "Dashboard" },
            { href: "medicaments.html", icon: "💊", label: "Médicaments", also: ["medicament-detail.html"] },
            { href: "stock.html", icon: "📦", label: "Stock" },
            { href: "alertes.html", icon: "🔔", label: "Alertes", counter: true },
            { href: "ventes.html", icon: "🛒", label: "Ventes" },
            { href: "fournisseurs.html", icon: "🚚", label: "Fournisseurs" },
            { href: "commandes.html", icon: "📒", label: "Commandes" },
            { href: "utilisateurs.html", icon: "👥", label: "Utilisateurs" }
        ],

        EMPLOYE: [
            { href: "medicaments.html", icon: "💊", label: "Médicaments", also: ["medicament-detail.html"] },
            { href: "stock.html", icon: "📦", label: "Stock" },
            { href: "alertes.html", icon: "🔔", label: "Alertes", counter: true },
            { href: "ventes.html", icon: "🛒", label: "Ventes" }
        ]
    };


    const BASE_CSS = `
        .menu-counter {
            margin-left: auto;
            min-width: 20px;
            padding: 2px 6px;
            border-radius: 20px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .menu-counter.hidden {
            display: none;
        }
    `;


    /*
     * Actions masquées pour le pharmacien
     * sur les pages de ses collègues.
     */
    const PHARMACIEN_CSS = `
        body.role-pharmacien #addSupplierBtn,
        body.role-pharmacien .suppliers-table th:last-child,
        body.role-pharmacien .suppliers-table td:last-child {
            display: none !important;
        }
    `;


    let resolveReady;

    window.PharmaSession = {

        user: null,

        ready: new Promise(resolve => {
            resolveReady = resolve;
        }),

        canEdit() {
            const role = this.user ? this.user.role : "";
            return role === "ADMIN" || role === "PHARMACIEN";
        },

        roleLabel(role) {
            return ROLE_LABELS[role] || role || "";
        }
    };


    document.addEventListener("DOMContentLoaded", init);


    async function init() {

        let user = null;

        try {

            const response = await fetch(SESSION_URL, {
                method: "GET",
                headers: { "Accept": "application/json" },
                credentials: "include"
            });

            if (response.status === 401) {
                window.location.href = "connexion.html";
                return;
            }

            const result = await response.json();

            if (result.success && result.data) {
                user = result.data.utilisateur;
            }

        } catch (error) {

            console.error("Session indisponible :", error);
        }

        window.PharmaSession.user = user;

        if (user) {
            applyLayout(user);
        }

        resolveReady(user);
    }


    function applyLayout(user) {

        const role = user.role || "";

        document.body.classList.add(
            "role-" + role.toLowerCase()
        );

        const style = document.createElement("style");

        style.textContent =
            BASE_CSS + (role === "PHARMACIEN" ? PHARMACIEN_CSS : "");

        document.head.appendChild(style);

        buildMenu(role);
        fillProfile(user);

        if (role === "ADMIN" || role === "PHARMACIEN") {
            loadAlertCount();
        }
    }


    /* =====================================================
       MENU
    ====================================================== */

    function buildMenu(role) {

        const nav = document.querySelector(".sidebar-menu");

        if (!nav || !MENUS[role]) {
            return;
        }

        /*
         * Sur les pages de l'administrateur, le menu
         * d'origine est conservé. Il n'est construit que
         * pour les autres rôles ou si la page n'en a pas.
         */
        const pageHasMenu = nav.children.length > 0;

        if (role === "ADMIN" && pageHasMenu) {
            return;
        }

        const currentPage =
            window.location.pathname.split("/").pop() || "";

        nav.innerHTML = MENUS[role].map(item => {

            const active =
                item.href === currentPage ||
                (item.also || []).includes(currentPage);

            const counter = item.counter
                ? '<span class="menu-counter hidden" data-alert-counter>0</span>'
                : "";

            return `
                <a href="${item.href}" class="menu-item${active ? " active" : ""}">
                    <span class="menu-icon">${item.icon}</span>
                    <span>${item.label}</span>
                    ${counter}
                </a>
            `;
        }).join("");
    }


    /* =====================================================
       PROFIL
    ====================================================== */

    function fillProfile(user) {

        const fullName =
            `${user.prenom || ""} ${user.nom || ""}`.trim();

        const nameElement =
            document.getElementById("adminName") ||
            document.getElementById("userName");

        if (nameElement && fullName) {
            nameElement.textContent = fullName;
        }

        document
            .querySelectorAll(".user-profile .avatar")
            .forEach(avatar => {
                if (!avatar.querySelector("img")) {
                    avatar.textContent =
                        (user.prenom || fullName || "?")
                            .charAt(0)
                            .toUpperCase();
                }
            });

        const roleElement =
            document.getElementById("userRole") ||
            document.querySelector(".user-info small");

        if (roleElement) {
            roleElement.textContent = ROLE_LABELS[user.role] || user.role;
        }
    }


    /* =====================================================
       ALERTES (CLOCHE + COMPTEUR DU MENU)
    ====================================================== */

    async function loadAlertCount() {

        try {

            const response = await fetch(
                `${ALERTES_URL}?statut=NOUVELLE`,
                { credentials: "include" }
            );

            if (!response.ok) {
                return;
            }

            const result = await response.json();

            const total =
                result.success && result.data
                    ? Number(result.data.compteurs.total || 0)
                    : 0;

            window.PharmaSession.updateAlertCount(total);

        } catch (error) {

            console.error("Compteur d'alertes indisponible :", error);
        }
    }


    window.PharmaSession.updateAlertCount = function (total) {

        document
            .querySelectorAll("[data-alert-counter]")
            .forEach(counter => {
                counter.textContent = total;
                counter.classList.toggle("hidden", total === 0);
            });

        document
            .querySelectorAll(".notification-badge")
            .forEach(badge => {
                badge.textContent = total;
                badge.style.display = total === 0 ? "none" : "";
            });

        document
            .querySelectorAll(".notification-button")
            .forEach(button => {
                if (!button.dataset.alertLink) {
                    button.dataset.alertLink = "1";
                    button.title = "Voir les alertes";
                    button.addEventListener("click", () => {
                        window.location.href = "alertes.html";
                    });
                }
            });
    };

})();
