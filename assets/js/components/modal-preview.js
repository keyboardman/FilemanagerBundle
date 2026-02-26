export default function modalPreview(bootstrap) {
    const previewModal = new bootstrap.Modal(
        document.getElementById("previewModal"),
    );
    const previewFiles = document.querySelectorAll(".file-preview");

    const modalTitle = document.getElementById("previewModalTitle");
    const modalImage = document.getElementById("previewModalImage");
    const modalVideo = document.getElementById("previewModalVideo");
    const modalAudio = document.getElementById("previewModalAudio");

    previewFiles.forEach((el) => {
        el.addEventListener("click", () => {
            const file = el.dataset.file;
            const type = el.dataset.type;
            const name = el.dataset.name;

            modalTitle.textContent = name;

            // Masquer tous les médias
            modalImage.classList.add("d-none");
            modalVideo.classList.add("d-none");
            modalAudio.classList.add("d-none");

            if (type === "image") {
                modalImage.src = file;
                modalImage.classList.remove("d-none");
            } else if (type === "video") {
                modalVideo.querySelector("source").src = file;
                modalVideo.load();
                modalVideo.classList.remove("d-none");
            } else if (type === "audio") {
                modalAudio.querySelector("source").src = file;
                modalAudio.load();
                modalAudio.classList.remove("d-none");
            }

            previewModal.show();
        });
    });

    // Quand la modal se ferme, stopper la lecture et remettre à zéro
    document
        .getElementById("previewModal")
        .addEventListener("hidden.bs.modal", () => {
            modalVideo.pause();
            modalAudio.pause();
            modalVideo.currentTime = 0;
            modalAudio.currentTime = 0;
        });
}
