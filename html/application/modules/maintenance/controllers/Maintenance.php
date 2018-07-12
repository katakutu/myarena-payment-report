<?php
defined('BASEPATH') OR exit('No direct script access allowed: Maintenance');
class Maintenance extends MX_Controller {
	function __construct() {
		parent::__construct();
		
		
	}
	function index() {
		$this->load->view('maintenance/maintenance');
	}
}