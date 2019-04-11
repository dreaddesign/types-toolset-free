<?php
/**
 * Generate a simple HTML file with links to subdirectories, and save it to "public/index.html".
 *
 * This is used when generating the documentation with Sami, and it's expected to be copied to the root directory
 * of the repository before running.
 *
 * @since 2.5.6
 */


/**
 * @param string $dir Path to a directory
 * @return string[] Names of its subdirectories
 */
function getFileList( $dir ) {
	// array to hold return value
	$retval = array();

	// add trailing slash if missing
	if ( substr( $dir, - 1 ) != "/" ) {
		$dir .= "/";
	}

	// open pointer to directory and read list of files
	$d = dir( $dir ) or die( "getFileList: Failed opening directory $dir for reading" );
	while ( false !== ( $entry = $d->read() ) ) {
		// skip hidden files
		if ( $entry[0] == "." ) {
			continue;
		}
		if ( is_dir( "$dir$entry" ) ) {
			$retval[] = $entry;
		}
	}
	$d->close();

	return $retval;
}


$subdirs = getFileList( __DIR__ . '/public' );

// Sort versions master > develop > anything else, which gets sorted by version_compare in descening order.
//
//
usort( $subdirs, function( $a, $b ) {
	if( 'master' === $a ) {
		if( 'master' === $b ) {
			return 0;
		} else {
			return -1; // $a < $b
		}
	} elseif( 'master' === $b ) {
		// at this point, $a is develop or a specific version
		return 1; // $a > $b
	} elseif( 'develop' === $a ) {
		// at this point, $b is only develop or a specific version
		if( 'develop' === $b ) {
			return 0;
		} else {
			return -1; // $a < $b
		}
	} elseif( 'develop' === $b ) {
		// at this point, $a is always a specific version
		return 1; // $a > $b
	} else {
		return version_compare( $a, $b ) * -1;
	}
} );


// Generate the index file contents.
//
//
ob_start();

?>
<h1>Toolset Common Documentation</h1>
<ul>
	<?php
		foreach( $subdirs as $subdir ) {
			printf(
				"\t<li><a href=\"%s\">%s</a></li>\n",
				$subdir, $subdir
			);
		}
	?>
</ul>
<?php

$output = ob_get_clean();

// Save the index file.
//
//
echo "rendered output for index.html: \n\n$output\n\n";
file_put_contents( __DIR__ . '/public/index.html', $output );