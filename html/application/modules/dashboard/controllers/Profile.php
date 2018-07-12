<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {
	public $is_admin = FALSE;
	public $is_merchant = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	function __construct() {
		parent::__construct();
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->load->helper('dashboard/dashboard_functions');
		$this->load->library('dashboard/Lib_imzers', $this->base_dashboard, 'imzers');
		$this->load->library('dashboard/Lib_imzcustom', FALSE, 'imzcustom');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->load->model('dashboard/Model_account', 'mod_account');
		$this->load->model('dashboard/Model_users', 'mod_users');
		$this->DateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		
		if (($this->authentication->userdata != FALSE) && ($this->authentication->localdata != FALSE)) {
			if (in_array($this->authentication->localdata['account_role'], $this->base_dashboard['admin_role'])) {
				$this->is_admin = TRUE;
			}
			if (!$this->is_admin) {
				if (in_array($this->authentication->localdata['account_role'], $this->base_dashboard['merchant_role'])) {
					$this->is_merchant = TRUE;
				}
			}
		}
	}
	function index() {
		$this->view('view');
	}
	function view($page = 'view') {
		$collectData = array();
		$collectData['page'] = 'profile-view';
		$collectData['title'] = 'View Profile: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		$collectData['action_page'] = (isset($page) ? $page : 'view');
		if (!$this->error) {
			$collectData['action_page'] = (is_string($collectData['action_page']) ? $collectData['action_page'] : 'view');
		}
		//----------------------------------------------------------------------------------------------------------
		if (!$this->error) {
			$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($this->authentication->localdata['seq'], 'seq');
			$collectData['collect']['local-properties'] = $this->mod_account->get_local_user_properties($this->authentication->localdata['seq']);
			$collectData['collect']['user-properties'] = array();
			if (count($collectData['collect']['local-properties']) > 0) {
				foreach ($collectData['collect']['local-properties'] as $keval) {
					$collectData['collect']['user-properties'][$keval->properties_key] = $keval->properties_value;
				}
			}
			$collectData['address_params'] = array(
				'country_code'		=> '360',
				'province_code'		=> (isset($collectData['collect']['user-properties']['user_address_province']) ? $collectData['collect']['user-properties']['user_address_province'] : ''),
				'city_code'			=> (isset($collectData['collect']['user-properties']['user_address_city']) ? $collectData['collect']['user-properties']['user_address_city'] : ''),
				'district_code'		=> (isset($collectData['collect']['user-properties']['user_address_district']) ? $collectData['collect']['user-properties']['user_address_district'] : ''),
				'area_code'			=> (isset($collectData['collect']['user-properties']['user_address_area']) ? $collectData['collect']['user-properties']['user_address_area'] : ''),
			);
			$collectData['collect']['address-province'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_province($collectData['address_params']) : $this->mod_account->get_province(360));
			$collectData['collect']['address-city'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_city($collectData['address_params']) : array());
			$collectData['collect']['address-district'] = ((strlen($collectData['address_params']['city_code']) > 0) ? $this->mod_account->get_district($collectData['address_params']) : array());
			$collectData['collect']['address-area'] = ((strlen($collectData['address_params']['district_code']) > 0) ? $this->mod_account->get_area($collectData['address_params']) : array());
			
			$collectData['collect']['address-values'] = array();
				
			if (count($collectData['collect']['address-province']) > 0) {
				foreach ($collectData['collect']['address-province'] as $keval) {
					if ((int)$keval->province_code === (int)$collectData['address_params']['province_code']) {
						$collectData['collect']['address-values']['province'] = $keval->province_name;
					}
				}
			}
			if (count($collectData['collect']['address-city']) > 0) {
				foreach ($collectData['collect']['address-city'] as $keval) {
					if ((int)$keval->city_code === (int)$collectData['address_params']['city_code']) {
						$collectData['collect']['address-values']['city'] = $keval->city_name;
					}
				}
			}
			if (count($collectData['collect']['address-district']) > 0) {
				foreach ($collectData['collect']['address-district'] as $keval) {
					if ((int)$keval->district_code === (int)$collectData['address_params']['district_code']) {
						$collectData['collect']['address-values']['district'] = $keval->district_name;
					}
				}
			}
			if (count($collectData['collect']['address-area']) > 0) {
				foreach ($collectData['collect']['address-area'] as $keval) {
					if ($keval->area_name === $collectData['address_params']['area_code']) {
						$collectData['collect']['address-values']['area'] = $keval->area_name;
					}
				}
			}
		}
		
		//---- Load View
		/*
		if (isset($collectData['collect']['localuser']->account_role)) {
			if ((int)$collectData['collect']['localuser']->account_role === base_config('super_admin_role')) {
				$this->error = true;
				$this->error_msg[] = "For demo purpose, Superadmin cannot editing their profile";
			}
		}
		*/
		if (!$this->error) {
			switch (strtolower($collectData['action_page'])) {
				case 'edit':
					$collectData['page'] = 'profile-edit';
					if (!$this->error) {
						if (isset($collectData['collect']['localuser']->account_email)) {
							$collectData['title'] .= $collectData['collect']['localuser']->account_email;
						}
					
						$this->load->view('dashboard/dashboard.php', $collectData);
					}
				break;
				case 'view':
				default:
					$collectData['page'] = 'profile-view';
					if (!$this->error) {
						if (isset($collectData['collect']['localuser']->account_email)) {
							$collectData['title'] .= $collectData['collect']['localuser']->account_email;
						}
					
						$this->load->view('dashboard/dashboard.php', $collectData);
					}
				break;
			}
		}
		
	}
	
	
	
	function edit() {
		$collectData = array();
		$collectData['page'] = 'profile-view';
		$collectData['title'] = 'View Profile: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		$collectData['action_page'] = (isset($page) ? $page : 'view');
		if (!$this->error) {
			$collectData['action_page'] = (is_string($collectData['action_page']) ? $collectData['action_page'] : 'view');
		}
		//----------------------------------------------------------------------------------------------------------
		if (!$this->error) {
			$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($this->authentication->localdata['seq'], 'seq');
			if (!isset($collectData['collect']['localuser']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Cannot determine profile data from database.";
			}
		}
		if (!$this->error) {
			$input_params = array();
			$user_params = array();
			if ($collectData['collect']['localuser']->seq !== $this->authentication->localdata['seq']) {
				$this->error = true;
				$this->error_msg[] = "Profile data is not owned by logged-in user.";
			}
		}
		if (!$this->error) {
			$collectData['collect']['local-properties'] = $this->mod_account->get_local_user_properties($this->authentication->localdata['seq']);
			$collectData['collect']['user-properties'] = array();
			if (count($collectData['collect']['local-properties']) > 0) {
				foreach ($collectData['collect']['local-properties'] as $keval) {
					$collectData['collect']['user-properties'][$keval->properties_key] = $keval->properties_value;
				}
			}
			$collectData['address_params'] = array(
				'country_code'		=> '360',
				'province_code'		=> (isset($collectData['collect']['user-properties']['user_address_province']) ? $collectData['collect']['user-properties']['user_address_province'] : ''),
				'city_code'			=> (isset($collectData['collect']['user-properties']['user_address_city']) ? $collectData['collect']['user-properties']['user_address_city'] : ''),
				'district_code'		=> (isset($collectData['collect']['user-properties']['user_address_district']) ? $collectData['collect']['user-properties']['user_address_district'] : ''),
				'area_code'			=> (isset($collectData['collect']['user-properties']['user_address_area']) ? $collectData['collect']['user-properties']['user_address_area'] : ''),
			);
			$collectData['collect']['address-province'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_province($collectData['address_params']) : NULL);
			$collectData['collect']['address-city'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_city($collectData['address_params']) : NULL);
			$collectData['collect']['address-district'] = ((strlen($collectData['address_params']['city_code']) > 0) ? $this->mod_account->get_district($collectData['address_params']) : NULL);
			$collectData['collect']['address-area'] = ((strlen($collectData['address_params']['district_code']) > 0) ? $this->mod_account->get_area($collectData['address_params']) : NULL);
			
			$collectData['collect']['address-values'] = array();
				
			if (count($collectData['collect']['address-province']) > 0) {
				foreach ($collectData['collect']['address-province'] as $keval) {
					if ((int)$keval->province_code === (int)$collectData['address_params']['province_code']) {
						$collectData['collect']['address-values']['province'] = $keval->province_name;
					}
				}
			}
			if (count($collectData['collect']['address-city']) > 0) {
				foreach ($collectData['collect']['address-city'] as $keval) {
					if ((int)$keval->city_code === (int)$collectData['address_params']['city_code']) {
						$collectData['collect']['address-values']['city'] = $keval->city_name;
					}
				}
			}
			if (count($collectData['collect']['address-district']) > 0) {
				foreach ($collectData['collect']['address-district'] as $keval) {
					if ((int)$keval->district_code === (int)$collectData['address_params']['district_code']) {
						$collectData['collect']['address-values']['district'] = $keval->district_name;
					}
				}
			}
			if (count($collectData['collect']['address-area']) > 0) {
				foreach ($collectData['collect']['address-area'] as $keval) {
					if ($keval->area_name === $collectData['address_params']['area_code']) {
						$collectData['collect']['address-values']['area'] = $keval->area_name;
					}
				}
			}
		}
		//================================================
		// Editing Profile
		//================================================
		//----
		$form_validation = TRUE;
		//----
		$user_params['body'] = array(
			'user_email' => (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
			'user_password' => (isset($this->imzcustom->php_input_request['body']['user_password']) ? $this->imzcustom->php_input_request['body']['user_password'] : ''),
			'user_password_confirm' => (isset($this->imzcustom->php_input_request['body']['user_password_confirm']) ? $this->imzcustom->php_input_request['body']['user_password_confirm'] : ''),
		);
		$user_params['body']['user_fullname'] = (isset($this->imzcustom->php_input_request['body']['user_fullname']) ? $this->imzcustom->php_input_request['body']['user_fullname'] : '');
		$user_params['body']['user_username'] = (isset($this->imzcustom->php_input_request['body']['user_username']) ? $this->imzcustom->php_input_request['body']['user_username'] : '');
		$user_params['body']['user_nickname'] = (isset($this->imzcustom->php_input_request['body']['user_nickname']) ? $this->imzcustom->php_input_request['body']['user_nickname'] : '');
		$user_params['body']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
		$user_params['body']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
		$user_params['body']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
		$user_params['body']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
		$user_params['body']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
		$user_params['body']['account_activation_ending'] = (isset($this->imzcustom->php_input_request['body']['account_activation_ending']) ? $this->imzcustom->php_input_request['body']['account_activation_ending'] : '');
		$user_params['body']['account_phonenumber'] = (isset($this->imzcustom->php_input_request['body']['account_phonenumber']) ? $this->imzcustom->php_input_request['body']['account_phonenumber'] : '');
		$user_params['body']['account_phonemobile'] = (isset($this->imzcustom->php_input_request['body']['account_phonemobile']) ? $this->imzcustom->php_input_request['body']['account_phonemobile'] : '');
		if ($form_validation) {
			if (!is_string($user_params['body']['user_fullname'])) {
				$form_validation = FALSE;
				$this->error_msg[] = "You should use string full name.";
			}
		}
		if ($form_validation) {
			if (strlen($user_params['body']['user_fullname']) === 0) {
				$form_validation = FALSE;
				$this->error_msg[] = "Input Fullname cannot be empty.";
			}
		}
		if ($form_validation) {
			if ($user_params['body']['user_password'] !== $user_params['body']['user_password_confirm']) {
				$form_validation = FALSE;
				$this->error_msg[] = "Password and Confirm password should be same.";
			}
		}
		//======================
		if (!$form_validation) {
			$this->session->set_flashdata('error', TRUE);
			$error_string = "";
			if (count($this->error_msg) > 0) {
				foreach ($this->error_msg as $errorVal) {
					$error_string .= "- {$errorVal}";
				}
			}
			$this->session->set_flashdata('action_message', $error_string);
			header('Location: ' . base_url("{$this->imzers->base_path}/profile/view/edit"));
			exit;
		} else {
			if (!$this->error) {
				$collectData['upload_img'] = base_config('upload_img');
				if (isset($collectData['upload_img']['local']) && isset($collectData['upload_img']['resize'])) {
					//============================
					// Load Library Upload and CDN
					$upload_image_params = array(
						'upload_dir'			=> (isset($collectData['upload_img']['local']['upload_dir']) ? $collectData['upload_img']['local']['upload_dir'] : (FCPATH . 'media')),
						'upload_path' 			=> (isset($collectData['upload_img']['local']['upload_path']) ? $collectData['upload_img']['local']['upload_path'] : (FCPATH . 'media' . DIRECTORY_SEPARATOR . 'images')),
						'allowed_types'			=> $collectData['upload_img']['local']['allowed_types'],
						'max_size' 				=> 5120,
						'max_width' 			=> 3260,
						'max_height' 			=> 3260,
						'encrypt_name'			=> TRUE,
						'file_name'				=> (uniqid() . time()),
					);
					$this->load->library('upload');
					$this->upload->initialize($collectData['upload_img']['local']);
					$this->load->library('image_lib');
					
					//============================
				} else {
					$this->error = true;
					$this->error_msg[] = "Config dont have upload image preference or cdn preference";
				}
			}
			if (!$this->error) {
				$input_params = array(
					'user_fullname'						=> $user_params['body']['user_fullname'],
					'user_nickname'						=> $user_params['body']['user_nickname'],
					'user_username'						=> (isset($user_params['body']['user_username']) ? $user_params['body']['user_username'] : ''),
					'user_password'						=> (is_string($user_params['body']['user_password']) || is_numeric($user_params['body']['user_password'])) ? $user_params['body']['user_password'] : '',
					'user_password_confirm'				=> (is_string($user_params['body']['user_password_confirm']) || is_numeric($user_params['body']['user_password_confirm'])) ? $user_params['body']['user_password_confirm'] : '',
					'user_phonenumber'					=> $user_params['body']['account_phonenumber'],
					'user_phonemobile'					=> $user_params['body']['account_phonemobile'],
					'user_address'						=> $user_params['body']['user_address'],
				);
				$input_params['user_phonenumber'] = sprintf('%s', $input_params['user_phonenumber']);
				$input_params['user_phonemobile'] = sprintf('%s', $input_params['user_phonemobile']);
				$input_params['user_fullname'] = sprintf('%s', $input_params['user_fullname']);
				$input_params['user_nickname'] = sprintf('%s', $input_params['user_nickname']);
				$input_params['user_username'] = sprintf('%s', $input_params['user_username']);
				$query_params = array(
					'account_username'				=> ((strlen($input_params['user_username']) > 0) ? strtolower($input_params['user_username']) : ''),
					'account_hash'					=> '', // Later
					'account_password'				=> '', // Later
					'account_inserting_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_code'		=> '', // Later,
					'account_activation_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_status'		=> 'Y', // Y for editing
					'account_nickname'				=> ((strlen($input_params['user_nickname']) > 0) ? $input_params['user_nickname'] : ''),
					'account_fullname'				=> ((strlen($input_params['user_fullname']) > 0) ? ucwords(strtolower($input_params['user_fullname'])) : ''),
					'account_address'				=> ((strlen($input_params['user_address']) > 0) ? $input_params['user_address'] : ''),
					'account_phonenumber'			=> ((strlen($input_params['user_phonenumber']) > 0) ? $input_params['user_phonenumber'] : ''),
					'account_phonemobile'			=> ((strlen($input_params['user_phonemobile']) > 0) ? $input_params['user_phonemobile'] : ''),
					'account_edited_datetime'		=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_edited_by'				=> (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system'), // Should be user itself or system or by administrator (Set Later)
				);
			}
			if (!$this->error) {
				if (isset($_FILES['user_picture']['tmp_name'])) {
					$collectData['upload_data'] = array();
					if ($_FILES['user_picture']['tmp_name'] != null) {
						if ($this->upload->do_upload('user_picture')) {
							$collectData['upload_data']['source'] = $this->upload->data();
							// Process to resize
							if (isset($collectData['upload_data']['source']['full_path'])) {
								$collectData['upload_img']['resize']['library']['source_image'] = $collectData['upload_data']['source']['full_path'];
								$this->image_lib->initialize($collectData['upload_img']['resize']['library']);
								if (!$this->image_lib->resize()) {
									$this->error = true;
									$this->error_msg[] = "Cannot using image-library for resizing image";
								} else {
									if (isset($this->image_lib->full_dst_path)) {
										if (!file_exists($this->image_lib->full_dst_path)) {
											$this->error = true;
											$this->error_msg[] = "File of resized image not really exists.";
										} else {
											// Do CDN Upload Here
											// For next project if using CDN FTP
											$query_params['account_picture'] = base_url('media/images/' . basename($this->image_lib->full_dst_path));
										}
									}
									try {
										unlink($collectData['upload_data']['source']['full_path']);
									} catch (Exception $ex) {
										$this->error = true;
										$this->error_msg[] = "Error exception while deleting original file: {$ex->getMessage()}";
									}
								}
							} else {
								$this->error = true;
								$this->error_msg[] = "Uploaded image not get full-path of image";
							}
						} else {
							$this->error = true;
							$this->error_msg[] = "Cannot upload files from user input.";
						}
						/*
						$collectData['VerotUpload'] = new VerotUpload($_FILES['user_picture']);
						if ($collectData['VerotUpload']->uploaded) {
							$collectData['VerotUpload']->file_new_name_body = $collectData['upload_img']['local']['file_name'];
							$collectData['VerotUpload']->file_safe_name = true;
							$collectData['VerotUpload']->file_overwrite = false;
							$collectData['VerotUpload']->file_auto_rename = true;
							$collectData['VerotUpload']->file_max_size = $collectData['upload_img']['local']['max_size'];
							$collectData['VerotUpload']->mime_check = true;
							$collectData['VerotUpload']->allowed = $collectData['upload_img']['local']['allowed_mimes'];
							$collectData['VerotUpload']->forbidden = $collectData['upload_img']['local']['forbidden_mimes'];
							$collectData['VerotUpload']->image_resize = true;
							$collectData['VerotUpload']->image_x = 215;
							$collectData['VerotUpload']->image_y = 215;
							$collectData['VerotUpload']->image_ratio_y = true;
							// Validate Upload
							$collectData['upload_data']['source'] = array(
								'src'	=> $collectData['VerotUpload']->file_src_name,
								'name'	=> $collectData['VerotUpload']->file_src_name_body,
								'ext'	=> $collectData['VerotUpload']->file_src_name_ext,
								'path'		=> $collectData['VerotUpload']->file_src_pathname,
								'mime'		=> $collectData['VerotUpload']->file_src_mime,
								'size'		=> $collectData['VerotUpload']->file_src_size,
								'is_image'	=> $collectData['VerotUpload']->file_is_image,
								'error'		=> $collectData['VerotUpload']->file_src_error,
							);
							if ($collectData['upload_data']['source']['is_image'] !== TRUE) {
								$this->error = true;
								$this->error_msg[] = "Source should be an image file.";
							}
						}
						*/
					}
				}
			}
			if (!$this->error) {
				if (isset($_FILES['user_picture']['tmp_name'])) {
					if ($_FILES['user_picture']['tmp_name'] != null) {
						// Process Upload with Verot-Upload
						/*
						if ($collectData['VerotUpload']->uploaded) {
							$collectData['VerotUpload']->process($collectData['upload_img']['local']['upload_path'] . DIRECTORY_SEPARATOR);
							if ($collectData['VerotUpload']->processed) {
								$collectData['upload_data']['result'] = array(
									'path'			=> $collectData['VerotUpload']->file_dst_pathname,
									'name'			=> $collectData['VerotUpload']->file_dst_name_body,
									'ext'			=> $collectData['VerotUpload']->file_dst_name_ext,
									'name_ext'		=> $collectData['VerotUpload']->file_dst_name,
									'image_x'		=> $collectData['VerotUpload']->image_dst_x,
									'image_y'		=> $collectData['VerotUpload']->image_dst_y,
									'image_type'	=> $collectData['VerotUpload']->image_dst_type,
									'image_url'		=> base_url('media/images/' . $collectData['VerotUpload']->file_dst_name),
								);
								$query_params['account_picture'] = $collectData['upload_data']['result']['image_url'];
								// Cleaning image
								$collectData['VerotUpload']->clean();
							} else {
								$this->error = true;
								$this->error_msg[] = "Cannot process upload and resize image : " . $collectData['VerotUpload']->error;
							}
						}
						*/
					}
				}
			}
			//-------------------------------------
			if (!$this->error) {
				if ($input_params['user_password'] !== $input_params['user_password_confirm']) {
					$this->error = true;
					$this->error_msg[] = "Password and confirm password not match";
				}
			}
			if (!$this->error) {
				try {
					$input_params['unique_salt_string'] = hash_hmac('sha256', $this->authentication->localdata['account_hash'], ENCRYPT_KEY, FALSE);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Cannot create new unique datetime.";
				}
			}
			if (!$this->error) {
				if (strlen($input_params['user_password']) > 0) {
					// Super-admin cannot editing profile password
					if (isset($collectData['collect']['localuser']->account_role)) {
						if ((int)$collectData['collect']['localuser']->account_role === base_config('super_admin_role')) {
							$this->error = true;
							$this->error_msg[] = "For demo purpose, Superadmin cannot editing their profile password, please make another admin to test password change.";
						}
					}
				}
			}
			if (!$this->error) {
				if (strlen($input_params['user_password']) > 0) {
					$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($input_params['unique_salt_string']);
					$query_params['account_activation_code'] = md5($query_params['account_hash']);
					try {
						$query_params['account_password'] = sha1("{$input_params['unique_salt_string']}|{$input_params['user_password']}");
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot create sha1 of encrypted password: " . $ex->getMessage();
					}
				} else {
					if (isset($query_params['account_hash'])) {
						unset($query_params['account_hash']);
					}
					if (isset($query_params['account_activation_code'])) {
						unset($query_params['account_activation_code']);
					}
					if (isset($query_params['account_password'])) {
						unset($query_params['account_password']);
					}
				}
			}
			if (!$this->error) {
				$query_params['account_edited_by'] = (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system');
				try {
					$collectData['local_users'] = $this->mod_users->get_local_user_match_by($query_params['account_username'], 'username', $this->authentication->localdata['seq']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Cannot check username is exists from model from edit-account: {$ex->getMessage()}";
				}
			}
			if (!$this->error) {
				if (count($collectData['local_users']) > 0) {
					$this->error = true;
					$this->error_msg[] = "Username already taken.";
				}
			}
			//=== Editing User
			if (!$this->error) {
				try {
					$collectData['edit_account_seq'] = $this->mod_account->edit_user($this->authentication->localdata['seq'], $query_params);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Cannot editing user to local-account: {$ex->getMessage()}";
				}
			}
			//-- Editing User Properties
			if (!$this->error) {
				$collectData['local_properties_params'] = array();
				$collectData['local_data_properties'] = array();
				$collectData['local_data_properties']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
				$collectData['local_data_properties']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
				$collectData['local_data_properties']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
				$collectData['local_data_properties']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
				$collectData['local_data_properties']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
				if ((int)$collectData['edit_account_seq'] > 0) {
					foreach ($collectData['local_data_properties'] as $key => $val) {
						if (strlen($val) > 0) {
							$collectData['local_properties_params'] = array(
								'properties_key'				=> strtolower($key),
								'properties_value'				=> $this->imzers->safe_text_post($val, 512),
							);
							$collectData['new_propertie_seq'] = $this->mod_account->update_local_user_properties($collectData['edit_account_seq'], $collectData['local_properties_params']);
						}
					}
				}
			}
			//===============
			// Done Editing
			if (!$this->error) {
				$this->session->set_flashdata('error', FALSE);
				$this->session->set_flashdata('action_message', 'Success edit profile.');
				header('Location: ' . base_url("{$collectData['base_path']}/profile/view"));
				exit;
			} else {
				$this->session->set_flashdata('error', TRUE);
				$error_to_show = "";
				foreach ($this->error_msg as $keval) {
					$error_to_show .= $keval;
				}
				$this->session->set_flashdata('action_message', $error_to_show);
				header('Location: ' . base_url("{$collectData['base_path']}/profile/view/edit"));
				exit;
			}
		}
		
		
	}
	
	
	
	
	
	function debug() {
		echo "<pre>";
		print_r($this->imzcustom->php_input_request);
	}
}