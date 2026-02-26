export default function (target, mode, origin) {
    const inputTarget = document.getElementById(target);
    document.querySelectorAll(".select-file-btn").forEach((button) => {
        button.addEventListener("click", () => {
            const path = button.dataset.path;

            if (mode == "iframe") {
                window.parent.postMessage(
                    {
                        type: "filemanager:selected",
                        file: path,
                    },
                    origin,
                );
            } else {
                inputTarget.value = path;
            }
        });
    });
}
