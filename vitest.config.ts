import { defineConfig } from "vitest/config"

// @javaabu/block-editor and @javaabu/stencil are the TypeScript javaabu/block-editor and
// javaabu/stencil ship in their Composer packages. Apps get them through javaabu/manifest;
// here they come from vendor/ (run composer install first).
const path = (relative: string) => new URL(relative, import.meta.url).pathname

export default defineConfig({
  resolve: {
    alias: {
      "@javaabu/mediapicker": path("./resources/js"),
      "@javaabu/block-editor": path("./vendor/javaabu/block-editor/resources/js"),
      "@javaabu/stencil": path("./vendor/javaabu/stencil/resources/js"),
    },
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    // Onu's media components import their libraries' CSS; let Vite process it
    server: { deps: { inline: [/@javaabu\/onu/, /yet-another-react-lightbox/, /media-chrome/] } },
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
