<?php
// -------------------------------------------------------
// Important thing for allow load Codeigniter from outside
//include('bootstrap.php');
// -------------------------------------------------------
if (!defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Auto_approve_deposit extends MY_Controller {
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	protected $base_dashboard, $base_mutasi = array();
	protected $base_suksesbugil;
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
	function get_active_banks() {
		try {
			$active_banks = $this->mod_suksesbugil->get_sb_banks(1);
		} catch (Exception $ex) {
			throw $ex;
			$active_banks = FALSE;
		}
		return $active_banks;
	}
	function running_auto_approve($bank_code = 'all') {
		$collectData = array(
			'bank_code'					=> (is_string($bank_code) ? strtolower($bank_code) : 'all'),
			'auto_approve_is_enabled'	=> FALSE,
			'collect'					=> array(),
		);
		$collectData['bank_data'] = $this->mod_mutasi->get_bank_type_by('code', $collectData['bank_code']);
		if (!isset($collectData['bank_data']->seq)) {
			$this->error = true;
			$this->error_msg[] = "Bank data not exists.";
		}
		if (!$this->error) {
			$collectData['setting_auto_approve'] = $this->mod_cli->get_setting_by_code('auto_approve');
			if (!isset($collectData['setting_auto_approve']->setting_value)) {
				$collectData['auto_approve_is_enabled'] = FALSE;
			} else {
				if (strtoupper($collectData['setting_auto_approve']->setting_value) === 'Y') {
					$collectData['auto_approve_is_enabled'] = TRUE;
				}
			}
		}
		if ($collectData['auto_approve_is_enabled'] === TRUE) {
			//===== RUNNING AUTO APPROVE
			$collectData['query_params'] = array(
				
			);
			try {
				$collectData['collect']['bank_accounts'] = $this->mod_cli->get_bank_account_by_bankseq_and_active($collectData['bank_data']->seq, 1);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get bank accounts active: {$ex->getMessage()}.";
			}
			if (!$this->error) {
				$collectData['collect']['deposit_data'] = array();
				if (is_array($collectData['collect']['bank_accounts'])) {
					if (count($collectData['collect']['bank_accounts']) > 0) {
						foreach ($collectData['collect']['bank_accounts'] as $accVal) {
							try {
								$collectData['collect']['deposit_data'][$accVal->seq] = $this->mod_cli->get_sb_deposit_data($collectData['bank_data']->seq, $accVal->seq, 'waiting', $this->DateObject->format('Y-m-d H:i:s'), $this->base_suksesbugil['cli']['auto_approve']['interval_deposit']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Cannot get deposit data with waiting status and range-date: {$ex->getMessage()}";
							}
						}
					}
				}
			}
			if (!$this->error) {
				$collectData['deposit_data_need_check'] = array();
				$collectData['bank_mutasi_transaction_data_match'] = array();
				if (count($collectData['collect']['deposit_data']) > 0) {
					foreach ($collectData['collect']['deposit_data'] as $depodataVal) {
						if (is_array($depodataVal) && (count($depodataVal) > 0)) {
							foreach ($depodataVal as $trans_data) {
								$collectData['deposit_data_need_check'][] = $trans_data;
							}
						}
					}
				}
			}
			//-------------------------
			// DEBUG
			//-------------------------
			/*
			if (!$this->error) {
				$collectData['get_mb_transaction_incoming'] = array();
				if (count($collectData['deposit_data_need_check']) > 0) {
					foreach ($collectData['deposit_data_need_check'] as $checkval) {
						$collectData['get_mb_transaction_incoming'][] = $this->mod_cli->get_mb_transaction_incoming($checkval->mutasi_bank_seq, $checkval->mutasi_bank_account_seq, $checkval->transaction_date, $checkval, 0, $this->base_suksesbugil['cli']['auto_approve']['interval_mutasi'], $collectData['bank_data']->bank_code);
					}
				}
				return $collectData['get_mb_transaction_incoming'];
			} else {
				return $this->error_msg;
			}
			*/
			//-------------------------
			if (!$this->error) {
				if (count($collectData['deposit_data_need_check']) > 0) {
					foreach ($collectData['deposit_data_need_check'] as $checkval) {
						$get_mb_transaction_incoming = $this->mod_cli->get_mb_transaction_incoming($checkval->mutasi_bank_seq, $checkval->mutasi_bank_account_seq, $checkval->transaction_date, $checkval, 0, $this->base_suksesbugil['cli']['auto_approve']['interval_mutasi'], $collectData['bank_data']->bank_code);
						if (isset($get_mb_transaction_incoming->seq)) {
							$collectData['bank_mutasi_transaction_data_match'][$checkval->seq] = array(
								'suksesbugil'	=> $checkval,
								'mutasi'		=> $get_mb_transaction_incoming,
							);
						}
						// If more than (interval-day) then delete
						$sb_trans_transaction_date = new DateTime($checkval->transaction_datetime);
						switch (strtolower($this->base_suksesbugil['cli']['auto_approve']['interval_delete']['unit'])) {
							case 'day':
								$sb_trans_transaction_date->add(new DateInterval("P{$this->base_suksesbugil['cli']['auto_approve']['interval_delete']['amount']}D"));
							break;
							case 'hour':
								$sb_trans_transaction_date->add(new DateInterval("PT{$this->base_suksesbugil['cli']['auto_approve']['interval_delete']['amount']}H"));
							break;
							case 'minute':
							default:
								$sb_trans_transaction_date->add(new DateInterval("PT{$this->base_suksesbugil['cli']['auto_approve']['interval_delete']['amount']}M"));
							break;
						}
						if ($sb_trans_transaction_date->format('Y-m-d H:i:s') < $this->DateObject->format('Y-m-d H:i:s')) {
							if (strtolower($checkval->auto_approve_status) !== 'failed') {
								$sb_trans_delete_params = array(
									'auto_approve_status'		=> 'deleted',
								);
								$this->mod_cli->update_sb_transaction($checkval->seq, $sb_trans_delete_params);
							}
						}
					}
				}
			}
			if (!$this->error) {
				// Set data-match
				$collectData['bank_mutasi_transaction_approved_data'] = array();
				if (count($collectData['bank_mutasi_transaction_data_match']) > 0) {
					try {
						$collectData['bank_mutasi_transaction_approved_data'] = $this->mod_cli->match_autoapprove_by_curl('approve', $collectData['bank_data']->bank_code, $collectData['bank_mutasi_transaction_data_match']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Matching auto approve return exception error with message: {$ex->getMessage()}.";
					}
				}
			}
			if (!$this->error) {
				if (count($collectData['bank_mutasi_transaction_approved_data']) > 0) {
					foreach ($collectData['bank_mutasi_transaction_approved_data'] as $approveKey => $approveVal) {
						$this->mod_cli->insert_approve_log($approveKey, $approveVal);
					}
				}
			}
			
			
			
			
			//--------------------------
		}
		
		
		return $collectData;
	}
	//==================================================================================================
	function update_deposit_transaction_by_cli($bank_code = 'bca') {
		$collectData = array(
			'collect'			=> array(),
			'bank_code'			=> (is_string($bank_code) ? strtolower($bank_code) : 'bca'),
		);
		$collectData['collect']['insert_transaction_seqs'] = array();
		try {
			$collectData['transaction'] = $this->mod_suksesbugil->get_data_suksesbugil_by('bank', $collectData['bank_code']);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		if (isset($collectData['transaction']['raw_transactions'])) {
			$collectData['parse'] = $this->mod_suksesbugil->parse($collectData['transaction']['raw_transactions']);
			$collectData['trans'] = $this->mod_suksesbugil->generate_suksesbugil_transaction_data($collectData['parse']);
			if (count($collectData['trans']) > 0) {
				foreach ($collectData['trans'] as $val) {
					$collectData['query_params'] = array(
						'transaction_amount' => $val['transaction_amount'],
						'transaction_from_acc_rekening' => $val['transaction_from_acc_rekening'],
						'transaction_from_acc_bank' => $val['transaction_from_acc_bank'],
						'transaction_sb_unique_identifier' => $val['transaction_sb_unique_identifier'],
					);
					$collectData['sb_transaction_seq'] = 0;
					try {
						$collectData['sb_transaction_data'] = $this->mod_suksesbugil->get_suksesbugil_transaction_item_by_bankaccount_and_date($val['mutasi_bank_seq'], $val['mutasi_bank_account_seq'], $val['transaction_date'], $collectData['query_params']);
					} catch (Exception $ex) {
						throw $ex;
						$collectData['sb_transaction_data'] = FALSE;
					}
					if ($collectData['sb_transaction_data'] != FALSE) {
						if (isset($collectData['sb_transaction_data']->value)) {
							$collectData['sb_transaction_seq'] = $collectData['sb_transaction_data']->value;
						}
					}
					if ((int)$collectData['sb_transaction_seq'] === 0) {
						try {
							$collectData['new_insert_transaction_seq'] = $this->mod_suksesbugil->insert_suksesbugil_transactions($val);
						} catch (Exception $ex) {
							throw $ex;
							$collectData['new_insert_transaction_seq'] = 0;
						}
					} else {
						$collectData['new_insert_transaction_seq'] = 0;
					}
					if ($collectData['new_insert_transaction_seq'] > 0) {
						$collectData['collect']['insert_transaction_seqs'][] = $collectData['new_insert_transaction_seq'];
					}
				}
			}
			$count_seqs = count($collectData['collect']['insert_transaction_seqs']);
		} else {
			$count_seqs = -1;
		}
		return $count_seqs;
	}
	
	
	
	//----------------------------------------------------------------------------
	function insert_auto_approve_log($instance = 'bank: bca') {
		$query_params = array(
			'running_type'				=> 'suksesbugil',
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
	public function run_suksesbugil() {
		$active_banks = $this->get_active_banks();
		$new_auto_approve_log_seq = $this->insert_auto_approve_log('sb: goltogel');
		//--
		// Update Suksesbugil data
		if (is_array($active_banks) && (count($active_banks) > 0)) {
			foreach ($active_banks as $bank) {
				echo "\r\n-- Update Deposit Transaction of [{$bank->bank_codename}] --\r\n";
				$count_insert = $this->update_deposit_transaction_by_cli($bank->bank_code);
				echo "\r\n -- DONE for {$bank->bank_codename} : [{$count_insert} Insert] --\r\n";
			}
		}
		// Auto Approve
		$running_auto_approve = array();
		if (is_array($active_banks) && (count($active_banks) > 0)) {
			foreach ($active_banks as $keval) {
				$running_auto_approve[$keval->bank_code] = $this->running_auto_approve($keval->bank_code);
			}
		}
		print_r($running_auto_approve);
		$this->update_auto_approve_log($new_auto_approve_log_seq);
	}
}











