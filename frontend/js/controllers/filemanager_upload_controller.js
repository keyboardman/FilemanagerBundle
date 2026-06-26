import { Controller } from "@hotwired/stimulus";
import {
    computeChunkProgress,
    computeMonolithicProgress,
    isAllowedMediaType,
    parseUploadResponse,
} from "../upload/helpers.js";

export default class extends Controller {
    static targets = ["progressContainer"];

    static values = {
        uploadUrl: String,
        uploadChunkUrl: String,
        filesystem: String,
        path: String,
        chunkSize: Number,
        chunkThreshold: Number,
    };

    initialize() {
        this.onInputChange = this.onInputChange.bind(this);
        this.openFilePicker = this.openFilePicker.bind(this);
        this.beforeUnloadHandler = this.beforeUnloadHandler.bind(this);
        this.queue = [];
        this.processing = false;
        this.uploading = false;
        this.progressItems = new Map();
    }

    connect() {
        this.inputElement = this.element.querySelector(
            '[data-symfony--ux-dropzone--dropzone-target="input"]',
        );

        if (this.inputElement) {
            this.inputElement.addEventListener("change", this.onInputChange);
        }

        const uploadBtn = document.getElementById("file-upload-btn");
        if (uploadBtn) {
            uploadBtn.addEventListener("click", this.openFilePicker);
        }

        window.addEventListener("beforeunload", this.beforeUnloadHandler);
    }

    disconnect() {
        if (this.inputElement) {
            this.inputElement.removeEventListener("change", this.onInputChange);
        }

        const uploadBtn = document.getElementById("file-upload-btn");
        if (uploadBtn) {
            uploadBtn.removeEventListener("click", this.openFilePicker);
        }

        window.removeEventListener("beforeunload", this.beforeUnloadHandler);
    }

    openFilePicker(event) {
        event.preventDefault();
        this.inputElement?.click();
    }

    onInputChange() {
        const files = Array.from(this.inputElement?.files || []);
        if (files.length === 0) {
            return;
        }

        this.enqueueFiles(files);
        this.inputElement.value = "";
    }

    enqueueFiles(files) {
        for (const file of files) {
            if (!this.isAllowedMedia(file)) {
                this.showError(file.name, "Type de fichier non autorisé (images, vidéos et audios uniquement).");
                continue;
            }

            this.queue.push(file);
            this.createProgressItem(file);
        }

        this.processQueue();
    }

    isAllowedMedia(file) {
        return isAllowedMediaType(file.type);
    }

    async processQueue() {
        if (this.processing) {
            return;
        }

        this.processing = true;
        let shouldReload = false;

        while (this.queue.length > 0) {
            const file = this.queue.shift();
            this.uploading = true;

            try {
                if (file.size > this.chunkThresholdValue) {
                    await this.uploadChunked(file);
                } else {
                    await this.uploadMonolithic(file);
                }

                this.markSuccess(file);
                shouldReload = true;
            } catch (error) {
                this.markError(file, error.message || "Erreur lors de l'upload.");
            }
        }

        this.uploading = false;
        this.processing = false;

        if (shouldReload) {
            window.location.reload();
        }
    }

    uploadMonolithic(file) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();

            formData.append("file", file);
            formData.append("filesystem", this.filesystemValue);
            formData.append("path", this.pathValue);

            xhr.upload.addEventListener("progress", (event) => {
                if (event.lengthComputable) {
                    this.updateProgress(file, computeMonolithicProgress(event.loaded, event.total));
                }
            });

            xhr.addEventListener("load", () => {
                const result = this.parseResponse(xhr);
                if (result.success) {
                    resolve(result);
                } else {
                    reject(new Error(result.error || "Erreur upload."));
                }
            });

            xhr.addEventListener("error", () => reject(new Error("Erreur réseau lors de l'upload.")));
            xhr.addEventListener("abort", () => reject(new Error("Upload annulé.")));

            xhr.open("POST", this.uploadUrlValue);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            xhr.send(formData);
        });
    }

    async uploadChunked(file) {
        const chunkSize = this.chunkSizeValue;
        const totalChunks = Math.ceil(file.size / chunkSize);
        const uploadId = crypto.randomUUID();

        for (let index = 0; index < totalChunks; index += 1) {
            const start = index * chunkSize;
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);

            await this.sendChunk(file, chunk, {
                uploadId,
                chunkIndex: index,
                totalChunks,
                totalSize: file.size,
                start,
            });
        }
    }

    sendChunk(file, chunk, meta) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();

            formData.append("chunk", chunk, file.name);
            formData.append("uploadId", meta.uploadId);
            formData.append("chunkIndex", String(meta.chunkIndex));
            formData.append("totalChunks", String(meta.totalChunks));
            formData.append("totalSize", String(meta.totalSize));
            formData.append("filename", file.name);
            formData.append("filesystem", this.filesystemValue);
            formData.append("path", this.pathValue);

            xhr.upload.addEventListener("progress", (event) => {
                if (event.lengthComputable) {
                    this.updateProgress(file, computeChunkProgress(meta.start, event.loaded, file.size));
                }
            });

            xhr.addEventListener("load", () => {
                const result = this.parseResponse(xhr);
                if (result.success) {
                    resolve(result);
                } else {
                    reject(new Error(result.error || "Erreur upload fragmenté."));
                }
            });

            xhr.addEventListener("error", () => reject(new Error("Erreur réseau lors de l'envoi du fragment.")));
            xhr.addEventListener("abort", () => reject(new Error("Upload annulé.")));

            xhr.open("POST", this.uploadChunkUrlValue);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            xhr.send(formData);
        });
    }

    parseResponse(xhr) {
        return parseUploadResponse(xhr);
    }

    createProgressItem(file) {
        const item = document.createElement("div");
        item.className = "upload-progress-item mb-2";
        item.innerHTML = `
            <div class="d-flex justify-content-between small mb-1">
                <span class="upload-filename text-truncate me-2">${this.escapeHtml(file.name)}</span>
                <span class="upload-percent text-nowrap">0%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="upload-error small text-danger mt-1 d-none"></div>
        `;

        this.progressContainerTarget.appendChild(item);
        this.progressItems.set(file, {
            bar: item.querySelector(".progress-bar"),
            percent: item.querySelector(".upload-percent"),
            error: item.querySelector(".upload-error"),
        });
    }

    updateProgress(file, percent) {
        const progress = this.progressItems.get(file);
        if (!progress) {
            return;
        }

        progress.bar.style.width = `${percent}%`;
        progress.bar.setAttribute("aria-valuenow", String(percent));
        progress.percent.textContent = `${percent}%`;
    }

    markSuccess(file) {
        const progress = this.progressItems.get(file);
        if (!progress) {
            return;
        }

        this.updateProgress(file, 100);
        progress.bar.classList.add("bg-success");
        progress.percent.textContent = "Terminé";
    }

    markError(file, message) {
        const progress = this.progressItems.get(file);
        if (!progress) {
            return;
        }

        progress.bar.classList.add("bg-danger");
        progress.percent.textContent = "Erreur";
        progress.error.textContent = message;
        progress.error.classList.remove("d-none");
    }

    showError(filename, message) {
        const placeholder = { name: filename };
        this.createProgressItem(placeholder);
        this.markError(placeholder, message);
    }

    beforeUnloadHandler(event) {
        if (this.uploading) {
            event.preventDefault();
            event.returnValue = "";
        }
    }

    escapeHtml(value) {
        return value
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;");
    }
}
