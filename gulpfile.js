var gulp = require( 'gulp' ),

	// Load the packages.
	autoprefixer = require( 'gulp-autoprefixer' ),
	minifycss    = require( 'gulp-minify-css' ),
	less         = require( 'gulp-less' ),
	rename       = require( 'gulp-rename' ),
	uglify       = require( 'gulp-uglify' ),
	plumber      = require( 'gulp-plumber' ),
	//browserSync  = require( 'browser-sync' ),
	//reload       = browserSync.reload,

	// Env vars.
	csspath   = './assets/css/',
	jspath    = './assets/js/',
	proxypath = 'localhost/wordpress',

	// CSS files.
	cssfiles = [ 'admin', 'speekr' ],
	csss     = [],


	// Gulp Watch error fix
	onError = {
		errorHandler: function (err) {
			console.log( err );
			this.emit('end');
		}
	};


// Gulp CSS task: LESS, autoprefix, rename
cssfiles.forEach( function( name ) {

	csss.push( 'css' + name );
	
	gulp.task('css' + name, function () { 

		return gulp.src(csspath + 'src/' + name + '.less') 

			// watch error handler
			.pipe( plumber( onError ) )

			// Compile LESS 
			.pipe( less() )

			// Will autoprefix the css                    
			.pipe( autoprefixer( { browsers: ['last 2 versions'], cascade: false } ) ) 

			// Rename the file with suffix
			.pipe( rename( { suffix: '.min'} ) )

			// Minify CSS
			.pipe( minifycss() )

			// The folder
			.pipe( gulp.dest( csspath ) );

			// For browser sync
			//.pipe( browserSync.stream() );
	});
} );

gulp.task('js', function () {

	return gulp.src( jspath + 'src/*.js' ) 

		// Will minify the JS
		.pipe( uglify() )      

			// Rename the file with suffix
			.pipe(rename({
				 suffix: '.min'
			}))
		.pipe( gulp.dest( jspath ) ); 

});




// Prepare da watches
gulp.task('watch', function () {
	gulp.watch([ csspath + 'src/*.less', csspath + 'src/less/*.less' ], ['css']);
	gulp.watch( jspath + 'src/*.js', ['js'] );

	// Start the browsersync server
	//browserSync({
	//	proxy: proxypath
	//});

	// Watching PHP and CSS files for change on WP
	//gulp.watch( [ './*.php', csspath + '*.css' ], reload );  

});


// working task
gulp.task( 'css', ['cssadmin', 'cssspeekr'] );
gulp.task( 'work', ['css', 'watch'] );
gulp.task( 'default', ['css'] );