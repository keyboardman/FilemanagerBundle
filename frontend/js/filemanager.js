import * as bootstrap from "bootstrap";
import fileSelect from "./components/file-select";
import fileUpload from "./components/file-upload";
import fileCreateDirectory from "./components/file-create-directory";
import modalPreview from "./components/modal-preview";
import modalRename from "./components/modal-rename";
import filesystemSelect from "./components/filesystem-select";
import mediaFilter from "./components/media-filter";
import mediaSort from "./components/media-sort";
import { getParentOrigin } from "./components/utils";

import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "../css/filemanager.css";

document.addEventListener("DOMContentLoaded", function () {
    const app = document.getElementById("filemanager-app");

    const crossdomain = app.dataset.crossdomain;
    const target = app.dataset.fieldTarget;
    const mode = app.dataset.mode;

    const uploadUrl = app.dataset.uploadUrl;
    const renameUrl = app.dataset.renameUrl;
    const createDirectoryUrl = app.dataset.createDirectoryUrl;
    const currentFilesystem = app.dataset.filesystem;
    const currentPath = app.dataset.path;

    let origin = null;
    getParentOrigin(crossdomain)
        .then((_origin) => {
            if (_origin) {
                origin = _origin;
                fileSelect(target, mode, _origin);
                mediaFilter(_origin);
            } else {
                mediaFilter();
            }
        })
        .catch((err) => {
            console.warn(err);
        });
    filesystemSelect();

    mediaSort();

    fileUpload(currentFilesystem, currentPath, uploadUrl);
    fileCreateDirectory(currentFilesystem, currentPath, createDirectoryUrl);

    modalPreview(bootstrap);
    modalRename(bootstrap, renameUrl, currentFilesystem);
});
