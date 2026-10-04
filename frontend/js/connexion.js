// ==========================================
// PHARMASTOCK - JAVASCRIPT DE CONNEXION
// ==========================================


// ------------------------------------------
// 1. AFFICHER / MASQUER LE MOT DE PASSE
// ------------------------------------------

const passwordInput = document.getElementById("password");
const showPassword = document.getElementById("showPassword");

if (passwordInput && showPassword) {

    showPassword.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            // Afficher le mot de passe
            passwordInput.type = "text";

            // Changer l'icône
            showPassword.textContent = "🙈";

            // Accessibilité
            showPassword.setAttribute(
                "aria-label",
                "Masquer le mot de passe"
            );

        } else {

            // Masquer le mot de passe
            passwordInput.type = "password";

            // Remettre l'icône
            showPassword.textContent = "👁";

            // Accessibilité
            showPassword.setAttribute(
                "aria-label",
                "Afficher le mot de passe"
            );
        }
    });
}


// ------------------------------------------
// 2. AFFICHAGE DES MESSAGES D'ERREUR
// ------------------------------------------

const urlParams = new URLSearchParams(window.location.search);

const error = urlParams.get("error");

const errorMessage = document.getElementById("error-message");


// ------------------------------------------
// 3. MESSAGE : IDENTIFIANTS INCORRECTS
// ------------------------------------------

if (error === "identifiants") {

    errorMessage.textContent =
        "Email ou mot de passe incorrect.";

    errorMessage.classList.add("show");
}


// ------------------------------------------
// 4. MESSAGE : CHAMPS VIDES
// ------------------------------------------

if (error === "champs_vides") {

    errorMessage.textContent =
        "Veuillez remplir tous les champs.";

    errorMessage.classList.add("show");
}


// ------------------------------------------
// 5. FAIRE DISPARAÎTRE LE MESSAGE
// ------------------------------------------

if (error) {

    setTimeout(function () {

        errorMessage.classList.remove("show");

    }, 5000);
}