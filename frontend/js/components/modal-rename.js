export default function modalRename(bootstrap, renameUrl, currentFilesystem) {
    const renameModalElement = document.getElementById("renameModal");

    const renameModal = new bootstrap.Modal(renameModalElement);

    const renameInput = document.getElementById("renameInput");
    const confirmRenameBtn = document.getElementById("confirmRenameBtn");

    let currentRenamePath = null;

    // Ouvrir la modal
    document.querySelectorAll(".file-rename").forEach((button) => {
        button.addEventListener("click", () => {
            currentRenamePath = button.dataset.path;
            renameInput.value = button.dataset.name;

            renameModal.show();

            setTimeout(() => {
                renameInput.focus();
                renameInput.select();
            }, 300);
        });
    });

    // Confirmer rename
    confirmRenameBtn.addEventListener("click", async () => {
        const newName = renameInput.value.trim();
        if (!newName || !currentRenamePath) return;

        try {
            const response = await fetch(renameUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    filesystem: currentFilesystem,
                    path: currentRenamePath,
                    newName: newName,
                }),
            });

            const result = await response.json();

            if (result.success) {
                renameModal.hide();
                location.reload();
            } else {
                alert(result.error || "Erreur lors du renommage");
            }
        } catch (error) {
            console.error(error);
            alert("Erreur serveur");
        }
    });
}
