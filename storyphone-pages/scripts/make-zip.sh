#!/usr/bin/env bash
#
# Build an installable plugin zip for wp-admin → Plugins → Add New → Upload.
#
# The archive contains a single top-level storyphone-pages/ directory, which is
# what WordPress expects. Source and tooling are excluded so the upload stays small.

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="$(basename "$PLUGIN_DIR")"
PARENT_DIR="$(dirname "$PLUGIN_DIR")"
ZIP_PATH="$PARENT_DIR/$PLUGIN_SLUG.zip"

if [ ! -f "$PLUGIN_DIR/build/main.js" ]; then
	echo "error: build/main.js is missing. Run 'npm run build' first." >&2
	exit 1
fi

rm -f "$ZIP_PATH"

cd "$PARENT_DIR"
zip -r -q "$ZIP_PATH" "$PLUGIN_SLUG" \
	-x "$PLUGIN_SLUG/node_modules/*" \
	-x "$PLUGIN_SLUG/src/*" \
	-x "$PLUGIN_SLUG/scripts/*" \
	-x "$PLUGIN_SLUG/.git/*" \
	-x "$PLUGIN_SLUG/.tmp-*" \
	-x "$PLUGIN_SLUG/.tmp-*/*" \
	-x "$PLUGIN_SLUG/package.json" \
	-x "$PLUGIN_SLUG/package-lock.json" \
	-x "$PLUGIN_SLUG/vite.config.js" \
	-x "*/.DS_Store" \
	-x "*.zip"

echo "Created: $ZIP_PATH"
unzip -l "$ZIP_PATH" | tail -n 5
