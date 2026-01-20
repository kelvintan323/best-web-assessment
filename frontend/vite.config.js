import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import vue from "@vitejs/plugin-vue";
import vueJsx from "@vitejs/plugin-vue-jsx";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import {
  VueRouterAutoImports,
  getPascalCaseRouteName,
} from "unplugin-vue-router";
import VueRouter from "unplugin-vue-router/vite";
import { defineConfig } from "vite";
import VueDevTools from "vite-plugin-vue-devtools";
import MetaLayouts from "vite-plugin-vue-meta-layouts";
import vuetify from "vite-plugin-vuetify";
import svgLoader from "vite-svg-loader";

function preserveFiles(files) {
  const preserved = new Map();
  const outDir = path.resolve(__dirname, "../public");

  return {
    name: "preserve-files",
    buildStart() {
      for (const file of files) {
        const filePath = path.join(outDir, file);
        if (fs.existsSync(filePath)) {
          preserved.set(file, fs.readFileSync(filePath));
        }
      }
    },
    closeBundle() {
      for (const [file, content] of preserved) {
        const filePath = path.join(outDir, file);
        fs.writeFileSync(filePath, content);
      }
    },
  };
}

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
    VueRouter({
      getRouteName: (routeNode) => {
        // Convert pascal case to kebab case
        return getPascalCaseRouteName(routeNode)
          .replace(/([a-z\d])([A-Z])/g, "$1-$2")
          .toLowerCase();
      },
    }),
    vue({
      template: {
        compilerOptions: {
          isCustomElement: (tag) =>
            tag === "swiper-container" || tag === "swiper-slide",
        },
      },
    }),
    VueDevTools(),
    vueJsx(),

    // Docs: https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin
    vuetify({
      styles: "sass",
    }),

    // Docs: https://github.com/dishait/vite-plugin-vue-meta-layouts?tab=readme-ov-file
    MetaLayouts({
      target: "./src/layouts",
      defaultLayout: "default",
    }),

    // Docs: https://github.com/antfu/unplugin-vue-components#unplugin-vue-components
    Components({
      dirs: ["src/@core/components", "src/views/demos", "src/components"],
      dts: true,
      resolvers: [
        (componentName) => {
          // Auto import `VueApexCharts`
          if (componentName === "VueApexCharts")
            return {
              name: "default",
              from: "vue3-apexcharts",
              as: "VueApexCharts",
            };
        },
      ],
    }),

    // Docs: https://github.com/antfu/unplugin-auto-import#unplugin-auto-import
    AutoImport({
      imports: [
        "vue",
        VueRouterAutoImports,
        "@vueuse/core",
        "@vueuse/math",
        "vue-i18n",
        "pinia",
      ],
      dirs: [
        "./src/@core/utils",
        "./src/@core/composable/",
        "./src/composables/",
        "./src/utils/",
        "./src/plugins/*/composables/*",
      ],
      vueTemplate: true,

      // ℹ️ Disabled to avoid confusion & accidental usage
      ignore: ["useCookies", "useStorage"],
      eslintrc: {
        enabled: true,
        filepath: "./.eslintrc-auto-import.json",
      },
    }),
    svgLoader(),
    preserveFiles(["index.php", ".htaccess", "favicon.ico"]),
  ],
  define: { "process.env": {} },
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
      "@themeConfig": fileURLToPath(
        new URL("./themeConfig.js", import.meta.url),
      ),
      "@core": fileURLToPath(new URL("./src/@core", import.meta.url)),
      "@layouts": fileURLToPath(new URL("./src/@layouts", import.meta.url)),
      "@images": fileURLToPath(
        new URL("./src/assets/images/", import.meta.url),
      ),
      "@styles": fileURLToPath(
        new URL("./src/assets/styles/", import.meta.url),
      ),
      "@configured-variables": fileURLToPath(
        new URL(
          "./src/assets/styles/variables/_template.scss",
          import.meta.url,
        ),
      ),
      "@db": fileURLToPath(
        new URL("./src/plugins/fake-api/handlers/", import.meta.url),
      ),
      "@api-utils": fileURLToPath(
        new URL("./src/plugins/fake-api/utils/", import.meta.url),
      ),
    },
  },
  build: {
    outDir: "../public",
    emptyOutDir: true,
    chunkSizeWarningLimit: 5000,
  },
  server: {
    warmup: {
      clientFiles: ["./src/**/*.vue"],
    },
  },
  optimizeDeps: {
    include: ["vuetify"],
    entries: ["./src/**/*.vue"],
  },
});
