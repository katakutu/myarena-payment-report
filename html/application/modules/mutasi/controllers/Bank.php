<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Bank extends MY_Controller {
	public $is_editor = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	protected $base_dashboard, $base_mutasi = array();
	function __construct() {
		parent::__construct();
		$this->load->helper('dashboard/dashboard_functions');
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->load->model('dashboard/Model_account', 'mod_account');
		$this->DateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		if (($this->authentication->localdata != FALSE)) {
			if (in_array((int)$this->authentication->localdata['account_role'], base_config('editor_role'))) {
				$this->is_editor = TRUE;
			}
		}
		
		# Load Library
		$this->load->library('mutasi/Lib_bni', FALSE, 'bni');
		$this->load->library('mutasi/Lib_bca', FALSE, 'bca');
		$this->load->library('mutasi/Lib_mandiri', FALSE, 'mandiri');
		$this->load->library('mutasi/Lib_bri', FALSE, 'bri');
		
		# Make Transaction Data
		$this->transaction_date = array(
			'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d')),
			'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d')),
		);
	}
	private function parse_number($number, $dec_point = null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d' . preg_quote($dec_point).']/', '', $number)));
	}
	function create_time_zone($timezone, $datetime = null) {
		if (!isset($datetime)) {
			$datetime = date('Y-m-d H:i:s');
		}
		$DateObject = new DateTime($datetime);
		$DateObject->setTimezone(new DateTimeZone($timezone));
		// TO using use @DateObject->format('Y') : Year
		return $DateObject;
	}
	function index() {
		echo 'hello..';
	}
	
	
	
	
	
	
	function getbcaweb() {
		echo "<pre>";
		$login = $this->bca->login('idrus9007', '788888');
		
				
		
		//$rekening = $this->bca->get_informasi_rekening();
		$logout = $this->bca->logout();
		
		
		echo "\r\n\n\n----\r\n\n\n";
		print_r($login);
		echo "\r\n\n\n----\r\n\n\n";
		//print_r($rekening);
		echo "\r\n\n\n----\r\n\n\n";
		//print_r($logout);
		
	}
	function getbcaweb_trans() {
		echo "<pre>";
		$login = $this->bca->login('idrus9007', '788888');
		$transactions = $this->bca->get_mutasi_transactions($this->transaction_date);
		
		$logout = $this->bca->logout();
		
		print_r($transactions);
	}
	
	
	
	
	function bri() {
		echo "<pre>";
		$login = $this->bri->login('Hendra9625', 'Anakripas123');
		//$login = $this->bri->login('stendi2001', 'Aa788888');
		
		$rekening = $this->bri->get_informasi_rekening();
		$logout = $this->bri->logout();
		
		
		echo "\r\n\n\n----\r\n\n\n";
		//print_r($login);
		
		echo "\r\n\n\n----\r\n\n\n";
		print_r($rekening);
		
	}
	
	function bri_trans() {
		echo "<pre>";
		//$login = $this->bri->login('Hendra9625', 'Anakripas123');
		//$login = $this->bri->login('stendi2001', 'Aa788888');
		$login = $this->bri->login('ramdan2511', 'Aa788888');
		/*
		$this->load->model('mutasi/Model_mutasi', 'mod_mutasi');
		$data = $this->mod_mutasi->get_rekening_transaction_by('seq', 6, array());
		print_r($data);
		exit;
		*/
		
		//$transactions = $this->bri->get_mutasi_transactions($this->transaction_date, '505601015659535');
		//$transactions = $this->bri->get_mutasi_transactions($this->transaction_date, '534901019125531');
		$transactions = $this->bri->get_mutasi_transactions($this->transaction_date, '107401002497538');
		
		$logout = $this->bri->logout();
		
		print_r($transactions);
	}
	
	function mandiri() {
		echo "<pre>";
		$login = $this->mandiri->login('81irwan', '323232');
		
		$rekening = $this->mandiri->get_informasi_rekening();
		$logout = $this->mandiri->logout();
		
		
		
		
		
		
		echo "\r\n\n\n----\r\n\n\n";
		print_r($login);
		echo "\r\n\n\n----\r\n\n\n";
		
		print_r($rekening);
		
		
		echo "\r\n\n\n----\r\n\n\n";
		//print_r($logout);
		
	}
	function mandiri_trans() {
		echo "<pre>";
		$login = $this->mandiri->login('81irwan', '323232');
		$transactions = $this->mandiri->get_mutasi_transactions($this->transaction_date);
		
		$logout = $this->mandiri->logout();
		
		print_r($transactions);
	}
	
	
	function bni_trans() {
		echo "<pre>";
		$login = $this->bni->login('2010deni', 'aa788888');

				
		// Get Transactions
		if (isset($login['form_data']['form_logout']['url']) && isset($login['form_data']['form_logout']['query_params'])) {
			try {
				$transactions = $this->bni->get_mutasi_transactions($login['form_data']['form_logout']['url'], $login['form_data']['form_logout']['query_params'], $this->transaction_date);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get transaction data: {$ex->getMessage()}";
				$transactions = false;
			}
		} else {
			$transactions = false;
		}
		
		if (isset($transaction['logout_params'])) {
			$logout = $this->bni->logout($login['form_data']['form_logout']['url'], $transaction['logout_params']);
		} else {
			$logout = false;
		}
		
		print_r($transactions);
		
	}
	
	function bca_trans() {
		echo "<pre>";
		$login = $this->bca->login('imranasr1207', '841020');
		$transactions = $this->bca->get_mutasi_transactions($this->transaction_date);
		
		$logout = $this->bca->logout();
		
		print_r($transactions);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	function parse() {
		$collectData = array(
			'html' 			=> $this->gethtml(),
			'dom'			=> array(
				'doc' 				=> new DOMDocument,
				'items'				=> array(),
			),
			'dateobject'	=> $this->create_time_zone(ConstantConfig::$timezone),
			'as_fid'		=> array(),
		);
		$collectData['dom']['dom_elements'] = array();
		$informasi_rekening = array(
			'rekening_number'			=> '',
			'rekening_name'				=> '',
			'rekening_periode'			=> '',
			'rekening_currency'			=> '',
		);
		libxml_use_internal_errors(true);
		//====================================================
		$collectData['dom']['doc']->preserveWhiteSpace = false;
		$collectData['dom']['doc']->validateOnParse = false;
		$collectData['dom']['doc']->loadHTML($collectData['html']);
		$collectData['dom']['xpath'] = new DOMXPath($collectData['dom']['doc']);
		// Informasi Rekening
		$collectData['dom']['login_form'] = $collectData['dom']['xpath']->query("//input[@name='entered_login']");
		$collectData['dom']['post_form'] = $collectData['dom']['xpath']->query("//script[@language='javascript']");
		
		
		
		
		
		
		
		//----
		$collectData['required_params'] = array();
		$collectData['query_params'] = array();
		//----
		
		print_r($collectData);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function ua() {
		echo $this->gethtml();
	}
	function gethtml() {
		$html = <<<HTML
<SCRIPT LANGUAGE="JavaScript">
<!-- 
function popUp(URL) {
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=950,height=200,left = 112,top = 310');");
}

function popUp3(URL) {
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=550,height=400,left = 100,top = 134');");
}
function popUp4(URL) {
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=300,height=200,left = 100,top = 134');");
}
function target_popup(URL){
	day = new Date();
	id = day.getTime();
	eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=500,left = 100,top = 134');");
}
// End -->
</script>
19681302 dp Rejecte1d<br><SCRIPT LANGUAGE="JavaScript">
<!--

function hilang() {
	document.getElementById("xxx").style.visibility = "hidden";
}

var checkflag = "false";
function check(field) {
	var totalbox = document.getElementById('jumbox').value;
	if (checkflag == "false") {
		for (i = 1; i <= totalbox; i++) {
			document.getElementById('input'+i).checked = true;
		}
		checkflag = "true";
		return; 
	}else {
		for (i = 1; i <= totalbox; i++) {
			document.getElementById('input'+i).checked = false;
		}
		checkflag = "false";
		return;
	}
}
//-->
</SCRIPT>
<table width=50% align=center border=1 bordercolor=#FFFFFF cellpadding=0 cellspacing=0>
	<tr bgcolor=#157bcd style="font-family:Arial,Tahoma,Verdana;font-size:18px">
		<td align=center width=20% style="padding:8px;"><b><a href="agen_playermoneyx.php?action=1" style="text-decoration:none;color:black;">Deposit</a></b></td>
		<td align=center width=20%><b><a href="agen_playermoneyx.php?action=2" style="text-decoration:none;color:black;">Withdraw</a></b></td>
		<td align=center width=20%><b><a href="agen_transmanual.php?action=2" style="text-decoration:none;color:black;">Transaksi Manual</a></b></td>
		<td align=center width=20%><b><a href="agen_useronhold.php" style="text-decoration:none;color:black;">On Hold</a></b></td>
			<td align=center width=20%><b><a href="agen_operator.php" style="text-decoration:none;color:black;">Report</a></b></td>
		<!-- <td align=center width=20%><b><a href="agen_playermoneyxhis.php" style="text-decoration:none;color:black;">History - Player</a></b></td> -->
	</tr>
</table>&nbsp&nbsp
<b><a href="agen_playermoneyx.php?action=1" style="text-decoration:none;color:BLACK;">[ALL]</a></b>&nbsp&nbsp
<b>[<a href="agen_playermoneyx.php?action=1&bank=BCA" style="text-decoration:none;color:blue;">BCA 0</a>]</b>&nbsp&nbsp<b>[<a href="agen_playermoneyx.php?action=1&bank=BNI" style="text-decoration:none;color:blue;">BNI 0</a>]</b>&nbsp&nbsp<b>[<a href="agen_playermoneyx.php?action=1&bank=BRI" style="text-decoration:none;color:blue;">BRI 0</a>]</b>&nbsp&nbsp<b>[<a href="agen_playermoneyx.php?action=1&bank=DANAMON" style="text-decoration:none;color:blue;">DANAMON 0</a>]</b>&nbsp&nbsp<b>[<a href="agen_playermoneyx.php?action=1&bank=MANDIRI" style="text-decoration:none;color:blue;">MANDIRI 0</a>]</b>&nbsp&nbsp<center><font color=red face=arial size=2><b>No New Request</b></font></center>
HTML;
		return $html;
	}
	
	
	
	
	
	
	
}



















