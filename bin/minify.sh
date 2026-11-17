#!/usr/bin/env bash
# Rebuild the minified front-end assets. Run after editing the public CSS or JS.
# The version is pinned so the output is the same on every machine and in CI.
set -euo pipefail
cd "$(dirname "$0")/.."

ESBUILD="esbuild@0.24.0"
npx --yes "$ESBUILD" public/js/privento-public.js   --minify --log-level=warning --outfile=public/js/privento-public.min.js
npx --yes "$ESBUILD" public/css/privento-public.css --minify --log-level=warning --outfile=public/css/privento-public.min.css
