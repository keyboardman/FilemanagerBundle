export default function () {
    const fsSelect = document.getElementById("filesystem-select");

    fsSelect.addEventListener("change", function () {
        const selectedFs = this.value;

        // Recharger la page avec le filesystem sélectionné et path="/"
        const params = new URLSearchParams(window.location.search);
        params.set("filesystem", selectedFs);
        params.set("path", "/");

        window.location.search = params.toString();
    });
}
