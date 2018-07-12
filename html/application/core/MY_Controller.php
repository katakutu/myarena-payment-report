<?php (defined('BASEPATH')) OR exit('No direct script access allowed');
/* load the MX_Controller class */
require(APPPATH . "third_party/MX/Controller.php");

class MY_Controller extends MX_Controller {
	protected $maintenance = FALSE;
	function __construct() {
		parent::__construct();
		$this->maintenance = ConstantConfig::$maintenance;
		if ($this->maintenance === TRUE) {
			//$this->output->set_status_header('302');
			//redirect(base_url('maintenance'));
			$this->output->set_status_header('503');
			$show_maintenance = $this->show_maintenance();
			echo 'Maintenance is ON';
			exit;
		}
	}
	function get_is_maintenance() {
		return $this->maintenance;
	}
	function show_maintenance() {
		return $this->load->view('maintenance/maintenance');
	}
}
