<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
class Lib_authentication {
	private static $instance;
	private $databases = array();
	public $userdata = NULL;
	public $localdata = NULL;
	public $tables = array();
	protected $CI;
	protected $base_dashboard = array();
	protected $altorouter;
	public function __construct($configs = array()) {
		$this->CI = &get_instance();
		if (!isset($configs)) {
			$this->CI->load->config('dashboard/base_dashboard');
			$configs = $this->CI->config->item('base_dashboard');
		}
		$this->base_dashboard = $configs;
		$this->tables = (isset($this->base_dashboard['get_tables']) ? $this->base_dashboard['get_tables'] : array());
		// Load Database
		$this->db_dashboard = $this->CI->load->database('dashboard', TRUE);
		// Load Imzers Library
		$this->CI->load->library('dashboard/Lib_imzers', $this->base_dashboard, 'imzers');
		$this->userdata_start();
		if (isset($this->base_dashboard['altorouter']['mapping'])) {
			$this->set_altorouter_match($this->base_dashboard['altorouter']['mapping']);
		}
	}
	private static function get_instance($configs = array()) {
        if (!self::$instance) {
            self::$instance = new Lib_authentication($configs);
        }
        return self::$instance;
    }
	public function get_database($database_name) {
		$sql = "SELECT db_host, db_port, db_user, db_pass, db_name FROM dashboard_databases WHERE LOWER(database_name) = LOWER('{$database_name}')";
		$sql_query = $this->CI->imzers->db_query($sql);
		return $sql_query->fetch_assoc();
	}
	function userdata_start() {
		if ($this->CI->session->userdata('social_account_login_seq') != FALSE) {
			$this->userdata = $this->get_login_data($this->CI->session->userdata('social_account_login_seq'));
			if (isset($this->userdata->local_seq)) {
				$this->localdata = $this->get_login_data_userdata($this->userdata->local_seq);
			}
		}
	}
	private function set_altorouter_match($mapping = null) {
		if (!isset($mapping)) {
			if (isset($this->base_dashboard['altorouter']['mapping'])) {
				$mapping = $this->base_dashboard['altorouter']['mapping'];
			}
		}
		$this->altorouter = new AltoRouter();
		if (is_array($mapping) && (count($mapping) > 0)) {
			foreach ($mapping as $val) {
				if (is_array($val) && (count($val) > 3)) {
					$this->altorouter->map($val[0], $val[1], $val[2], $val[3]);
				}
			}
		}
		return $this;
	}
	public function get_altorouter_match() {
		return $this->altorouter->match();
	}
	//------------------------------------------------------------------
	
