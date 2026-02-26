import "../css/filemanager-field.css";

document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".open-filemanager");
    const modal = document.getElementById("filemanager-modal");

    buttons.forEach((button) => {
        button.addEventListener("click", function () {
            window.addEventListener("message", (event) => {
                if (event.data?.type === "REQUEST_PARENT_ORIGIN") {
                    // On renvoie l'origine uniquement au vrai parent de l'iframe
                    event.source.postMessage(
                        {
                            type: "PARENT_ORIGIN",
                            origin: window.location.origin,
                        },
                        event.origin,
                    );
                }
            });

            const url = button.dataset.url;
            const target = button.dataset.target;
            const input = document.getElementById(target);
            openModal(modal, input, url);
        });
    });
});

function openModal(modal, input, url) {
    const iframe = document.createElement("iframe");
    iframe.src = url;
    iframe.style.width = "100%";
    iframe.style.height = "100%";
    iframe.style.border = "none";

    const modalOverlay = document.querySelector(".filemanager-overlay");
    const modalContent = document.querySelector(".filemanager-content");
    modalContent.appendChild(iframe);

    modal.style.display = "block";

    modalOverlay.addEventListener("click", () => {
        modal.style.display = "none";
    });

    function messageHandler(event) {
        if (event.data.type === "filemanager:selected") {
            input.value = event.data.file;
            iframe.remove();
            modal.style.display = "none";

            window.removeEventListener("message", messageHandler);
        }
    }

    window.addEventListener("message", messageHandler);
}
