<?php
// -------------------------------------------------------
// Important thing for allow load Codeigniter from outside
//include('bootstrap.php');
// -------------------------------------------------------
if (!defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Mutasi_update_scheduler extends MY_Controller {
	public $is_editor = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	protected $base_dashboard, $base_cryptocurrency = array();
	protected $insert_to_enabled_data_params = array();
	function __construct() {
		parent::__construct();
		if (!is_cli()) {
			exit('Only command line access');
		}
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
		# Load mutasi config
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		# Load Model Mutasi
		$this->load->model('mutasi/Model_mutasi', 'mod_mutasi');
		# Load Cli Update Model
		$this->load->model('mutasi/Cli_mutasi_update_scheduler', 'mod_cli');
		# Set Bank Active Time
		$this->set_bank_active_datetime();
	}
	private function set_bank_active_datetime() {
		$this->base_mutasi['banks_active_time'] = $this->mod_mutasi->get_bank_active_datetime($this->base_mutasi['banks']);
	}
	
	//==================================================================================
	// Daily Transaction Update !== Hurray!
	//==================================================================================
	# Live Fetch from Bank
	function update_transaction_daily($account_seq = 0) {
		$collectData = array(
			'page'					=> 'cli-mutasi-transaction',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		$collectData['transaction_date_default'] = array(
			'starting'			=> date('Y-m-d'),
			'stopping'			=> date('Y-m-d'),
		);
		$collectData['transaction_date_post'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : $collectData['transaction_date_default']);
		$collectData['date_stopping_min'] =  $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		$collectData['date_stopping_min']->sub(new DateInterval("P30D")); // Minimum date-stopping
		//=================================
		if (!$this->error) {
			$collectData['collect']['account_bank_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			if (!isset($collectData['collect']['account_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account bank data not exists on database.";
			}
		}
		//============= Check Time Active
		if (!$this->error) {
			$bank_code = $collectData['collect']['account_bank_data']->bank_code;
			$collectData['between_datetime'] = array(
				'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', $this->DateObject->format('Y-m-d') . ' ' . $this->base_mutasi['banks_active_time'][$bank_code]['starting']),
				'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', $this->DateObject->format('Y-m-d') . ' ' . $this->base_mutasi['banks_active_time'][$bank_code]['stopping']),
			);
			if ($this->mod_mutasi->is_datetime_between_range($this->DateObject, $collectData['between_datetime']['starting'], $collectData['between_datetime']['stopping']) !== TRUE) {
				$this->error = true;
				$this->error_msg[] = "Active bank schedule is from {$this->base_mutasi['banks_active_time'][$bank_code]['starting']} to {$this->base_mutasi['banks_active_time'][$bank_code]['stopping']}";
				$this->error_msg[] = json_encode($collectData['between_datetime'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			}
		}
		//=============
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if (strtotime($collectData['transaction_date_post']['stopping']) > 0) {
					try {
						$collectData['date_stopping_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['stopping']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-stopping: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping should be in YYYY-MM-DD format";
				}
				if (strtotime($collectData['transaction_date_post']['starting']) > 0) {
					try {
						$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['starting']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-starting: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting should be in YYYY-MM-DD format";
				}	
			} else {
				$collectData['transaction_date'] = array(
					'stopping'			=> date('Y-m-d'),
				);
				$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']);
				# ---- reduce date
				$reduce_date_interval = $this->base_mutasi['interval_daterange'][$bank_code];
				switch (strtolower($reduce_date_interval['unit'])) {
					case 'hour':
						$collectData['date_starting_object']->sub(new DateInterval("PT{$reduce_date_interval['amount']}H"));
					break;
					case 'day':
					default:
						$collectData['date_starting_object']->sub(new DateInterval("P{$reduce_date_interval['amount']}D"));
					break;
				}
				$collectData['transaction_date']['starting'] = $collectData['date_starting_object']->format('Y-m-d');
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				# Date Stopping
				if ($collectData['date_stopping_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping must be today or lower of this day.";
				} else {
					if ($collectData['date_stopping_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-stopping minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
				# Date Starting
				if ($collectData['date_starting_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be today or lower of this day.";
				} else {
					if ($collectData['date_starting_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-starting minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if ($collectData['date_starting_object']->format('Y-m-d') > $collectData['date_stopping_object']->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be lower or equal than date-stopping.";
				} else {
					$collectData['transaction_date'] = array(
						'stopping'			=> $collectData['date_stopping_object']->format('Y-m-d'),
						'starting'			=> $collectData['date_starting_object']->format('Y-m-d'),
					);
				}
			}
		}
		if (!$this->error) {
			try {
				$collectData['collect']['transaction_date'] = array(
					'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['starting']),
					'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']),
				);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot create dateobject of transaction-date both start and end.";
			}
		}
		if (!$this->error) {
			$collectData['input_params'] = array(
				'date'		=> array(
					'starting'		=> $collectData['collect']['transaction_date']['starting'],
					'stopping'		=> $collectData['collect']['transaction_date']['stopping'],
				),
			);
			$collectData['collect']['transaction_date_yesterday'] = array(
				'stopping'			=> $collectData['collect']['transaction_date']['starting'],
			);
			$collectData['collect']['transaction_date_yesterday']['starting'] = new DateTime($collectData['collect']['transaction_date']['starting']->format('Y-m-d'));
			switch (strtolower($this->base_mutasi['interval_daterange'][$bank_code]['unit'])) {
				case 'hour':
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("PT{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}H"));
				break;
				case 'day':
				default:
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("P{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}D"));
				break;
			}
		}
		
		//==== GET MUTASI
		if (!$this->error) {
			try {
				$collectData['collect']['transactions_data'] = $this->mod_mutasi->get_rekening_transaction_by('seq', $collectData['collect']['account_bank_data']->seq, $collectData['input_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get data transaction mutasi from mod-mutasi: {$ex->getMessage()}";
			}
		}
		if (!isset($collectData['collect']['account_bank_data']->bank_code)) {
			$this->error = true;
			$this->error_msg[] = "Bank code should be exists from collect of account-bank-data.";
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['transactions_data']['items'])) {
				$this->error = true;
				$this->error_msg[] = "No items of mutasi transaction-data by today.";
				$this->error_msg[] = $collectData['collect']['transactions_data'];
			} else {
				if (!is_array($collectData['collect']['transactions_data']['items'])) {
					$this->error = true;
					$this->error_msg[] = "Transaction item data not in array datatype.";
				}
			}
		}
		if (!$this->error) {
			$collectData['collect_count_items'] = array(
				'fetch'				=> count($collectData['collect']['transactions_data']['items']),
				'fetched_items'		=> $collectData['collect']['transactions_data']['items'],
			);
			try {
				$collectData['collect_count_items']['database_today'] = $this->mod_mutasi->get_count_items_by_seq_with_insertdate($collectData['collect']['account_bank_data']->seq, $collectData['collect']['transaction_date']);
				$collectData['collect_count_items']['database_yesterday'] = $this->mod_mutasi->get_count_items_by_seq_with_insertdate($collectData['collect']['account_bank_data']->seq, $collectData['collect']['transaction_date_yesterday']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot count inserted data fetched to database_today.";
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect_count_items']['database_today']->value)) {
				$this->error = true;
				$this->error_msg[] = "No value count from database_today insert fetch.";
			}
		}
		//------------------
		if (!$this->error) {
			$collectData['check_data_of_db'] = array();
			$collectData['insert_data_to_db'] = array();
			$mutasi_i = 0;
			switch (strtolower($collectData['collect']['account_bank_data']->bank_code)) {
				case 'bni':
					$collectData['collect_count_items']['diff'] = ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_today']->value);
					$mutasi_i = 0;
					foreach ($collectData['collect']['transactions_data']['items'] as $transitemVal) {
						if (isset($transitemVal['transaction_date'])) {
							if (is_string($transitemVal['transaction_date']) || is_numeric($transitemVal['transaction_date'])) {
								$transaction_date = trim($transitemVal['transaction_date']);
								try {
									$transaction_date_object = DateTime::createFromFormat('d/m/Y', "{$transitemVal['transaction_date']}/{$this->DateObject->format('Y')}");
								} catch (Exception $ex) {
									throw $ex;
									$transaction_date_object = FALSE;
								}
								if ($transaction_date_object != FALSE) {
									if ($transaction_date_object->format('Y-m-d') === $this->DateObject->format('Y-m-d')) {
										$collectData['insert_data_to_db'][$mutasi_i] = $transitemVal;
									}
								}
							}
						}
						$mutasi_i += 1;
					}
				break;
				case 'mandiri':
				case 'bri':
				case 'bca':
				default:
					$collectData['collect_count_items']['diff'] = ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_today']->value);
					$mutasi_i = 0;
					foreach ($collectData['collect']['transactions_data']['items'] as $transitemVal) {
						if (isset($transitemVal['transaction_date'])) {
							if (is_string($transitemVal['transaction_date']) || is_numeric($transitemVal['transaction_date'])) {
								$transaction_date = trim($transitemVal['transaction_date']);
								try {
									$transaction_date_object = DateTime::createFromFormat('d/m/Y', "{$transitemVal['transaction_date']}/{$this->DateObject->format('Y')}");
								} catch (Exception $ex) {
									throw $ex;
									$transaction_date_object = FALSE;
								}
								if ($transaction_date_object != FALSE) {
									if ($transaction_date_object->format('Y-m-d') === $this->DateObject->format('Y-m-d')) {
										$collectData['insert_data_to_db'][$mutasi_i] = $transitemVal;
									}
								}
							}
						}
						$mutasi_i += 1;
					}
				break;
			}
			$collectData['collect_count_items']['allow_insert'] = count($collectData['insert_data_to_db']);
		}
		//==============
		// Debug
		/*
		if (!$this->error) {
			print_r($collectData);
			
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		//======================================================================
		
		if (!$this->error) {
			$collectData['allow_insert'] = FALSE;
			switch (strtolower($collectData['collect']['account_bank_data']->bank_code)) {
				case 'bni':
					$collectData['allow_insert'] = TRUE;
					$collectData['difference_between_count'] = array(
						'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
						'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
					);
				break;
				case 'mandiri':
				case 'bri':
					if ($collectData['collect_count_items']['fetch'] >= $collectData['collect_count_items']['database_today']->value) {
						$collectData['allow_insert'] = TRUE;
						$collectData['difference_between_count'] = array(
							'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
							'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
						);
					}
				break;
				case 'bca':
				default:
					if ($collectData['collect_count_items']['fetch'] >= $collectData['collect_count_items']['database_today']->value) {
						$collectData['allow_insert'] = TRUE;
						$collectData['difference_between_count'] = array(
							'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
							'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
						);
					}
				break;
			}
		}
		//-----------------------------------------
		//-----------------------------------------
		if (!$this->error) {
			if ($collectData['allow_insert'] === TRUE) {
				$insert_i = 0;
				foreach ($collectData['insert_data_to_db'] as $insertKey => $insertVal) {
					$collectData['check_data_of_db'][] = $this->mod_mutasi->insert_transaction_fetch_by('account_seq', $collectData['collect']['account_bank_data']->seq, $collectData['collect']['account_bank_data'], $insertVal, $collectData['collect']['transaction_date'], $insertKey, $insert_i);
					$insert_i++;
				}
			}
		}
		//-----------------------------------------
		//-----------------------------------------
		// Show Collectdata or error
		if (!$this->error) {
			return array(
				'insert_data_to_db'			=> $collectData['insert_data_to_db'],
				'check_data_of_db'			=> $collectData['check_data_of_db'],
				'collect_count_items'		=> $collectData['collect_count_items'],
				'difference_between_count'	=> $collectData['difference_between_count'],
				'allow_insert'				=> $collectData['allow_insert'],
			);
		} else {
			return ($this->error_msg);
		}
	}
	
	function get_bank_account_active() {
		return $this->mod_cli->get_bank_account_with_status(1);
	}
	# Select Active Bank To Exceute
	function get_bank_account_with_bankcode_status($bank_code, $is_active) {
		return $this->mod_cli->get_bank_account_with_bankcode_status($bank_code, $is_active);
	}
	//----------------------------------------------------------------------------
	function insert_auto_approve_log($instance = 'bank: bca') {
		$query_params = array(
			'running_type'				=> 'mutasi',
			'running_instance'			=> $instance,
			'running_datetime_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
			'running_datetime_stopping'	=> $this->DateObject->format('Y-m-d H:i:s'),
		);
		return $this->mod_cli->insert_auto_approve_log($query_params);
	}
	function update_auto_approve_log($log_seq) {
		$this->mod_cli->update_auto_approve_log($log_seq);
	}
	
	//---------------------- Running from inside
	public function run_mutasi($bank_code, $is_active) {
		$bank_accounts = $this->get_bank_account_with_bankcode_status($bank_code, $is_active);
		$new_auto_approve_log_seq = $this->insert_auto_approve_log("bank: {$bank_code}");
		try {
			$rows = $bank_accounts->result();
		} catch (Exception $ex) {
			exit("Error exception: " . $ex->getMessage());
		}
		if (is_array($rows)) {
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					echo "\r\n-- Update Transaction Daily of [{$row->account_title}: {$row->rekening_number}] --\r\n";
					$running_mutasi = $this->update_transaction_daily($row->seq);
					print_r($running_mutasi);
					echo "\r\n -- DONE --\r\n";
				}
			}
		}
		$this->update_auto_approve_log($new_auto_approve_log_seq);
	}
}



//=== Start Object
//$Mutasi_update_scheduler = new Mutasi_update_scheduler();
//=========================================
// Create Args
/*
$args = array(
	'bank'			=> (isset($argv[1]) ? $argv[1] : ''),
	'is_active'		=> (isset($argv[2]) ? $argv[2] : 0),
);
print_r($args);
exit;
*/
//=========================================
//$bank_accounts = $Mutasi_update_scheduler->get_bank_account_active();
//$bank_accounts = $Mutasi_update_scheduler->get_bank_account_with_bankcode_status('bca', 1);
//print_r($bank_accounts->result());

/*
$new_auto_approve_log_seq = $Mutasi_update_scheduler->insert_auto_approve_log();
try {
	$rows = $bank_accounts->result();
} catch (Exception $ex) {
	exit("Error exception: " . $ex->getMessage());
}
if (is_array($rows)) {
	if (count($rows) > 0) {
		foreach ($rows as $row) {
			echo "\r\n-- Update Transaction Daily of [{$row->account_title}: {$row->rekening_number}] --\r\n";
			$running_mutasi = $Mutasi_update_scheduler->update_transaction_daily($row->seq);
			print_r($running_mutasi);
			echo "\r\n -- DONE --\r\n";
		}
	}
}
$Mutasi_update_scheduler->update_auto_approve_log($new_auto_approve_log_seq);
*/








