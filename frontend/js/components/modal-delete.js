export default function modalDelete(bootstrap, currentFilesystem, deleteFileUrl, deleteDirectoryUrl) {
    const deleteModalElement = document.getElementById("deleteModal");
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    const deleteModalMessage = document.getElementById("deleteModalMessage");

    if (!deleteModalElement || !confirmDeleteBtn || !deleteModalMessage) {
        return;
    }

    const deleteModal = new bootstrap.Modal(deleteModalElement);

    let currentPath = null;
    let currentType = null;
    let currentName = null;

    document.querySelectorAll(".file-delete").forEach((button) => {
        button.addEventListener("click", () => {
            currentPath = button.dataset.path || null;
            currentType = button.dataset.type || null;
            currentName = button.dataset.name || "cet élément";

            const labelType = currentType === "directory" ? "le dossier" : "le fichier";
            deleteModalMessage.textContent = `Voulez-vous vraiment supprimer ${labelType} "${currentName}" ?`;
            deleteModal.show();
        });
    });

    confirmDeleteBtn.addEventListener("click", async () => {
        if (!currentPath || !currentType) {
            deleteModal.hide();
            return;
        }

        const endpointUrl = currentType === "directory" ? deleteDirectoryUrl : deleteFileUrl;

        try {
            const response = await fetch(endpointUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    filesystem: currentFilesystem,
                    path: currentPath,
                }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                deleteModal.hide();
                location.reload();
                return;
            }

            alert(result.error || "Erreur lors de la suppression.");
        } catch (error) {
            console.error(error);
            alert("Erreur serveur.");
        }
    });
}
