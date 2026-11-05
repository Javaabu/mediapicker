import { defineConfig } from "vite"

/**
 * Builds the prebuilt assets in dist/ for apps that don't build javaabu/mediapicker with
 * their own Vite. javaabu/manifest finds them through dist/.vite/manifest.json, keyed by
 * these entry paths. dist/ is committed; CI fails when it's out of date.
 *
 * The React components and pages aren't prebuilt: apps build them from resources/js.
 */
export const entries = ["resources/js/blade/picker.ts", "resources/js/blade/cropper.ts", "resources/js/blocks/index.ts"]

export default defineConfig({
  // Relative URLs, as javaabu/manifest serves dist/ from a sub-path.
  base: "./",
  publicDir: false,
  resolve: {
    alias: {
      "@javaabu/mediapicker": new URL("./resources/js", import.meta.url).pathname,
      "@javaabu/block-editor": new URL("./vendor/javaabu/block-editor/resources/js", import.meta.url).pathname,
    },
  },
  build: {
    outDir: "dist",
    emptyOutDir: true,
    manifest: true,
    target: "es2020",
    sourcemap: false,
    reportCompressedSize: false,
    chunkSizeWarningLimit: 800,
    rollupOptions: {
      input: entries,
      onwarn(warning, warn) {
        // Onu's "use client" directives mean nothing in a browser bundle.
        if (warning.code === "MODULE_LEVEL_DIRECTIVE") return
        warn(warning)
      },
    },
  },
})
