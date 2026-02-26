import path from "path";
import { defineConfig } from "vite";

export default defineConfig(({ mode }) => {
    const isField = mode === "field";

    return {
        publicDir: false,
        base: "./",
        build: {
            outDir: path.resolve(__dirname, "public/assets"),
            emptyOutDir: true, // on nettoie seulement au premier build
            assetsDir: "",
            cssCodeSplit: true,
            assetsInlineLimit: 0,
            /*
            lib: {
                entry: isField
                    ? path.resolve(__dirname, "assets/js/filemanager-field.js")
                    : path.resolve(__dirname, "assets/js/filemanager.js"),
                name: isField ? "FileManagerField" : "FileManager",
                fileName: () =>
                    isField ? "filemanager-field.js" : "filemanager.js",
                formats: ["iife"], // ✅ maintenant autorisé
            },*/

            rollupOptions: {
                input: {
                    filemanager: path.resolve(
                        __dirname,
                        "assets/js/filemanager.js",
                    ),
                    "filemanager-field": path.resolve(
                        __dirname,
                        "assets/js/filemanager-field.js",
                    ),
                },
                output: {
                    entryFileNames: "[name].js",
                    chunkFileNames: "[name].js", // ✅ important
                    assetFileNames: "[name].[ext]", // ✅ important
                },
            },
        },
    };
});
