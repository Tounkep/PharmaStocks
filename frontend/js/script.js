document.addEventListener('DOMContentLoaded', () => {
    // Appel de l'API Backend
    fetch('../backend/api_dashboard.php')
        .then(response => {
            if (!response.ok) throw new Error('Erreur réseau / serveur');
            return response.json();
        })
        .then(data => {
            // 1. Mettre à jour l'utilisateur
            if (data.user) {
                document.getElementById('user-firstname').textContent = data.user.prenom;
                document.getElementById('welcome-firstname').textContent = data.user.prenom;
                document.getElementById('user-role').textContent = data.user.role;
            }

            // 2. Mettre à jour la liste des ventes
            const ventesList = document.getElementById('ventes-list');
            ventesList.innerHTML = ''; // Vider le squelette/chargement

            if (data.ventes && data.ventes.length > 0) {
                data.ventes.forEach(v => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <span class="item-icon icon-green"><i class="fa-solid fa-arrow-right-arrow-left"></i></span>
                        <span class="item-title">Vente #${v.id}</span>
                        <span class="item-price">${parseFloat(v.montanttotal).toFixed(2)} €</span>
                        <span class="item-time">${v.heure}</span>
                    `;
                    ventesList.appendChild(li);
                });
            } else {
                ventesList.innerHTML = '<li>Aucune vente enregistrée aujourd\'hui.</li>';
            }

            // 3. Mettre à jour les stocks faibles
            const stocksList = document.getElementById('stocks-list');
            stocksList.innerHTML = '';

            if (data.stocksFaibles && data.stocksFaibles.length > 0) {
                data.stocksFaibles.forEach(s => {
                    const li = document.createElement('li');
                    const isRupture = parseInt(s.total_quantite) === 0;
                    const iconClass = isRupture ? 'dot-red fa-circle-xmark' : 'dot-orange fa-triangle-exclamation';

                    li.innerHTML = `
                        <span class="status-dot ${iconClass}"><i class="fa-solid ${iconClass}"></i></span>
                        <span class="item-title">${s.nom}</span>
                    `;
                    stocksList.appendChild(li);
                });
            } else {
                stocksList.innerHTML = '<li>Aucune alerte de stock.</li>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
});