// =========================================================
// FRONTEND JAVASCRIPT : Interactions dynamiques
// =========================================================

document.addEventListener('DOMContentLoaded', function () {
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

    // Fermeture de la modale en cliquant en dehors du contenu
    window.addEventListener('click', function (e) {
        if (e.target === saleModal) {
            saleModal.style.display = 'none';
        }
    });
});