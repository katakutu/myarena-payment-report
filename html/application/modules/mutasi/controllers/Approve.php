<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Approve extends MY_Controller {
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
		
		# Load Models
		$this->load->model('mutasi/Model_mutasi', 'mod_mutasi');
		$this->load->model('mutasi/Model_suksesbugil', 'mod_suksesbugil');
		//--
		$this->load->model('mutasi/Cli_mutasi_update_scheduler', 'mod_cli');
		# Load mutasi config
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		$this->load->config('mutasi/base_suksesbugil');
		$this->base_suksesbugil = $this->config->item('base_suksesbugil');
	}
	
	
	
	
	function index($trans_seq) {
		$collectData = array();
		$collectData['bank_data'] = $this->mod_mutasi->get_bank_type_by('code', 'bca');
		$collectData['deposit_data_need_check'] = array();
		for ($fi = 34; $fi < 42; $fi++) {
			$collectData['deposit_data_need_check'][$fi] = $this->mod_suksesbugil->get_sb_trans_single_by('seq', $fi);
		}

		$collectData['bank_mutasi_transaction_data_match'] = array();
		
		if (count($collectData['deposit_data_need_check']) > 0) {
			foreach ($collectData['deposit_data_need_check'] as $checkval) {
				$bank_mutasi_transaction_data_match = $this->mod_cli->get_mb_transaction_incoming($checkval->mutasi_bank_seq, $checkval->mutasi_bank_account_seq, $checkval->transaction_date, $checkval, 0, $this->base_suksesbugil['cli']['auto_approve']['interval_mutasi']);
				if (isset($bank_mutasi_transaction_data_match->seq)) {
					$collectData['bank_mutasi_transaction_data_match'][$checkval->seq] = array(
						'suksesbugil'	=> $checkval,
						'mutasi'		=> $bank_mutasi_transaction_data_match,
					);
				}
			}
		}
		if (!$this->error) {
			// Set data-match
			$collectData['bank_mutasi_transaction_approved_data'] = array();
			if (count($collectData['bank_mutasi_transaction_data_match']) > 0) {
				try {
					$collectData['bank_mutasi_transaction_approved_data'] = $this->mod_cli->execute_matching_autoapprove_by_curl('approve', $collectData['bank_data']->bank_code, $collectData['bank_mutasi_transaction_data_match']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Matching auto approve return exception error with message: {$ex->getMessage()}.";
				}
			}
		}
		
		if (!$this->error) {
			print_r($collectData);
		} else {
			print_r($this->error_msg);
		}
	}
	
	function parsing($type = 'already') {
		$collectData = array(
			'collect'		=> array(),
		);
		$collectData['html_string'] = '';
		switch (strtolower($type)) {
			case 'tdkproses':
				$collectData['html_string'] = $this->sampletdkterproses();
			break;
			case 'login':
				$collectData['html_string'] = $this->samplelogin();
			break;
			case 'already':
			default:
				$collectData['html_string'] = $this->samplealready();
			break;
		}
		
		
		// DOM Process
		$collectData['dom_table_data'] = array();
		if (strlen($collectData['html_string']) > 0) {
			libxml_use_internal_errors(true);
			$collectData['collect']['dom'] = new DOMDocument;
			$collectData['collect']['dom']->preserveWhiteSpace = false;
			$collectData['collect']['dom']->validateOnParse = false;
			$collectData['collect']['dom']->loadHTML($collectData['html_string']);
			$collectData['collect']['xpath'] = new DOMXPath($collectData['collect']['dom']);
			$collectData['collect']['queries'] = array(
				'form'		=> $collectData['collect']['xpath']->query('//input[@name="entered_login"]'),
			);
			if ($collectData['collect']['queries']['form']->length === 0) {
				if ($collectData['collect']['dom']->hasChildNodes()) {
					foreach ($collectData['collect']['dom']->childNodes as $item) {
						$collectData['dom_table_data'][] = array(
							'nodepath'		=> $item->getNodePath(),
							'nodevalue'		=> trim(strip_tags($item->nodeValue)),
							'nodename'		=> $item->nodeName,
							'parentpath'	=> $item->parentNode->getNodePath(),
						);
					}
				}
			}
			
		}
		
		
		
		
		
		
		print_r($collectData);
	}
	
	
	
	function samplealready() {
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
Data already approved by wohaauji
HTML;
		return $html;
	}
	function sampletdkterproses() {
		$tdk_terproses = <<<HTML
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
Tidak ada transaksi terproses, silahkan tekan CTRL+F5 untuk mengrefresh halaman!
HTML;
		
	
	
		return $tdk_terproses;
	}
	function samplelogin() {
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
<html>
    <head>
        <title>Monaco</title>
        <SCRIPT LANGUAGE="JavaScript">
            <!--
//  ------ check form ------
function checkData() {
  var f1 = document.forms[0];
  var wm = "Please complete below fields\n\r\n";
  var noerror = 1;

  // --- entered_login ---
  var t1 = f1.entered_login;
  if (t1.value == "" || t1.value == " ") {
    wm += "Login\r\n";
    noerror = 0;
  }

  // --- entered_password ---
  var t1 = f1.entered_password;
  if (t1.value == "" || t1.value == " ") {
    wm += "Password\r\n";
    noerror = 0;
  }

  // --- check if errors occurred ---
  if (noerror == 0) {
    alert(wm);
    return false;
  }
  else return true;
}

//-->

        </SCRIPT>
        <script type="text/javascript" src="images/vbulletin_md5.js"></script>
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
        <style type="text/css">
body{margin:0;padding:0;}
.wrapper{
  font-family: 'Lato', sans-serif;
  background-image: url(../images/idntoto/log-bg.jpg);
    height: 100%;
    color: #fff;
    width: 100%;
    position: absolute;
}

.form-wrapper {
  background-image: url(../images/idntoto/form-bg.jpg);
    width: 362px;
    height: 407px;
    display: block;
    margin: 170px auto;
    border-radius: 23px;
}

.logo {
  width: 100%;
    display: block;
    padding-top: 60px;
    margin-bottom: 10px;
}

.logo img {
  display: block;
    margin: auto;
}

.form {
  margin-top: 60px;
}

.form-rows {
  margin: 3px 0;
}
.form-rows img {
  margin-left: 20px;
}

.form-rows input[type="text"],
.form-rows input[type="password"] {
  background-color: #545766;
    border: none;
    padding: 6px 10px;
    margin-left: 20px;
    color: #fff;
    width: 176px;
}

.labels {
  text-transform: uppercase;
    font-size: 12px;
    display: inline-block;
    margin-left: 22px;
    width: 45px;
}

.divide {
  display: inline-block;
    width: 67px;
    text-align: right;
}

.submit-button {
  display: block;
    margin: auto;
    padding: 9px 26px;
    cursor: pointer;
    border-radius: 6px;
    border: 1px solid #3176e7;
    color: #fff;
    margin-top: 23px;
    background: rgba(46,86,151,1);
  background: -moz-linear-gradient(top, rgba(46,86,151,1) 0%, rgba(24,55,121,1) 50%, rgba(3,26,96,1) 51%, rgba(59,89,171,1) 100%);
  background: -webkit-gradient(left top, left bottom, color-stop(0%, rgba(46,86,151,1)), color-stop(50%, rgba(24,55,121,1)), color-stop(51%, rgba(3,26,96,1)), color-stop(100%, rgba(59,89,171,1)));
  background: -webkit-linear-gradient(top, rgba(46,86,151,1) 0%, rgba(24,55,121,1) 50%, rgba(3,26,96,1) 51%, rgba(59,89,171,1) 100%);
  background: -o-linear-gradient(top, rgba(46,86,151,1) 0%, rgba(24,55,121,1) 50%, rgba(3,26,96,1) 51%, rgba(59,89,171,1) 100%);
  background: -ms-linear-gradient(top, rgba(46,86,151,1) 0%, rgba(24,55,121,1) 50%, rgba(3,26,96,1) 51%, rgba(59,89,171,1) 100%);
  background: linear-gradient(to bottom, rgba(46,86,151,1) 0%, rgba(24,55,121,1) 50%, rgba(3,26,96,1) 51%, rgba(59,89,171,1) 100%);
  filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#2e5697', endColorstr='#3b59ab', GradientType=0 );
}

.submit-button:focus {
  outline: none !important;
}

.wl {
  display: inline-block;
    float: right;
    position: relative;
    right: 59px;
    font-style: italic;
    top: 4px;
    color: #ccc;
}
</style>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

    </head>
    <body>
        <!-- <div class="login"><div class="login-header"><img src="../images/idntoto/logo.png" />
        </div><div class="login-body" align="center"><form action='' METHOD="post" onsubmit="md5hash(entered_password, vb_login_md5password, vb_login_md5password_utf, 0)"><table cellpadding="0" cellspacing="0" border="0"><tr><td width="65">Username</td><td width="20">:</td><td><input name="entered_login" tabindex="1" type="text" size="8" style="width: 227px;height:28px;color:#747474;font-size:16px;" autocomplete=OFF onKeypress="if (event.keyCode < 48 || event.keyCode > 57 && event.keyCode < 65 || event.keyCode > 90 && event.keyCode < 97 || event.keyCode > 122 || event.keyCode == 13) { event.returnValue = false; } if (event.keyCode == 8) {chkval(document.form1,this);}"> </td>
                </tr><tr><td height="10"></td></tr><tr><td>Password</td><td>:</td><td><input name="entered_password" type="password" tabindex="2" style="width: 227px;height:28px;color:#747474;font-size:16px;" size="8" autocomplete=OFF></td>
              </tr>
            </table><div class="submit-btn"><input type="submit" class="btn btn-theme" value="LOGIN">
                </div><input name="vb_login_md5password" type="hidden"><input name="vb_login_md5password_utf" type="hidden">
            </form>
        </div>
    </div> -->
<div class="wrapper">
    <div class="form-wrapper">
        <div class="logo">
            <img src="../images/idntoto/logo.png">
            <span class="wl">whitelabel</span>
        </div>
        <div class="form">
            <form action='' METHOD="post" onsubmit="md5hash(entered_password, vb_login_md5password, vb_login_md5password_utf, 0)">
                <div class="form-rows">
                    <span class="labels">username</span>
                    <span class="divide">:</span>
                    <input name="entered_login" tabindex="1" type="text" size="8" autocomplete=OFF onKeypress="if (event.keyCode < 48 || event.keyCode > 57 && event.keyCode < 65 || event.keyCode > 90 && event.keyCode < 97 || event.keyCode > 122 || event.keyCode == 13) { event.returnValue = false; } if (event.keyCode == 8) {chkval(document.form1,this);}">
            
                </div>
                <div class="form-rows">
                    <span class="labels">password</span>
                    <span class="divide">:</span>
                    <input name="entered_password" type="password" tabindex="2" size="8" autocomplete=OFF>
            
                </div>
                <input type="submit" class="submit-button" value="LOGIN">
                <input name="vb_login_md5password" type="hidden">
                <input name="vb_login_md5password_utf" type="hidden">
          
            </form>
        </div>
    </div>
</div>
</body>
</html>
HTML;
		return $html;
	}
	
	
	
	
	
	
	
	
	
}



















