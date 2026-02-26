export default function fileUpload(currentFilesystem, currentPath, uploadUrl) {
    const uploadBtn = document.getElementById("file-upload-btn");
    const fileInput = document.getElementById("file-upload-input");
    console.log("fileUpload", fileInput);

    uploadBtn.addEventListener("click", () => {
        console.log("uploadBtn");
        fileInput.click();
    });

    fileInput.addEventListener("change", async (event) => {
        const file = event.target.files[0];

        if (!file) return;

        const formData = new FormData();
        formData.append("file", file);
        formData.append("filesystem", currentFilesystem);
        formData.append("path", currentPath); // path courant

        try {
            const response = await fetch(uploadUrl, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const result = await response.json();
            if (result.success) {
                alert(`Fichier '${result.name}' uploadé avec succès !`);
                // Optionnel : recharger la page ou actualiser la liste
                location.reload();
            } else {
                alert("Erreur upload : " + (result.error || "Unknown error"));
            }
        } catch (err) {
            console.error(err);
            alert("Erreur lors de l'upload du fichier");
        } finally {
            fileInput.value = ""; // reset
        }
    });
}