	function get_login_data($uid, $login_server = null) {
		if (!$login_server) {
			$login_server = 'goodgames';
		}
		$loginTable = $this->tables['dashboard_account_social'];
		switch (strtolower($login_server)) {
			case 'local':
			case 'localhost':
				$loginTable = $this->tables['dashboard_account'];
			break;
			case 'goodgames':
			default:
				$loginTable = $this->tables['dashboard_account_social'];
			break;
		}
		$sql = sprintf("SELECT * FROM %s", $loginTable);
		if (is_numeric($uid)) {
			$sql .= sprintf(" WHERE seq = '%d'", $uid);
		} else if (is_string($uid)) {
			switch (strtolower($login_server)) {
				case 'goodgames':
					$sql .= sprintf(" WHERE LOWER(login_username) = '%s'", strtolower($uid));
				break;
				case 'local':
				case 'localhost':
				default:
					$sql .= sprintf(" WHERE LOWER(account_email) = '%s'", strtolower($uid));
				break;
			}
		} else {
			$sql .= " WHERE seq = 0";
		}
		$sql_query = $this->CI->imzers->db_query($sql);
		return $sql_query->fetch_object();
	}
	function get_login_data_userdata($local_seq) {
		$userdata = array();
		$local_seq = (is_numeric($local_seq) ? (int)$local_seq : 0);
		if ((int)$local_seq === 0) {
			return false;
		}
		$sql = sprintf("SELECT a.*, r.role_id, r.role_code, r.role_name FROM %s AS a LEFT JOIN %s AS r ON r.seq = a.account_role WHERE (a.seq = '%d') LIMIT 1",
			$this->tables['dashboard_account'],
			$this->tables['dashboard_account_roles'],
			$local_seq
		);
		try {
			$sql_query = $this->CI->imzers->db_query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return false;
		}
		while ($rows = $this->CI->imzers->db_fetch()) {
			$userdata = $rows;
		}
		return $userdata;
	}
	function update_social_login_account($account_seq) {
		$account_params = array(
			'login_datetime_first'			=> NULL,
		);
		$sql = sprintf("UPDATE %s SET login_datetime_last = NOW() WHERE seq = '%d'",
			$this->tables['dashboard_account_social'],
			$account_seq
		);
		$this->CI->imzers->db_query($sql);
	}
	function update_local_login_account($account_seq) {
		$sql = sprintf("UPDATE %s SET login_datetime = NOW() WHERE seq = '%d'",
			$this->tables['dashboard_account'],
			$account_seq
		);
		$this->CI->imzers->db_query($sql);
	}
	//======================
	// Users
	function get_local_user_by($by_value, $by_type = null) {
		if (!isset($by_type)) {
			$by_type = 'email';
		}
		$sql_wheres = array();
		switch (strtolower($by_type)) {
			case 'email':
			default:
				$sql_wheres['account_email'] = $by_value;
			break;
			case 'username':
				$sql_wheres['account_username'] = $by_value;
			break;
			case 'seq':
			case 'id':
				if (is_numeric($by_value)) {
					$sql_wheres['seq'] = $by_value;
				} else {
					$sql_wheres['account_username'] = $by_value;
				}
			break;
			case 'activation':
				if (is_string($by_value)) {
					$sql_wheres['account_activation_code'] = $by_value;
				}
			break;
		}
		$sql = sprintf("SELECT * FROM %s WHERE", $this->tables['dashboard_account']);
		if (count($sql_wheres) > 0) {
			$for_i = 0;
			foreach ($sql_wheres as $ke => $val) {
				if ($for_i > 0) {
					$sql .= sprintf(" AND %s = '%s'", $ke, $val);
				} else {
					$sql .= sprintf(" %s = '%s'", $ke, $val);
				}
				$for_i += 1;
			}
		}
		$sql_query = $this->CI->imzers->db_query($sql);
		return $sql_query->fetch_assoc();
	}
	//-------------------
	// Utils
	//-------------------
	function create_unique_datetime($timezone = "Asia/Bangkok") {
		$microtime = microtime(true);
		$micro = sprintf("%06d",($microtime - floor($microtime)) * 1000000);
		$DateObject = new DateTime(date("Y-m-d H:i:s.{$micro}", $microtime));
		$DateObject->setTimezone(new DateTimeZone($timezone));
		return $DateObject->format('YmdHisu');
	}
	function create_dateobject($timezone, $format, $date) {
		$microtime = microtime(true);
		$micro = sprintf("%06d",($microtime - floor($microtime)) * 1000000);
		$datetime = date('Y-m-d H:i:s', strtotime($date));
		$DateObject = new DateTime("{$datetime}.{$micro}");
		$DateObject->setTimezone(new DateTimeZone($timezone));
		$DateObject->createFromFormat($format, $date);
		return $DateObject;
	}
	
