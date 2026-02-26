export default function mediaFilter(origin) {
    const mediaFilterSelect = document.getElementById("media-filter");

    if (origin) {
        mediaFilterSelect.setAttribute("disabled", true);
    }

    mediaFilterSelect.addEventListener("change", function () {
        const params = new URLSearchParams(window.location.search);
        params.set("media", mediaFilterSelect.value ?? "");
        window.location.search = params.toString();
    });
}
