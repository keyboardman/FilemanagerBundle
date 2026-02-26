import path from "path";
import { defineConfig } from "vite";

export default defineConfig(({ mode }) => {
    const isField = mode === "field";

    return {
      publicDir: false, // ne pas copier de fichiers statiques par défaut
      base: "./", // base relative pour Symfony AssetMapper
      build: {
        outDir: path.resolve(__dirname, "public"), // build directement dans assets/ du bundle
        emptyOutDir: true, // on nettoie au premier build
        assetsDir: "", // pas de sous-dossier pour CSS/images
        cssCodeSplit: true,
        assetsInlineLimit: 0,
        rollupOptions: {
          input: {
            filemanager: path.resolve(__dirname, "frontend/js/filemanager.js"),
            "filemanager-field": path.resolve(
              __dirname,
              "frontend/js/filemanager-field.js",
            ),
          },
          output: {
            entryFileNames: "[name].js",
            chunkFileNames: "[name].js",
            assetFileNames: "[name].[ext]",
          },
        },
      },
    };
});