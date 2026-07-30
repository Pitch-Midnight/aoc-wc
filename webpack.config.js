const path = require( 'path' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
// NOTE: ../trs-build-targets is required lazily, inside the config function.
// It lives in the sibling wp-plugin-build repo, which a CI runner checking out
// only this plugin does not have. Requiring it at module scope would fail the
// build on a runner that never intended to deliver anywhere.
//
// REMOVED 2026-07-30: ./shared-configs/webpack-configs/plugins.webpack-config
// An earlier attempt at what trs-build-targets.js now does. It failed because
// the machine paths stayed hardcoded INSIDE the shared file, so sharing the
// config without externalising the paths just relocated the problem. Same
// removal as add-to-cart-pro and enhanced-ajax-add-to-cart-wc, which carried
// copies of it.

const pluginSlug = 'additional-order-costs-for-woocommerce';

// env and argv are DEFAULTED because `npm run build` deliberately passes
// neither: a machine-independent build must not need a delivery target, and some
// webpack-cli versions call the config function with env undefined.
const config = ( env = {}, argv = {} ) => {

	const isProduction = argv.mode === 'production';

	// MACHINE DELIVERY IS OPT-IN, via --env LOC. Compiling and packaging are
	// machine-independent; copying into a running WordPress install is not.
	// Opt-in rather than falling back to a defaultTarget, so `npm run build`
	// cannot quietly write into a live test site.
	const targets = env.LOC
		? require( '../trs-build-targets' ).resolve( pluginSlug, env.LOC )
		: null;

	if ( targets ) {
		console.log( `[trs] target ${ targets.target } (config: ${ targets.source })` );
		console.log( `[trs] dev -> ${ targets.devFolder }` );
	} else {
		console.log( '[trs] no --env LOC given - compiling only, no delivery.' );
	}

	// The release payload, kept identical to what the old shared-configs
	// production branch copied: blocks/, src/ and vendor/ were commented out
	// there and stay out here. `build/` is the wp-scripts output directory.
	const payloadTo = ( destination ) => ( [
		{ from: path.resolve( __dirname, 'assets' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'cmb2' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'build' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'languages' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'includes' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'woo-includes' ) + '/**', to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'README.txt' ), to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, 'LICENSE.txt' ), to: destination, noErrorOnMissing: true },
		{ from: path.resolve( __dirname, '*.php' ), to: destination, noErrorOnMissing: true },
	] );

	const pluginList = [ ...defaultConfig.plugins ];

	// PRODUCTION DELIVERY IS NOT DONE HERE. CopyWebpackPlugin globs its sources
	// when a compilation starts but webpack writes its output when it ends, so
	// copying the build's own product in the same pass is a race - it delivers
	// everything except what the build just made. `npm run deploy` runs
	// ../trs-deliver.js against the payload trs-package.js already staged.
	//
	// The watch path keeps CopyWebpackPlugin, because files need to land in the
	// site on every rebuild. CopyWebpackPlugin also rejects an empty `patterns`
	// array, so it must be omitted rather than given nothing to do.
	if ( ! isProduction && targets ) {
		pluginList.push( new CopyWebpackPlugin( { patterns: payloadTo( targets.devFolder ) } ) );
	}

	// defaultConfig.entry is a function in @wordpress/scripts 20+ and a plain
	// object in older releases. Handled both ways so a toolchain bump does not
	// break this line.
	const baseEntry = typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: { ...defaultConfig.entry };

	const entries = { ...baseEntry };
	entries['aoc-wc-admin'] = path.resolve(__dirname, 'assets/js/aoc-wc-admin.js')

	return {
		...defaultConfig,
		plugins: pluginList,
		watchOptions: {
			ignored: ['**/build/**', '**/node_modules'],
		},
		entry: {
			...entries,
		}
	}
}

module.exports = config;
