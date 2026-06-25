import { Application } from "@hotwired/stimulus";
import DropzoneController from "@symfony/ux-dropzone/dist/controller.js";
import FilemanagerUploadController from "./controllers/filemanager_upload_controller.js";
import fileSelect from "./components/file-select";
import fileCreateDirectory from "./components/file-create-directory";
import modalPreview from "./components/modal-preview";
import modalRename from "./components/modal-rename";
import modalDelete from "./components/modal-delete";
import filesystemSelect from "./components/filesystem-select";
import mediaFilter from "./components/media-filter";
import mediaSort from "./components/media-sort";
import { getParentOrigin } from "./components/utils";

import * as bootstrap from "bootstrap";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "@symfony/ux-dropzone/dist/style.min.css";
import "../css/filemanager.css";

const application = Application.start();
application.register("symfony--ux-dropzone--dropzone", DropzoneController);
application.register("filemanager-upload", FilemanagerUploadController);

document.addEventListener("DOMContentLoaded", function () {
    const app = document.getElementById("filemanager-app");

    const crossdomain = app.dataset.crossdomain;
    const target = app.dataset.fieldTarget;
    const mode = app.dataset.mode;

    const renameUrl = app.dataset.renameUrl;
    const createDirectoryUrl = app.dataset.createDirectoryUrl;
    const deleteFileUrl = app.dataset.deleteFileUrl;
    const deleteDirectoryUrl = app.dataset.deleteDirectoryUrl;
    const currentFilesystem = app.dataset.filesystem;

    const bindFileSelect = (origin) => {
        if (mode === "iframe" || target) {
            fileSelect(target, mode, origin ?? "*");
        }
    };

    if (mode === "iframe" || target) {
        getParentOrigin(crossdomain)
            .then((origin) => {
                bindFileSelect(origin);
                if (origin) {
                    mediaFilter(origin);
                } else {
                    mediaFilter();
                }
            })
            .catch((err) => {
                console.warn(err);
                bindFileSelect("*");
                mediaFilter();
            });
    } else {
        mediaFilter();
    }

    filesystemSelect();
    mediaSort();
    fileCreateDirectory(currentFilesystem, app.dataset.path, createDirectoryUrl);

    modalPreview(bootstrap);
    modalRename(bootstrap, renameUrl, currentFilesystem);
    modalDelete(bootstrap, currentFilesystem, deleteFileUrl, deleteDirectoryUrl);
});
