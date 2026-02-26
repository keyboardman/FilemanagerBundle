export default function mediaSort() {
    const mediaSortSelect = document.getElementById("sort-filter");

    mediaSortSelect.addEventListener("change", function () {
        const params = new URLSearchParams(window.location.search);
        params.set("sort", mediaSortSelect.value ?? "");
        window.location.search = params.toString();
    });
}
