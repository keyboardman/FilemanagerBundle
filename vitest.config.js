import path from "path";
import { defineConfig } from "vitest/config";

export default defineConfig({
    test: {
        environment: "happy-dom",
        include: ["frontend/js/**/*.test.js"],
    },
    resolve: {
        alias: {
            "@hotwired/stimulus": path.resolve(__dirname, "node_modules/@hotwired/stimulus"),
        },
    },
});
