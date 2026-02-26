export function getParentOrigin(crossdomain = 0, timeout = 2000) {
    return new Promise((resolve, reject) => {
        const isInIframe = window.self !== window.top;
        if (!isInIframe) {
            resolve(null); // pas dans une iframe
            return;
        }

        if (crossdomain == 0) {
            const parentOrigin = window.parent.location.origin;
            resolve(parentOrigin);
            return;
        }

        // Listener pour recevoir l'origine du parent
        function handleMessage(event) {
            if (event.data?.type === "PARENT_ORIGIN") {
                console.log("crossdomain", crossdomain, event.data);
                window.removeEventListener("message", handleMessage);
                resolve(event.data.origin); // on renvoie l'origine du parent
            }
        }

        window.addEventListener("message", handleMessage);

        try {
            // Demande au parent de renvoyer son origine
            window.parent.postMessage({ type: "REQUEST_PARENT_ORIGIN" }, "*");
        } catch (e) {
            window.removeEventListener("message", handleMessage);
            reject("Impossible d'envoyer le message au parent : " + e);
        }

        // Timeout si le parent ne répond pas
        setTimeout(() => {
            window.removeEventListener("message", handleMessage);
            reject("Aucune réponse du parent");
        }, timeout);
    });
}
