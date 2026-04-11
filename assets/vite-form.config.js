import { resolve } from "path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  optimizeDeps: { include: ["lodash.throttle", "lodash.orderby"] },
  build: {
    outDir: "../files/js/",
    assetsDir: "./",
    emptyOutDir: false,
    rollupOptions: {
      input: {
        form: "./js/form.js",
      },
      output: {
        manualChunks: {},
        entryFileNames: "form.js",
      },
    },
  },
});
