<?php
if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

if (!function_exists('base_mutasi')) {
	function base_mutasi($config_name = '') {
		$CI = &get_instance();
		$CI->load->config('base_mutasi');
		$base_mutasi = $CI->config->item('base_mutasi');
		if (isset($base_mutasi[$config_name])) {
			return $base_mutasi[$config_name];
		}
		return "";
	}
}