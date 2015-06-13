<?php

class TimberURLHelper {

	/**
	 * @return string
	 */
	public static function get_current_url() {
		$pageURL = "http://";
		if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
			$pageURL = "https://";;
		}
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public static function is_url($url) {
		if (!is_string($url)) {
			return false;
		}
		$url = strtolower($url);
		if (strstr($url, '://')) {
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public static function get_path_base() {
		$struc = get_option('permalink_structure');
		$struc = explode('/', $struc);
		$p = '/';
		foreach ($struc as $s) {
			if (!strstr($s, '%') && strlen($s)) {
				$p .= $s . '/';
			}
		}
		return $p;
	}

	/**
	 * @param string $url
	 * @param bool $force
	 * @return string
	 */
	public static function get_rel_url($url, $force = false) {
		$url_info = parse_url($url);
		if (isset($url_info['host']) && $url_info['host'] != $_SERVER['HTTP_HOST'] && !$force) {
			return $url;
		}
		$link = '';
		if (isset($url_info['path'])){
			$link = $url_info['path'];
		}
		if (isset($url_info['query']) && strlen($url_info['query'])) {
			$link .= '?' . $url_info['query'];
		}
		if (isset($url_info['fragment']) && strlen($url_info['fragment'])) {
			$link .= '#' . $url_info['fragment'];
		}
		$link = TimberURLHelper::remove_double_slashes($link);
		return $link;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public static function is_local($url) {
		if (strstr($url, $_SERVER['HTTP_HOST'])) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $src
	 * @return string
	 */
	public static function get_full_path($src) {
		$root = ABSPATH;
		$old_root_path = $root . $src;
		$old_root_path = str_replace('//', '/', $old_root_path);
		return $old_root_path;
	}

	/**
	 * Takes a url and figures out its place based in the file system based on path
	 * NOTE: Not fool-proof, makes a lot of assumptions about the file path
	 * matching the URL path
	 * @param string $url
	 * @return string
	 */
	public static function url_to_file_system($url) {
		$url_parts = parse_url($url);
		$path = ABSPATH . $url_parts['path'];
		$path = str_replace('//', '/', $path);
		return $path;
	}

	public static function file_system_to_url( $fs ) {
		$relative_path = self::get_rel_path($fs);
		$home = home_url('/'.$relative_path);
		return $home;
	}

	/**
	 * @param string $src
	 * @return string
	 */
	public static function get_rel_path($src) {
		if (strstr($src, ABSPATH)) {
			return str_replace(ABSPATH, '', $src);
		}
		//its outside the wordpress directory, alternate setups:
		$src = str_replace(WP_CONTENT_DIR, '', $src);
		return WP_CONTENT_SUBDIR . $src;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public static function remove_double_slashes($url) {
		$url = str_replace('//', '/', $url);
		if (strstr($url, 'http:') && !strstr($url, 'http://')) {
			$url = str_replace('http:/', 'http://', $url);
		}
		return $url;
	}

	/**
	 * @param string $url
	 * @param string $path
	 * @return string
	 */
	public static function prepend_to_url($url, $path) {
		if (strstr(strtolower($url), 'http')) {
			$url_parts = parse_url($url);
			$url = $url_parts['scheme'] . '://' . $url_parts['host'] . $path . $url_parts['path'];
			if ( isset($url_parts['query']) ) {
				$url .= $url_parts['query'];
			}
			if ( isset($url_parts['fragment']) ) {
				$url .= $url_parts['fragment'];
			}
		} else {
			$url = $url . $path;
		}
		return self::remove_double_slashes($url);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function preslashit($path) {
		if (strpos($path, '/') != 0) {
			$path = '/' . $path;
		}
		return $path;
	}

	/**
	 * @return boolean true if $path is an absolute url, false if relative.
	 */
	public static function is_absolute($path) {
		return (boolean) (strstr($path, 'http' ));
	}

	/**
	 * This function is slightly different from the one below in the case of:
	 * an image hosted on the same domain BUT on a different site than the
	 * Wordpress install will be reported as external content.
	 *
	 * @param  [type]  $url [description]
	 * @return boolean      if $url points to
	 */
	public static function is_external_content($url) {
		// using content_url() instead of site_url or home_url is IMPORTANT
		// otherwise you run into errors with sites that:
		// 1. use WPML plugin
		// 2. or redefine upload directory
		$is_external = TimberURLHelper::is_absolute($url) && !strstr($url, content_url());
		// $is_external = TimberURLHelper::is_absolute($url) && !strstr($url, site_url());
  //    	if ($is_external) {
  //       	$is_external = TimberURLHelper::is_absolute($url) && !strstr($url, home_url());
  //       }
        return $is_external;
	}

	/**
	 * @param string $url
	 * @return bool     true if $path is an external url, false if relative or local.
	 *                  true if it's a subdomain (http://cdn.example.org = true)
	 */
	public static function is_external($url) {
		$has_http = strstr(strtolower($url), 'http');
		$on_domain = strstr($url, $_SERVER['HTTP_HOST']);
		if ($has_http && !$on_domain) {
			return true;
		}
		return false;
	}

	/**
	 * Pass links through untrailingslashit unless they are a single /
	 *
	 * @param  string $link
	 * @return string
	 */
	public static function remove_trailing_slash($link) {
		if ( $link != "/")
			$link = untrailingslashit( $link );
		return $link;
	}

	/**
	 * @param string $url
	 * @param int $timeout
	 * @return string|WP_Error
	 * @deprecated since 0.20.0
	 */
	static function download_url($url, $timeout = 300) {
		if (!$url) {
			return new WP_Error('http_no_url', __('Invalid URL Provided.'));
		}

		$tmpfname = wp_tempnam($url);
		if (!$tmpfname) {
			return new WP_Error('http_no_file', __('Could not create Temporary file.'));
		}

		$response = wp_remote_get($url, array('timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname));

		if (is_wp_error($response)) {
			unlink($tmpfname);
			return $response;
		}
		if (200 != wp_remote_retrieve_response_code($response)) {
			unlink($tmpfname);
			return new WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)));
		}
		return $tmpfname;
	}

	/**
	 * @param int $i
	 * @return array
	 */
	public static function get_params($i = false) {
		$args = explode('/', trim(strtolower($_SERVER['REQUEST_URI'])));
		$newargs = array();
		foreach ($args as $arg) {
			if (strlen($arg)) {
				$newargs[] = $arg;
			}
		}
		if ($i === false){
			return $newargs;
		}
		if ($i < 0){
			//count from end
			$i = count($newargs) + $i;
		}
		if (isset($newargs[$i])) {
			return $newargs[$i];
		}
	}

}
