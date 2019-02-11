<?php
/*
 * xili-protect-files.php
 *
 * Protect uploaded files with login.
 *
 * @link https://github.com/dev-xiligroup/private-files
 *
 * @author xiligroup - inspired by hakre <http://hakre.wordpress.com/>
 * @license GPL-3.0+
 */
/**
 * example of lines to insert in .htaccess - here test only pdf and zip
 * RewriteCond %{REQUEST_URI} \.(pdf|zip)$ [NC]
 * RewriteCond %{REQUEST_FILENAME} -s
 * RewriteRule ^wp-content/uploads/(.*)$ wp-content/mu-plugins/files-protect/xili-protect-files.php?file=$1 [QSA,L]
 */

require_once( '../../../wp-load.php' ); // here in mu-plugins sub-folder

list( $basedir ) = array_values( array_intersect_key( wp_upload_dir(), array( 'basedir' => 1 ) ) ) + array( null );

$file_url = isset( $_GET['file'] ) ? $_GET['file'] : '';

$file = rtrim( $basedir, '/' ) . '/' . str_replace( '..', '', $file_url );
if ( ! $basedir || ! is_file( $file ) ) {
	status_header( 404 );
	die( '404 &#8212; File not found.' );
}

$mime = wp_check_filetype( $file );

if ( check_file_authorization( $file_url ) && check_user_authorization( $mime ) ) {

	if ( false === $mime['type'] && function_exists( 'mime_content_type' ) ) {
		$mime['type'] = mime_content_type( $file );
	}

	if ( $mime['type'] ) {
		$mimetype = $mime['type'];
	} else {
		$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
	}

	header( 'Content-Type: ' . $mimetype ); // always send this
	if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
		header( 'Content-Length: ' . filesize( $file ) );
	}

	$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
	$etag = '"' . md5( $last_modified ) . '"';
	header( "Last-Modified: $last_modified GMT" );
	header( 'ETag: ' . $etag );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

	// Support for Conditional GET
	$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

	if ( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
	}

	$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
	// If string is empty, return 0. If not, attempt to parse into a timestamp
	$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

	// Make a timestamp for our most recent modification...
	$modified_timestamp = strtotime( $last_modified );

	if ( ( $client_last_modified && $client_etag )
		? ( ( $client_modified_timestamp >= $modified_timestamp ) && ( $client_etag == $etag ) )
		: ( ( $client_modified_timestamp >= $modified_timestamp ) || ( $client_etag == $etag ) )
		) {
		status_header( 304 );
		exit;
	}

	// If we made it this far, just serve the file
	readfile( $file );
	exit;
} else { // get_permalink( $post->post_parent )
	// error_log( '******** UNAUTHORIZED ' . $file );
	if ( wp_redirect( home_url() . '?message=UNAUTHORIZED' ) ) {
		exit;
	}
}

/**
 * check user authorization according mime type if not logged - must be coherant with htaccess
 * @param  array $mime mime type array
 * @return boolean       true if ok
 */
function check_user_authorization( $mime ) {
	// error_log( '******** ' . serialize( $mime ) );
	if ( ! is_user_logged_in() ) {
		if ( in_array( $mime['ext'], array( 'jpg', 'jpeg', 'png' ) ) ) {
			$authorized = true;
		} else {
			$authorized = false;
		}
	} else {
		$authorized = true;
	}
	return $authorized;
}
/**
 * check user capabilities description]
 * @param  text $protect_content describe capability (w/o prefix read)
 * @return boolean true if ok
 */
function check_user_capabilities( $protect_content ) {
	if ( ! is_user_logged_in() ) {
		return false;
	} else {
		// $the_current_user = wp_get_current_user();
		if ( current_user_can( 'read_' . $protect_content ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * check file authorization according user capabilities and private protection
 * @param  text $full_file file folder and name as in table post
 * @return boolean            true iff authorized to be downloaded
 */
function check_file_authorization( $full_file ) {
	$attachments = get_posts(
		array(
			'post_type' => 'attachment',
			'meta_query' => array(
				array(
					'key' => '_wp_attached_file',
					'value' => $full_file,
				),
			),
		)
	);
	// here only test first parent found
	if ( 0 < count( $attachments ) && 0 < $attachments[0]->post_parent ) { // attachment found and parent available
		// error_log( '***** full_file 1  *** ' . $full_file );
		if ( post_password_required( $attachments[0]->post_parent ) ) { // password for the post is not available
			return false;
		}
		$status = get_post_meta( $attachments[0]->post_parent, 'xili_protect_content', true );
		// error_log( '*****$status*** ' . serialize( $status ) . ' - ' . $attachments[0]->post_parent );
		if ( 1 == $status ) {
			if ( ! check_user_capabilities( 'xili_protect_content' ) ) {
				return false;
			}
		}
	} else {

		// not a normal attachment check for thumbnail
		$filename   = pathinfo( $full_file );
		$attachments     = get_posts(
			array(
				'post_type' => 'attachment',
				'meta_query' => array(
					array(
						'key' => '_wp_attachment_metadata',
						'compare' => 'LIKE',
						'value' => $filename['filename'] . '.' . $filename['extension'],
					),
				),
			)
		);
		if ( 0 < count( $attachments ) ) {
			// error_log( '***** full_file 2  *** ' . $full_file );
			foreach ( $attachments as $single_image ) {
				$meta = wp_get_attachment_metadata( $single_image->ID );
				if ( 0 < count( $meta['sizes'] ) ) {
					$filepath   = pathinfo( $meta['file'] );
					if ( $filepath['dirname'] == $filename['dirname'] ) {// current path of the thumbnail
						foreach ( $meta['sizes'] as $single_size ) {
							if ( $filename['filename'] . '.' . $filename['extension'] == $single_size['file'] ) {
								if ( post_password_required( $single_image->post_parent ) ) { // password for the post is not available
									return false;
								}
								//die( 'dD' );
								$status = get_post_meta( $single_image->post_parent, 'xili_protect_content', true );

								if ( 1 == $status ) {
									if ( ! check_user_capabilities( 'xili_protect_content' ) ) {
										return false;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return true;
}
