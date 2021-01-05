<?php

/**
 * leadly_get_path
 *
 * Returns the plugin path to a specified file.
 *
 * @param	string $filename The specified file.
 * @return	string
 */
function leadly_get_path( $filename = '' ) {
	return LEADLY_PATH . ltrim($filename, '/');
}

/*
 * leadly_include
 *
 * Includes a file within the LEADLY plugin.
 *
 * @param	string $filename The specified file.
 * @return	void
 */
function leadly_include( $filename = '' ) {
	$file_path = leadly_get_path($filename);
	if( file_exists($file_path) ) {
		include_once($file_path);
	}
}
