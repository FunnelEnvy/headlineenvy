/*global module:false*/

module.exports = function( grunt ) {
	'use strict';

	// Project configuration.
	grunt.initConfig({
		compass: {
			dist: {
				config: 'config.rb',
				debugInfo: false
			}
		},
		cssmin: {
			minify: {
				expand: true,
				cwd: 'components/css/',
				src: ['*.css'],
				dest: 'components/css/min',
				ext: '.min.css'
			}
		},
		uglify: {
			compress: {
				files: [
					{
						expand: true, // enable dynamic expansion
						cwd: 'components/js/lib', // src matches are relative to this path
						src: ['**/*.js'], // pattern to match
						dest: 'components/js/min/'
					}
				]
			}
		},
		watch: {
			js: {
				files: ['components/js/lib/**/*.js'],
				tasks: [
					'newer:uglify'
				]
			},
			sass: {
				files: ['components/sass/**/*.scss'],
				tasks: [
					'compass:dist'
				]
			}
		},
		copy: {
			deploy: {
				files: [{
					expand: true,
					src: [
						'**',
						'!**/assets/**',
						'!**/bin/**',
						'!**/deploy/**',
						'!**/sass/**',
						'!**/node_modules/**',
						'!**/tests/**',
						'!config.rb',
						'!Gruntfile.js',
						'!package.json',
						'!phpunit.xml',
						'!README.md'
					],
					dest: 'deploy/'
				}],
			},
		},
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'headlineenvy',
					build_dir: 'deploy'
				},
			}
		},
		clean: [ 'deploy' ]
	});

	// Default task.
	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.registerTask( 'default', [
		'newer:uglify',
		'compass:dist',
		'cssmin'
	] );
	
	grunt.registerTask( 'deploy', [
		'copy:deploy',
		//'wp_deploy:deploy',
		//'clean'
	] );
};
