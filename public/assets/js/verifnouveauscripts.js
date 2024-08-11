document.addEventListener("DOMContentLoaded", function () {
    const submitBtn = document.getElementById("submit-verif");
    const modalCloseBtn = document.querySelector("[data-bs-dismiss='modal']");
    const modalConfirmBtn = document.getElementById("confirme");

    // Vérifiez si localStorage a les valeurs avant de les utiliser
    let montantRecu = localStorage.getItem("payment_verification_edit_montantRecu") || "";
    let typePaiement = localStorage.getItem("payment_verification_edit_typePaiement") || "";

    // Préremplir les champs d'entrée avec les valeurs récupérées du stockage local, si les éléments existent
    const montantRecuInput = document.getElementById("payment_verification_edit_montantRecu");
    const typePaiementInput = document.getElementById("payment_verification_edit_typePaiement");

    if (montantRecuInput) {
        montantRecuInput.value = montantRecu;
    }
    if (typePaiementInput) {
        typePaiementInput.value = typePaiement;
    }

    submitBtn.addEventListener("click", function () {
        if (montantRecuInput) {
            montantRecu = montantRecuInput.value;
        }
        if (typePaiementInput) {
            typePaiement = typePaiementInput.value;
        }

        // Enregistrer les valeurs dans le stockage local
        localStorage.setItem("payment_verification_edit_montantRecu", montantRecu);
        localStorage.setItem("payment_verification_edit_typePaiement", typePaiement);

        // Vérifier si les champs sont vides
        if (montantRecu.trim() === "" || typePaiement.trim() === "") {
            // Afficher un message d'erreur ou effectuer une autre action
            document.getElementById("alertMessageVerif").style.display = "block";
            return; // Arrêter l'exécution de la fonction
        }
        document.getElementById("alertMessageVerif").style.display = "none";

        const paymentInfoContent = `
            <p>Montant réçu: ${montantRecu} Fcfa</p>
            <p>Type de paiement: ${typePaiement}</p>
        `;
        document.getElementById("paymentModalBody").innerHTML = paymentInfoContent;

        // Afficher le modal
        let myModal = new bootstrap.Modal(document.getElementById("paymentModal"), {
            keyboard: false,
        });
        myModal.show();
    });

    modalConfirmBtn.addEventListener("click", function () {
        // Supprimer les données du localStorage
        localStorage.removeItem("payment_verification_edit_montantRecu");
        localStorage.removeItem("payment_verification_edit_typePaiement");
    });

    // Ajouter un gestionnaire d'événements pour le bouton de fermeture du modal
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener("click", function () {
            // Recharger la page
            window.location.reload();
        });
    }

    // Réinitialiser les valeurs des champs après le rechargement de la page
    window.addEventListener("load", function () {
        const montantRecuAfterLoad = localStorage.getItem("payment_verification_edit_montantRecu") || "";
        const typePaiementAfterLoad = localStorage.getItem("payment_verification_edit_typePaiement") || "";
        
        if (montantRecuInput) {
            montantRecuInput.value = montantRecuAfterLoad;
        }
        if (typePaiementInput) {
            typePaiementInput.value = typePaiementAfterLoad;
        }
    });
});
