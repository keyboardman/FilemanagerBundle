import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import UploadController from "../controllers/filemanager_upload_controller.js";
import {
    computeChunkProgress,
    computeMonolithicProgress,
    isAllowedMediaType,
    parseUploadResponse,
} from "../upload/helpers.js";

function createController() {
    const element = document.createElement("div");
    element.innerHTML = '<div data-filemanager-upload-target="progressContainer"></div>';

    const progressContainer = element.querySelector(
        '[data-filemanager-upload-target="progressContainer"]',
    );

    const controller = {
        element,
        progressContainerTarget: progressContainer,
        uploadUrlValue: "/api/filemanager/upload",
        uploadChunkUrlValue: "/api/filemanager/upload-chunk",
        filesystemValue: "default",
        pathValue: "/",
        chunkSizeValue: 10,
        chunkThresholdValue: 20,
    };

    for (const method of Object.getOwnPropertyNames(UploadController.prototype)) {
        if (method === "constructor") {
            continue;
        }

        controller[method] = UploadController.prototype[method].bind(controller);
    }

    controller.initialize();

    return controller;
}

function createFile(name, type, size) {
    const content = new Uint8Array(size);
    const file = new File([content], name, { type });
    Object.defineProperty(file, "size", { value: size });

    return file;
}

class MockXMLHttpRequest {
    constructor() {
        this.upload = { addEventListener: vi.fn() };
        this.listeners = {};
        this.requestHeaders = {};
        this.responseText = "";
        this.status = 200;
        this.openMethod = null;
        this.openUrl = null;
    }

    addEventListener(event, callback) {
        this.listeners[event] = callback;
    }

    open(method, url) {
        this.openMethod = method;
        this.openUrl = url;
    }

    setRequestHeader(name, value) {
        this.requestHeaders[name] = value;
    }

    send() {
        queueMicrotask(() => this.listeners.load?.());
    }

    simulateProgress(loaded, total) {
        const handler = this.upload.addEventListener.mock.calls.find(([event]) => event === "progress")?.[1];
        handler?.({ lengthComputable: true, loaded, total });
    }
}

describe("upload helpers", () => {
    it("accepts image, video and audio mime types", () => {
        expect(isAllowedMediaType("image/png")).toBe(true);
        expect(isAllowedMediaType("video/mp4")).toBe(true);
        expect(isAllowedMediaType("audio/mpeg")).toBe(true);
        expect(isAllowedMediaType("application/pdf")).toBe(false);
    });

    it("parses successful JSON responses", () => {
        const xhr = { responseText: '{"success":true}', status: 200 };
        expect(parseUploadResponse(xhr)).toEqual({ success: true });
    });

    it("returns an error for invalid JSON with HTTP error", () => {
        const xhr = { responseText: "not-json", status: 500 };
        expect(parseUploadResponse(xhr)).toEqual({
            success: false,
            error: "Erreur HTTP 500",
        });
    });

    it("computes monolithic and chunked progress", () => {
        expect(computeMonolithicProgress(50, 100)).toBe(50);
        expect(computeChunkProgress(100, 50, 200)).toBe(75);
    });
});

