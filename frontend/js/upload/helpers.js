export function isAllowedMediaType(mimeType) {
    return (
        mimeType.startsWith("image/") ||
        mimeType.startsWith("video/") ||
        mimeType.startsWith("audio/")
    );
}

export function parseUploadResponse(xhr) {
    try {
        return JSON.parse(xhr.responseText);
    } catch {
        return {
            success: false,
            error: xhr.status >= 400 ? `Erreur HTTP ${xhr.status}` : "Réponse serveur invalide.",
        };
    }
}

export function computeChunkProgress(start, loaded, fileSize) {
    return Math.round(((start + loaded) / fileSize) * 100);
}

export function computeMonolithicProgress(loaded, total) {
    return Math.round((loaded / total) * 100);
}
