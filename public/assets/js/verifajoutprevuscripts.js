document.addEventListener("DOMContentLoaded", function () {
    const submitBtn = document.getElementById("submit-verif");
    const modalCloseBtn = document.querySelector("[data-bs-dismiss='modal']");
    const modalConfirmBtn = document.getElementById("confirme");

    // Vérifiez si localStorage a les valeurs avant de les utiliser
    let montantPrevu = localStorage.getItem("payment_verification_edit_retard_montantPrevu") || "";

    // Préremplir les champs d'entrée avec les valeurs récupérées du stockage local, si les éléments existent
    const montantPrevuInput = document.getElementById("payment_verification_edit_retard_montantPrevu");

    if (montantPrevuInput) {
        montantPrevuInput.value = montantPrevu;
    }
    

    submitBtn.addEventListener("click", function () {
        if (montantPrevuInput) {
            montantPrevu = montantPrevuInput.value;
        } 

        // Enregistrer les valeurs dans le stockage local
        localStorage.setItem("payment_verification_edit_retard_montantPrevu", montantPrevu);

        // Vérifier si les champs sont vides
        if (montantPrevu.trim() === "") {
            // Afficher un message d'erreur ou effectuer une autre action
            document.getElementById("alertMessageVerif").style.display = "block";
            return; // Arrêter l'exécution de la fonction
        }
        document.getElementById("alertMessageVerif").style.display = "none";

        const paymentInfoContent = `
            <p>Montant prévu est: ${montantPrevu} Fcfa</p>
            <p>Veuillez confirmer si cela est exact !</p>
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
        localStorage.removeItem("payment_verification_edit_retard_montantPrevu");
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
        const montantRecuAfterLoad = localStorage.getItem("payment_verification_edit_retard_montantPrevu") || "";
        
        if (montantPrevuInput) {
            montantPrevuInput.value = montantRecuAfterLoad;
        } 
    });
});