	//=============================
	//-- Email
	public function send_email($mail_type, $input_params) {
		$mail_account = array();
		$mail_type = (is_string($mail_type) ? strtolower($mail_type) : $this->email_vendor);
		$query_params = array();
		$mail_vendors = (isset($this->base_dashboard['get_email_vendors']) ? $this->base_dashboard['get_email_vendors'] : FALSE);
		$mail_account['smtp_host'] = (isset($mail_vendors[$mail_type]['smtp_host']) ? $mail_vendors[$mail_type]['smtp_host'] : '');
		$mail_account['smtp_user'] = (isset($mail_vendors[$mail_type]['smtp_user']) ? $mail_vendors[$mail_type]['smtp_user'] : '');
		$mail_account['smtp_pass'] = (isset($mail_vendors[$mail_type]['smtp_pass']) ? $mail_vendors[$mail_type]['smtp_pass'] : '');
		$mail_account['smtp_port'] = (isset($mail_vendors[$mail_type]['smtp_port']) ? $mail_vendors[$mail_type]['smtp_port'] : '');
		$input_params['sender_address'] = (isset($mail_vendors[$mail_type]['sender_address']) ? $mail_vendors[$mail_type]['sender_address'] : '');
		$input_params['sender_name'] = (isset($mail_vendors[$mail_type]['sender_name']) ? $mail_vendors[$mail_type]['sender_name'] : '');
		
		$query_params['sender_address'] = (isset($input_params['sender_address']) ? $input_params['sender_address'] : '');
		$query_params['sender_name'] = (isset($input_params['sender_name']) ? $input_params['sender_name'] : '');
		$query_params['email_address'] = (isset($input_params['account_email']) ? $input_params['account_email'] : '');
		$query_params['email_subject'] = (isset($input_params['account_action_subject']) ? $input_params['account_action_subject'] : '');
		$query_params['email_body'] = (isset($input_params['account_action_body']) ? $input_params['account_action_body'] : '');
		try {
			$ret = $this->run_send_email($mail_type, $mail_account, $query_params);
		} catch (Exception $ex) {
			$ret = $ex->getMessage();
		}
		return $ret;
	}
	private function run_send_email($mail_type, $mail_account, $input_params) {
		$config = array();
		// Mail Type Avaiable: google and mailgun
		$mail_type = (is_string($mail_type) ? strtolower($mail_type) : 'mailgun');
		$config['useragent'] = 'Native Mailer';
		$config['smtp_host'] = (isset($mail_account['smtp_host']) ? $mail_account['smtp_host'] : '');
		$config['smtp_user'] = (isset($mail_account['smtp_user']) ? $mail_account['smtp_user'] : '');
		$config['smtp_pass'] = (isset($mail_account['smtp_pass']) ? $mail_account['smtp_pass'] : '');
		$config['smtp_port'] = (isset($mail_account['smtp_port']) ? (int)$mail_account['smtp_port'] : 465);
		$config['smtp_method'] = (isset($mail_account['smtp_method']) ? $mail_account['smtp_method'] : 'ssl');
		
		
		$config['smtp_timeout'] = 5;
		$config['wordwrap'] = TRUE;
		$config['wrapchars'] = 76;
		$config['mailtype'] = 'html';
		$config['charset'] = 'utf-8';
		$config['validate'] = FALSE;
		$config['priority'] = 3;
		$config['crlf'] = "\r\n";
		$config['newline'] = "\r\n";
		$config['bcc_batch_mode'] = FALSE;
		$config['bcc_batch_size'] = 200;
		
		$input_params['email_name'] = (isset($input_params['sender_name']) ? $input_params['sender_name'] : 'Dear Hello');
		//== Load PHP Mailer
		$mail = new PHPMailer(true);
		try {
			//Server settings
			$mail->SMTPDebug = 0;
			$mail->Host = $config['smtp_host'];
			$mail->Username = $config['smtp_user'];
			$mail->Password = $config['smtp_pass'];
			$mail->Port = $config['smtp_port'];
			if ($mail_type === 'sendmail') {
				$mail->From = $input_params['sender_address'];
				$mail->FromName = $input_params['sender_name'];
			} else {
				$mail->isSMTP();
				$mail->SMTPAuth = true;
				$mail->SMTPSecure = $config['smtp_method'];
				// Disabled SMTP SSL Verify
				$mail->SMTPOptions = array(
					'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
				);
			}
			
			//Recipients
			$mail->setFrom($input_params['sender_address'], $input_params['sender_name']);
			$mail->addAddress($input_params['email_address'], $input_params['email_name']);     // Add a recipient
			#$mail->addAddress('imzers@gmail.com');               // Name is optional
			$mail->addReplyTo($input_params['sender_address'], $input_params['sender_name']);
			$mail->addCC('imzers@gmail.com');
			#$mail->addBCC('bcc@example.com');
			//Attachments
			#$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			#$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
			//Content
			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject = $input_params['email_subject'];
			$mail->Body    = $input_params['email_body'];
			$mail->AltBody = stripslashes($input_params['email_body']);
			$mail->send();
			
			$result = array(
				'result'			=> TRUE,
				'message'			=> "Email already sent",
				
			);
		} catch (Exception $e) {
			$result = array(
				'result'			=> False,
				'message'			=> $mail->ErrorInfo,
				
			);
		}
		return $result;
	}
	
}



