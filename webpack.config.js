// webpack.config.js
// Extends @wordpress/scripts default config with legacy admin + frontend entry points.
// IMPORTANT: defaultConfig.entry is a FUNCTION in @wordpress/scripts v27+ — must call with ()
// See: https://samhermes.com/posts/customize-default-wp-scripts-config/
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		// Auto-detect all block.json entry points in src/blocks/
		...defaultConfig.entry(),
		// Legacy admin: JS + SCSS compiled to build/admin/index.{js,css}
		'admin/index': [
			path.resolve( __dirname, 'src/admin/index.js' ),
			path.resolve( __dirname, 'src/admin/style.scss' ),
		],
		// Legacy frontend: SCSS only → build/frontend/style.css
		// Note: also emits build/frontend/style.js (empty) — expected, do not enqueue it
		'frontend/style': path.resolve( __dirname, 'src/frontend/style.scss' ),
		// Editor panel styles — loaded in block editor via enqueue_block_editor_assets
		'editor/speekr-panels': path.resolve( __dirname, 'src/editor/speekr-panels.scss' ),
	},
};
