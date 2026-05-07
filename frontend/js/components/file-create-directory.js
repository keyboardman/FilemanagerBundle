export default function fileCreateDirectory(currentFilesystem, currentPath, createDirectoryUrl) {
    const createDirectoryBtn = document.getElementById("file-create-directory-btn");
    if (!createDirectoryBtn) {
        return;
    }

    createDirectoryBtn.addEventListener("click", async () => {
        const directoryName = window.prompt("Nom du nouveau dossier :");
        if (directoryName === null) {
            return;
        }

        const trimmedName = directoryName.trim();
        if (!trimmedName) {
            alert("Le nom du dossier ne peut pas être vide.");
            return;
        }

        try {
            const response = await fetch(createDirectoryUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({
                    filesystem: currentFilesystem,
                    path: currentPath,
                    name: trimmedName,
                }),
            });

            const result = await response.json();
            if (response.ok && result.success) {
                location.reload();
                return;
            }

            alert(result.error || "Erreur lors de la création du dossier.");
        } catch (error) {
            console.error(error);
            alert("Erreur serveur.");
        }
    });
}
