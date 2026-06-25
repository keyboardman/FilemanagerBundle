export default function (target, mode, origin) {
    document.querySelectorAll(".select-file-btn").forEach((button) => {
        button.addEventListener("click", () => {
            const path = button.dataset.path;

            if (mode === "iframe") {
                window.parent.postMessage(
                    {
                        type: "filemanager:selected",
                        file: path,
                    },
                    origin || "*",
                );
                return;
            }

            if (!target) {
                return;
            }

            const inputTarget = document.getElementById(target);
            if (inputTarget) {
                inputTarget.value = path;
            }
        });
    });
}
