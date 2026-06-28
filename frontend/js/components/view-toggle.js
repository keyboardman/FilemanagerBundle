export default function viewToggle() {
    const viewToggleSelect = document.getElementById("view-toggle");

    viewToggleSelect.addEventListener("change", function () {
        const params = new URLSearchParams(window.location.search);
        const value = viewToggleSelect.value ?? "card";

        if (value === "card") {
            params.delete("view");
        } else {
            params.set("view", value);
        }

        window.location.search = params.toString();
    });
}
