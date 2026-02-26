export default function (target, mode, origin) {
    const inputTarget = document.getElementById(target);
    document.querySelectorAll(".select-file-btn").forEach((button) => {
        button.addEventListener("click", () => {
            const path = button.dataset.path;

            console.log("path", path, mode);

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