describe("filemanager upload controller", () => {
    let controller;
    let xhrInstances;
    let originalXhr;
    let originalRandomUuid;
    let reloadMock;

    beforeEach(() => {
        controller = createController();
        xhrInstances = [];
        originalXhr = global.XMLHttpRequest;
        originalRandomUuid = global.crypto.randomUUID;

        global.XMLHttpRequest = vi.fn(() => {
            const xhr = new MockXMLHttpRequest();
            xhr.responseText = JSON.stringify({ success: true });
            xhrInstances.push(xhr);
            return xhr;
        });

        global.crypto.randomUUID = vi.fn(() => "11111111-2222-3333-4444-555555555555");
        reloadMock = vi.fn();
        Object.defineProperty(window, "location", {
            configurable: true,
            value: { reload: reloadMock },
        });
    });

    afterEach(() => {
        global.XMLHttpRequest = originalXhr;
        global.crypto.randomUUID = originalRandomUuid;
    });

    it("rejects disallowed media types without enqueueing upload", async () => {
        const file = createFile("doc.pdf", "application/pdf", 5);
        controller.enqueueFiles([file]);

        await vi.waitFor(() => expect(controller.processing).toBe(false));

        expect(xhrInstances).toHaveLength(0);
        expect(controller.progressContainerTarget.textContent).toContain("non autorisé");
    });

    it("uses monolithic upload for files under the chunk threshold", async () => {
        const file = createFile("photo.jpg", "image/jpeg", 15);

        controller.enqueueFiles([file]);
        await vi.waitFor(() => expect(controller.processing).toBe(false));

        expect(xhrInstances).toHaveLength(1);
        expect(xhrInstances[0].openUrl).toBe("/api/filemanager/upload");
        expect(reloadMock).toHaveBeenCalled();
    });

    it("uses chunked upload for large files", async () => {
        const file = createFile("video.mp4", "video/mp4", 25);

        controller.enqueueFiles([file]);
        await vi.waitFor(() => expect(controller.processing).toBe(false));

        expect(xhrInstances.length).toBeGreaterThan(1);
        expect(xhrInstances.every((xhr) => xhr.openUrl === "/api/filemanager/upload-chunk")).toBe(true);
    });

    it("updates progress during monolithic upload", async () => {
        const file = createFile("photo.jpg", "image/jpeg", 10);
        controller.createProgressItem(file);

        const xhr = new MockXMLHttpRequest();
        xhr.responseText = JSON.stringify({ success: true });

        await new Promise((resolve, reject) => {
            xhr.addEventListener("load", () => {
                const result = controller.parseResponse(xhr);
                result.success ? resolve(result) : reject(new Error(result.error));
            });

            xhr.upload.addEventListener("progress", (event) => {
                if (event.lengthComputable) {
                    controller.updateProgress(file, computeMonolithicProgress(event.loaded, event.total));
                }
            });

            xhr.simulateProgress(5, 10);
            xhr.listeners.load();
        });

        expect(controller.progressItems.get(file).percent.textContent).toBe("50%");
    });

    it("updates progress during chunked upload", () => {
        const file = createFile("video.mp4", "video/mp4", 20);
        controller.createProgressItem(file);

        const meta = { start: 10 };
        controller.updateProgress(file, computeChunkProgress(meta.start, 5, file.size));

        expect(controller.progressItems.get(file).percent.textContent).toBe("75%");
    });

    it("continues queue after API error and marks file as failed", async () => {
        let xhrCallIndex = 0;
        global.XMLHttpRequest = vi.fn(() => {
            const xhr = new MockXMLHttpRequest();
            xhr.responseText = JSON.stringify(
                xhrCallIndex === 0
                    ? { success: false, error: "Échec serveur" }
                    : { success: true },
            );
            xhrCallIndex += 1;
            xhrInstances.push(xhr);
            return xhr;
        });

        const failingFile = createFile("bad.jpg", "image/jpeg", 5);
        const successFile = createFile("good.jpg", "image/jpeg", 5);

        controller.enqueueFiles([failingFile, successFile]);

        await vi.waitFor(() => expect(controller.processing).toBe(false));

        expect(xhrInstances).toHaveLength(2);
        expect(controller.progressItems.get(failingFile).percent.textContent).toBe("Erreur");
        expect(controller.progressItems.get(successFile).percent.textContent).toBe("Terminé");
    });

    it("activates beforeunload only while uploading", () => {
        const event = { preventDefault: vi.fn(), returnValue: "" };

        controller.uploading = false;
        controller.beforeUnloadHandler(event);
        expect(event.preventDefault).not.toHaveBeenCalled();

        controller.uploading = true;
        controller.beforeUnloadHandler(event);
        expect(event.preventDefault).toHaveBeenCalled();
        expect(event.returnValue).toBe("");
    });
});
