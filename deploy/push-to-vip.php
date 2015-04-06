#! php
<?php
// inspired by https://github.com/thenbrent 's git to svn deploy script
// http://thereforei.am/2011/04/21/git-to-svn-automated-wordpress-plugin-deployment/

// prevent execution if we're not on the command line
if ( 'cli' != php_sapi_name() )
{
	die;
}

/**
 * Available options:
 *	--git             SSH address of git rep
 *	--svn             SVN url
 *	--svn-revision    SVN revision number
 *	--clean           rebuild base SVN as a clean checkout, defaults to FALSE unless --svn or --svn-revision are provided
 */

$long_options = array(
	'clean',
	'git::',
	'svn-revision::',
	'svn::',
);

$options = getopt( '', $long_options );

// Main config
$pluginslug = 'headlineenvy';

$clean = FALSE;
if ( isset( $options['clean'] ) || isset( $options['git'] ) || isset( $options['svn-revision'] ) )
{
	$clean = TRUE;
}//end if

// Path to temp repos for SVN and Git
// No trailing slashes (be cautious about incorrect paths, note that we rm the contents later)
$svn_repo_path = '/tmp/' . $pluginslug;
$git_repo_path = '/tmp/' . $pluginslug . '-git';

if ( ! file_exists( $svn_repo_path ) )
{
	$clean = TRUE;
}//end if

// Repo URLs
// Remote SVN repo with no trailing slash
$svn_repo_url = 'https://plugins.svn.wordpress.org/' . $pluginslug . '/trunk';

if ( isset( $options['svn'] ) && $options['svn'] )
{
	$svn_repo_url = $options['svn'];
	$clean = TRUE;
}//end if

$git_repo_url = 'git@github.com:FunnelEnvy/headlineenvy.git';

if ( isset( $options['git'] ) && $options['git'] )
{
	$git_repo_url = $options['git'];
}//end if

$svn_ignore_files = array( // paths relative to the top of the svn_repo_path
	'README.md',
	'.git',
	'.gitignore',
	'.gitmodules',
	'config.rb',
	'deploy/',
	'Gruntfile.js',
	'node_modules/',
	'package.json',
	'sass/',
	'tests/',
);

// Let's begin...
echo "
Preparing to push $pluginslug to $svn_repo_url
";

if ( $clean )
{
	echo '
	Cleaning the destination path
	';

	passthru( "rm -Rf $svn_repo_path" );

	$svn_revision = isset( $options['svn-revision'] ) ? '-r ' . $options['svn-revision'] : '';

	echo "
	Creating local copy of SVN repo at $svn_repo_path
	";

	passthru( "svn checkout $svn_revision $svn_repo_url $svn_repo_path" );
}//end if
else
{
	echo '
	Updating the SVN repo at the destination path
	';
	passthru( "pushd $svn_repo_path && svn up && popd" );
}//end else

echo '
Prepping the SVN repo to receive the Git
';

passthru( "rm -Rf $svn_repo_path/*" );

echo '
Exporting the HEAD of master from git to SVN
';
passthru( "git checkout-index -a -f --prefix=$svn_repo_path/" );

echo '
Exporting git submodules to SVN
';
passthru( "git submodule foreach 'git checkout-index -a -f --prefix=$svn_repo_path/\$path/'" );

echo '
Applying overrides
';
passthru( "cp -r $svn_repo_path/deploy/overrides/* $svn_repo_path" );

echo '
Building CSS and JS resources
';
passthru( "grunt ; cp -R js/min $svn_repo_path/js/min ; cp -R css $svn_repo_path/css" );

echo '
Setting svn:ignore properties
';

passthru( "svn propset svn:ignore '" . implode( "\n", $svn_ignore_files ) ." ' $svn_repo_path " );

passthru( "svn proplist -v $svn_repo_path" );

echo '
Marking deleted files for removal from the SVN repo
';

passthru( "svn st $svn_repo_path | grep '^\!' | sed 's/\!\s*//g' | xargs svn rm" );

echo '
Marking new files for addition to the SVN repo
';

passthru( "svn st $svn_repo_path | grep '^\?' | sed 's/\?\s*//g' | xargs svn add" );

echo 'Now forcibly removing the files that are supposed to be ignored in the svn repo';
foreach ( $svn_ignore_files as $file )
{
	passthru( "svn rm --force $svn_repo_path/$file" );
}

echo '
Removing any svn:executable properties for security
';

passthru( "find $svn_repo_path -type f -not -iwholename *svn* -exec svn propdel svn:executable {} \; | grep 'deleted from' 2>/dev/null" );

echo "
Automatic processes complete!

Next steps:

`cd $svn_repo_path` and review the changes
`svn commit` the changes
profit

* svn diff -x \"-bw --ignore-eol-style\" | grep \"^Index:\" | sed 's/^Index: //g' will be your friend if there are a lot of whitespace changes

Good luck!
";
