<?php
if (!defined('BASEPATH')) { exit('No direct script access allowed'); }


if (!function_exists('base_config')) {
	function base_config($config_name = '') {
		$CI = &get_instance();
		$CI->load->config('base_dashboard');
		$base_dashboard = $CI->config->item('base_dashboard');
		if (isset($base_dashboard[$config_name])) {
			return $base_dashboard[$config_name];
		}
		return "";
	}
}
if (!function_exists('base_permalink')) {
	function base_permalink($url) {
		$url = strtolower($url);
		$url = preg_replace('/&.+?;/', '', $url);
		$url = preg_replace('/\s+/', '_', $url);
		$url = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '_', $url);
		$url = preg_replace('|%|', '_', $url);
		$url = preg_replace('/&#?[a-z0-9]+;/i', '', $url);
		$url = preg_replace('/[^%A-Za-z0-9 \_\-]/', '_', $url);
		$url = preg_replace('|_+|', '-', $url);
		$url = preg_replace('|-+|', '-', $url);
		$url = trim($url, '-');
		$url = (strlen($url) > 128) ? substr($url, 0, 128) : $url;
		return $url;
	}
}
if (!function_exists('form_close')) {
	function form_close() {
		return '</form>';
	}
}
if (!function_exists('base_safe_text')) {
	function base_safe_text($text, $length, $allow_nl = false) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = trim(chop($text));
		$text = $allow_nl ? $text : preg_replace("/[\r|\n]/", "", $text);
		$text = substr($text, 0, $length);
		return $text;
	}
}
if (!function_exists('callback_date_valid_ymd')) {
	function callback_date_valid_ymd($date) {
		$day = (int) substr($date, 8, 2);
		$month = (int) substr($date, 5, 2);
		$year = (int) substr($date, 0, 4);
		return checkdate($month, $day, $year);
	}
}
//====================================================================================
if (!function_exists('set_sidebar_active')) {
	function set_sidebar_active($link_name, $param_name = 'service', $match = null) {
		if (!isset($match)) {
			$AltoRouter = new AltoRouter();
			$CI = &get_instance();
			$CI->load->config('base_dashboard');
			$base_dashboard = $CI->config->item('base_dashboard');
			if (isset($base_dashboard['altorouter']['mapping'])) {
				if (is_array($base_dashboard['altorouter']['mapping']) && (count($base_dashboard['altorouter']['mapping']) > 0)) {
					foreach ($base_dashboard['altorouter']['mapping'] as $val) {
						if (is_array($val) && (count($val) > 3)) {
							$AltoRouter->map($val[0], $val[1], $val[2], $val[3]);
						}
					}
				}
			}
			$match = $AltoRouter->match();
		}
		$param_name = (is_string($param_name) ? strtolower($param_name) : 'service');
		switch ($param_name) {
			case 'version':
				$link_name = (is_string($link_name) ? $link_name : 'dashboard');
				if (isset($match['params'][$param_name])) {
					if (is_string($match['params'][$param_name])) {
						if (strtolower($match['params'][$param_name]) === strtolower($link_name)) {
							return "active";
						}
					}
				}
			break;
			case 'service':
				$link_name = (is_string($link_name) ? $link_name : 'dashboard');
				if (isset($match['params'][$param_name])) {
					if (is_string($match['params'][$param_name])) {
						if (strtolower($match['params'][$param_name]) === strtolower($link_name)) {
							return "active";
						}
					}
				}
			break;
			case 'method':
				$link_name = (is_array($link_name) ? $link_name : array('service' => 'dashboard', 'method' => 'index'));
				if (isset($link_name['service']) && isset($link_name['method'])) {
					if (isset($match['params']['service']) && isset($match['params']['method'])) {
						if (is_string($match['params']['service']) && is_string($match['params']['method'])) {
							if (($match['params']['service'] == $link_name['service']) && ($match['params']['method'] == $link_name['method'])) {
								return 'active';
							}
						}
					}
				}
			break;
			case 'methodsub':
				$link_name = (is_array($link_name) ? $link_name : array('service' => 'dashboard', 'method' => 'index'));
				if (isset($link_name['service']) && isset($link_name['method'])) {
					if (isset($match['params']['service']) && isset($match['params']['method'])) {
						if (is_string($match['params']['service']) && is_string($match['params']['method'])) {
							if (($match['params']['service'] == $link_name['service']) && (strpos($match['params']['method'], $link_name['method']) !== FALSE)) {
								return 'active';
							}
						}
					}
				}
			break;
			case 'methodchild':
				$link_name = (is_array($link_name) ? $link_name : array('service' => 'dashboard', 'method' => 'index', 'segment' => 'all'));
				if (isset($link_name['service']) && isset($link_name['method']) && isset($link_name['segment'])) {
					if (isset($match['params']['service']) && isset($match['params']['method']) && isset($match['params']['segment'])) {
						if (is_string($match['params']['service']) && is_string($match['params']['method']) && is_string($match['params']['segment'])) {
							if (($match['params']['service'] == $link_name['service']) && ($match['params']['method'] == $link_name['method']) && ($match['params']['segment'] == $link_name['segment'])) {
								return 'active';
							}
						}
					}
				}
			break;
		}
		return '';
	}
}















