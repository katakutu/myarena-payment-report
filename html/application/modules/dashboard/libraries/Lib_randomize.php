<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Lib_randomize {


	//======================================================
	function check_new_group_url($group_url = '', $localdata = null) {
		if (!isset($localdata)) {
			$localdata = $this->authentication->localdata;
		}
		$local_seq = (isset($localdata['seq']) ? $localdata['seq'] : 0);
		$group_url = (is_string($group_url) ? $group_url : '');
		//preg_replace("/-$/","",preg_replace('/[^a-z0-9]+/i', "-", strtolower($title)));
		$group_url = $this->imzers->safe_text_post($group_url, 128);
		$sql = "SELECT COUNT(seq) AS value FROM {$this->authentication->tables['data_addressbook_group']} WHERE (group_owner = '{$this->imzers->sql_addslashes($local_seq)}') AND (CONCAT('', group_name_url, '') LIKE('%{$this->imzers->sql_addslashes($group_url)}%'))";
		$sql_query = $this->imzers->db_query($sql);
		$row = $sql_query->fetch_assoc();
		$value = (int)$row['value'];
		return ($value > 0) ? ($group_url . '-' . $value) : $group_url;
	}
	function check_new_item_url($item_url = '', $localdata = null) {
		if (!isset($localdata)) {
			$localdata = $this->authentication->localdata;
		}
		$local_seq = (isset($localdata['seq']) ? $localdata['seq'] : 0);
		$item_url = (is_string($item_url) ? $item_url : '');
		//preg_replace("/-$/","",preg_replace('/[^a-z0-9]+/i', "-", strtolower($title)));
		$item_url = $this->imzers->safe_text_post($item_url, 128);
		$sql = "SELECT COUNT(seq) AS value FROM {$this->authentication->tables['data_addressbook_item']} WHERE (item_owner = '{$this->imzers->sql_addslashes($local_seq)}') AND (CONCAT('', item_name_url, '') LIKE '%{$this->imzers->sql_addslashes($item_url)}%')";
		$sql_query = $this->imzers->db_query($sql);
		$row = $sql_query->fetch_assoc();
		$value = (int)$row['value'];
		return ($value > 0) ? ($item_url . '-' . $value) : $item_url;
	}
	private function check_new_code($link_code, $table = null, $link_seq = 0) {
		if (!isset($table)) {
			$table = $this->authentication->tables['data_addressbook_group'];
		}
		$link_code = (is_string($link_code) ? $link_code : '');
		$value = 0;
		$sql = sprintf("SELECT COUNT(seq) AS value FROM %s WHERE", $table);
		if (strlen($link_code) > 0) {
			if (strtolower($table) === 'data_addressbook_groups') {
				$sql .= sprintf(" (group_code = '%s')", $link_code);
			} else {
				$sql .= sprintf(" (item_code = '%s')", $link_code);
			}
			if ((int)$link_seq > 0) {
				$sql .= sprintf(" AND (seq != '%d')", $link_seq);
			}
			$sql_query = $this->imzers->db_query($sql);
			while ($row = $sql_query->fetch_assoc()) {
				$value = $row['value'];
			}
		} else {
			$value = 0;
		}
		return $value;
	}
	function generate_new_code($table, $length = 12) {
		do {
			$random = $this->get_new_unique_code($length);
			$rows = $this->check_new_code($random);
		} while ($rows > 0);
		return $random;
	}
	function get_new_unique_code($vLength = 12) {
		$sRandomString = "";    
		$sChr = "";
		for ($i = 0 ; $i < $vLength ; $i++) {
			$vState = rand(1, 3);
			switch ($vState) {
				case 1: $sChr = chr(rand(65, 90));  break;  // CAPS (A-Z)
				case 2: $sChr = chr(rand(97, 122)); break;  // small (a-z)
				case 3: $sChr = chr(rand(48, 57));  break;  // Numbers (0-9)
			}
			if (!in_array($sChr, array('O', 'o', '0', '1', 'l'))) {
				$sRandomString .= $sChr;
			} else {
				$i--;
			}
		}
		return $sRandomString;
	}
	
	
	
	
	
	
}