document.addEventListener("DOMContentLoaded", function () {
    const submitBtn = document.getElementById("submit-payment");
    const modalCloseBtn = document.querySelector("[data-bs-dismiss='modal']");
    const modalConfirmBtn = document.getElementById("confirme");

    let amount = localStorage.getItem("payment_retard_edit_montantAPayer") || "";
    let receiptFileName = localStorage.getItem("payment_retard_edit_imageFile_file") || "";

    // Préremplir les champs d'entrée avec les valeurs récupérées du stockage local
    document.getElementById("payment_retard_edit_montantAPayer").value = amount;

    // Vérifier si un nom de fichier est disponible dans le stockage local
    if (receiptFileName) {
        // Créer un objet File pour le nom de fichier stocké
        let receiptFile = new File([], receiptFileName);
        // Assigner le fichier créé au champ de fichier
        document.getElementById("payment_retard_edit_imageFile_file").files[0] = receiptFile;
    }

    if (submitBtn) {
        submitBtn.addEventListener("click", function (event) {
            event.preventDefault();
            amount = document.getElementById("payment_retard_edit_montantAPayer").value;
            const receipt = document.getElementById("payment_retard_edit_imageFile_file").files[0];

            // Enregistrer les valeurs dans le stockage local
            localStorage.setItem("payment_retard_edit_montantAPayer", amount);

            // Vérifier si les champs sont vides
            if (amount.trim() === "" || !receipt) {
                // Afficher un message d'erreur ou effectuer une autre action
                document.getElementById("alertMessage").style.display = "block";
                return; // Arrêter l'exécution de la fonction
            }
            document.getElementById("alertMessage").style.display = "none";

            const reader = new FileReader();
            reader.onload = function (event) {
                const paymentInfoContent = `
                    <p>Montant: ${amount} Fcfa</p>
                    <p>Reçu de paiement: ${receipt.name}</p>
                    <img src="${event.target.result}" alt="Reçu de paiement" style="max-width: 100%; height: auto; width: 200px;">
                `;
                document.getElementById("paymentInfoModalBody").innerHTML = paymentInfoContent;
                // Afficher le modal
                var myModal = new bootstrap.Modal(
                    document.getElementById("paymentInfoModal"),
                    { keyboard: false }
                );
                myModal.show();
            };
            reader.readAsDataURL(receipt);
        });
    }

    if (modalConfirmBtn) {
        modalConfirmBtn.addEventListener("click", function () {
            // Supprimer les données du localStorage
            localStorage.removeItem("payment_retard_edit_montantAPayer");
        });
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener("click", function () {
            // Recharger la page
            window.location.reload();
        });
    }
});
