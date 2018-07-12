<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>
<style type="text/css">
	.image-input-template img {
		max-width: 100%; 
	}
	#user-img-upload {
		border: none;
	}
	.input-group-btn img {
		max-width: 24px;
		cursor:pointer;
	}
	.btn-file > input {
		display: none;
	}
</style>

	<!-- BEGIN CONTENT -->
	<div class="page-content-wrapper">
		<div class="page-content">
			<!-- BEGIN FLASH MESSAGE-->
			<div class="row">
				<div class="col-md-12">
					<?php
					if ($this->session->flashdata('error')) {
						if ($this->session->flashdata('action_message')) {
							?>
							<div class="alert alert-danger alert-dismissable">
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
								<?=$this->session->flashdata('action_message');?>
							</div>
							<?php 
						}
					} else {
						if ($this->session->flashdata('action_message')) {
							?>
							<div class="alert alert-success alert-dismissable">
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
								<?=$this->session->flashdata('action_message');?>
							</div>
							<?php 
						}
					}
					?>
				</div>
			</div>
			<!-- END FLASH MESSAGE-->
		
			<!-- BEGIN PAGE HEADER-->
			<div class="row">
				<div class="col-md-12">
					<!-- BEGIN PAGE TITLE & BREADCRUMB-->
					<h3 class="page-title">
						<?= (isset($title) ? $title : ''); ?>		
					</h3>
					<ul class="page-breadcrumb breadcrumb">
						<li class="btn-group">
							<a href="<?= base_url($base_path . '/users/view/' . $collect['localuser']->seq);?>" class="btn green pull-right">
								View Data<i class="fa fa-eye"></i>
							</a>
						</li>
						<li>
							<i class="fa fa-users"></i>
							<a href="<?= base_url($base_path . '/users');?>">Users</a>
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-pencil"></i><?= (isset($title) ? $title : ''); ?>
						</li>
					</ul>
					<!-- END PAGE TITLE & BREADCRUMB-->
				</div>
			</div>
			<!-- END PAGE HEADER-->
		
			<div class="row">
				<div class="col-xs-12 col-sm-12 col-md-6">
					<div class="portlet box blue">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-info-circle"></i> User Roles
							</div>
							<div class="tools">
								<a class="expand" href="javascript:;"></a>
							</div>
						</div>
						<div class="portlet-body form display-hide">
							<div class="form-body">
								<div class="form-group">
									<?php
									if (isset($collect['roles'])) {
										if (is_array($collect['roles']) && count($collect['roles'])) {
											foreach ($collect['roles'] as $val) {
												?>
												<span class="form-control"><i class="fa fa-check"></i> <?=$val->role_name;?></span>
												<?php
											}
										}
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- BEGIN FORM-->
				<div class="col-md-6 pull-right">
					<form action="<?= base_url($base_path . '/users/lists');?>" class="form-horizontal" role="form" id="searh-form" method="post">
						<div class="input-group">
							<input name="search_text" class="form-control" placeholder="Search User" type="text" id="search_text" value="<?= (isset($search_text) ? $search_text : '');?>" />
							<span class="input-group-btn">
								<input  class="btn btn-primary" type="submit" value="Search"/>
							</span>
						</div>
					</form>	
				</div>
				<!-- END FORM-->
			</div>
			
			<div class="row">
				<div class="col-md-8">
					<div class="box box-primary">
						<div class="box-header">
							<h3 class="box-title">Edit User Details</h3>
						</div>
						<form id="users-edit" action="<?= base_url($base_path . '/users/editaction/' . $collect['localuser']->seq);?>" method="post" role="form" enctype="multipart/form-data">
							<div class="box-body">
								<div class="form-body">
									<div class="form-group required">
										<div class="row">
											<div class="col-md-6">
												<label for="user_email">Email address</label>
												<span class="form-control required email" id="user_email"><?= (isset($collect['localuser']->account_email) ? $collect['localuser']->account_email : '');?></span>
											</div>
											<div class="col-md-6">
												<label for="user_username">Username</label>
												<input type="text" class="form-control required" id="user_username" name="user_username" maxlength="64" value="<?= (isset($collect['localuser']->account_username) ? $collect['localuser']->account_username : '');?>" />
											</div>
										</div>
									</div>
									<div class="form-group">
										<div class="row">
											<div class="col-md-6">
												<label for="user_password">Password</label>
												<input type="password" class="form-control required" id="user_password"  name="user_password" maxlength="64" />
											</div>
											<div class="col-md-6">
												<label for="user_password_confirm">Confirm Password</label>
												<input type="password" class="form-control required equalTo" id="user_password_confirm" name="user_password_confirm" maxlength="64" />
											</div>
										</div>
									</div>
									<div class="form-group">
										<div class="row">
											<div class="col-md-4">
												<label for="user-img-input">Profile Picture</label>
												<div class="row">
													<div class="col-md-8 col-lg-10 col-sm-10 col-xs-12 image-input-template">
														<img id='user-img-upload' src="<?= (isset($collect['localuser']->account_picture) ? $collect['localuser']->account_picture : base_url('assets/media/images/no-image.png'));?>" />
													</div>
													<div class="col-md-4 col-lg-2 col-sm-2 col-xs-12 btn btn-file">
														<label class="input-group-btn" for="user-img-input">
															<img alt='image-select' src='<?= base_url('assets/img/icons/buttons/plus.svg');?>' />
														</label>
														<input type="file" id="user-img-input" name="user_picture" />
													</div>
												</div>
											</div>
											<div class="col-md-4">
												<label for="user_fullname">Full Name</label>
												<input type="text" class="form-control required" id="user_fullname" name="user_fullname" maxlength="128" value="<?= (isset($collect['localuser']->account_fullname) ? $collect['localuser']->account_fullname : '');?>">
											</div>
											<div class="col-md-4">
												<label for="user_nickname">Nickname</label>
												<input type="text" class="form-control required" id="user_nickname" name="user_nickname" maxlength="32" value="<?= (isset($collect['localuser']->account_nickname) ? $collect['localuser']->account_nickname : '');?>" />
											</div>
										</div>
									</div>
									<div class="form-group">
										<div class="row">
											<div class="col-md-4">
												<label for="user_phonenumber">Phone Number</label>
												<input type="text" class="form-control required" id="user_phonenumber" name="user_phonenumber" maxlength="128" value="<?= (isset($collect['localuser']->account_phonenumber) ? $collect['localuser']->account_phonenumber : '');?>">
											</div>
											<div class="col-md-4">
												<label for="user_phonemobile">Mobile Number</label>
												<input type="text" class="form-control required" id="user_phonemobile" name="user_phonemobile" maxlength="32" value="<?= (isset($collect['localuser']->account_phonemobile) ? $collect['localuser']->account_phonemobile : '');?>" />
											</div>
											<div class="col-md-4">
												<label for="subscription_expiring">Account Expired</label>
												<input type="text" class="form-control required" id="subscription_expiring" name="subscription_expiring" maxlength="32" value="<?= (isset($collect['localuser']->subscription_expiring) ? date('Y-m-d', strtotime($collect['localuser']->subscription_expiring)) : '');?>" />
											</div>
										</div>
									</div>
									<div class="row form-group">
										<div class="col-md-12">
											<label for="user_address">User Address</label>
											<textarea class="form-control required" id="user_address" name="user_address"><?= (isset($collect['localuser']->account_address) ? $collect['localuser']->account_address : '');?></textarea>
										</div>
									</div>
									<div class="row form-group">
										<div class="col-md-3">
											<select class="form-control required" id="user_address_province" name="user_address_province">
												<option value="">-- Select Province --</option>
												<?php
												$user_address_province = (isset($collect['user-properties']['user_address_province']) ? $collect['user-properties']['user_address_province'] : 0);
												if (isset($collect['address-province']) && count($collect['address-province']) > 0) {
													foreach ($collect['address-province'] as $keval) {
														if ((int)$keval->province_code === (int)$user_address_province) {
															$option_selected = " selected='selected'";
														} else {
															$option_selected = "";
														}
														?>
														<option value="<?=$keval->province_code;?>"<?=$option_selected;?>><?=$keval->province_name;?></option>
														<?php
													}
												}
												?>
											</select>
										</div>
										<div class="col-md-3">
											<select class="form-control required" id="user_address_city" name="user_address_city">
												<option value="">-- Kota/Kabupaten --</option>
												<?php
												$user_address_city = (isset($collect['user-properties']['user_address_city']) ? $collect['user-properties']['user_address_city'] : 0);
												if (isset($collect['address-city']) && count($collect['address-city']) > 0) {
													foreach ($collect['address-city'] as $keval) {
														if ((int)$keval->city_code === (int)$user_address_city) {
															$option_selected = " selected='selected'";
														} else {
															$option_selected = "";
														}
														?>
														<option value="<?=$keval->city_code;?>"<?=$option_selected;?>><?=$keval->city_name;?></option>
														<?php
													}
												}
												?>
											</select>
										</div>
										<div class="col-md-3">
											<select class="form-control required" id="user_address_district" name="user_address_district">
												<option value="">-- Kecamatan --</option>
												<?php
												$user_address_district = (isset($collect['user-properties']['user_address_district']) ? $collect['user-properties']['user_address_district'] : 0);
												if (isset($collect['address-district']) && count($collect['address-district']) > 0) {
													foreach ($collect['address-district'] as $keval) {
														if ((int)$keval->district_code === (int)$user_address_district) {
															$option_selected = " selected='selected'";
														} else {
															$option_selected = "";
														}
														?>
														<option value="<?=$keval->district_code;?>"<?=$option_selected;?>><?=$keval->district_name;?></option>
														<?php
													}
												}
												?>
											</select>
										</div>
										<div class="col-md-3">
											<select class="form-control required" id="user_address_area" name="user_address_area">
												<option value="">-- Desa/Kelurahan --</option>
												<?php
												$user_address_area = (isset($collect['user-properties']['user_address_area']) ? $collect['user-properties']['user_address_area'] : 0);
												if (isset($collect['address-area']) && count($collect['address-area']) > 0) {
													foreach ($collect['address-area'] as $keval) {
														if (strtolower($keval->area_name) === strtolower($user_address_area)) {
															$option_selected = " selected='selected'";
														} else {
															$option_selected = "";
														}
														?>
														<option value="<?=$keval->area_name;?>"<?=$option_selected;?>><?=$keval->area_name;?></option>
														<?php
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<div class="row">
											<div class="col-md-6">
												<label for="account_delete_status">Account Deletion</label>
												<?php
												$user_delete_status = (isset($collect['localuser']->account_delete_status) ? $collect['localuser']->account_delete_status : 0);
												?>
												<select class="form-control required" id="account_delete_status" name="account_delete_status">
													<?php
													if ((int)$user_delete_status > 0) {
														?>
														<option value="1" selected="selected">Yes</option>
														<option value="0">No</option>
														<?php
													} else {
														?>
														<option value="1">Yes</option>
														<option value="0" selected="selected">No</option>
														<?php
													}
													?>
												</select>
											</div>
											<div class="col-md-6">
												<label for="account_remark">Remark/Catatan</label>
												<input type="text" class="form-control" id="account_remark" name="account_remark" value="<?= (isset($collect['localuser']->account_inserting_remark) ? $collect['localuser']->account_inserting_remark : '');?>" maxlength="128" />
											</div>
										</div>
									</div>
									<div class="form-group required">
										<div class="row">
											<div class="col-md-6">
												<label for="user_role">Role</label>
												<select class="form-control required" id="user_role" name="user_role">
													<?php
													$user_role = (isset($collect['localuser']->account_role) ? $collect['localuser']->account_role : 1);
													if (isset($collect['roles']) && count($collect['roles'])) {
														$role_i = 0;
														foreach ($collect['roles'] as $role) {
															if ((int)$role->role_seq === (int)$user_role) {
																$selected_role = " selected='selected'";
															} else {
																$selected_role = "";
															}
															?>
															<option value="<?=$role->role_seq;?>"<?=$selected_role;?>><?=$role->role_name;?></option>
															<?php
															$role_i += 1;
														}
													}
													?>
												</select>
											</div>
											<div class="col-md-6">
												<label for="account_active">Active Status</label>
												<select class="form-control required" id="account_active" name="account_active">
													<?php
													if (strtoupper($collect['localuser']->account_active) === 'Y') {
														?>
														<option value="Y" selected="selected">Yes</option>
														<option value="N">No</option>
														<?php
													} else {
														?>
														<option value="Y">Yes</option>
														<option value="N" selected="selected">No</option>
													<?php
													}
													?>
												</select>
											</div>
										</div>
									</div>
									
								</div>    
							</div>
							
							<div class="box-footer">
								<div class="form-group">
									<input type="submit" class="btn btn-primary" value="Submit" />
									<input type="reset" class="btn btn-default" value="Reset" />
								</div>
							</div>
						</form>
					</div>
				</div>
				
				<div class="col-md-4">
				
				</div>
			</div>
			
			
		</div>
	</div>
	<!-- END CONTENT -->
	
<script src="<?= base_url('assets/plugins/datepick/jquery.plugin.js');?>" type="text/javascript"></script>
<link href="<?= base_url('assets/plugins/datepick/jquery.datepick.css');?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/datepick/jquery.datepick.js');?>" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		var datepickParams = {
			showSpeed: 'fast',
			dateFormat: 'yyyy-mm-dd',
			minDate: new Date()
		};
		//$('#subscription_starting').datepick(datepickParams);
		$('#subscription_expiring').datepick(datepickParams);
		//$('#account_activation_ending').datepick(datepickParams);
		//$('#inlineDatepicker').datepick({onSelect: showDate});
	});
</script>
<script type="text/javascript">
$(document).ready(function() {
	function get_address(address_type) {
		var address_type = address_type;
		address_type = address_type.toLowerCase();
		var ajaxData = {
			'user_address_country': '360',
			'user_address_province': $('#user_address_province').val(),
			'user_address_city': $('#user_address_city').val(),
			'user_address_district': $('#user_address_district').val(),
			'user_address_area': $('#user_address_area').val()
		};
		var ajaxUrl = '<?= base_url($base_path . '/dashboard/get_address');?>';
		if (address_type == 'province') {
			ajaxUrl += '/province';
		} else if (address_type == 'city') {
			ajaxUrl += '/city';
		} else if (address_type == 'district') {
			ajaxUrl += '/district';
		} else if (address_type == 'area') {
			ajaxUrl += '/area';
		} else {
			ajaxUrl += '/';
		}
		$.ajax({
			type: "POST",
			url: ajaxUrl,
			data: ajaxData,
			cache: false,
			success: function(ajaxReturn){
				if (address_type == 'province') {
					$('#user_address_city').html(ajaxReturn);
					$('#user_address_district').html('<option value="">-- Kecamatan --</option>');
					$('#user_address_area').html('<option value="">-- Desa/Kelurahan --</option>');
				} else if (address_type == 'city') {
					$('#user_address_district').html(ajaxReturn);
					$('#user_address_area').html('<option value="">-- Desa/Kelurahan --</option>');
				} else if (address_type == 'district') {
					$('#user_address_area').html(ajaxReturn);
				} else if (address_type == 'area') {
					//$('#user_address_province').html(ajaxReturn);
				} else {
					$('#user_address_province').html(ajaxReturn);
				}
			}
		});
	}
	$("#user_address_province").change(function () {
		get_address('province');
	});
	$("#user_address_city").change(function () {
		get_address('city');
	});
	$("#user_address_district").change(function () {
		get_address('district');
	});
	$("#user_address_area").change(function () {
		get_address('area');
	});
	
	
});
//=================================
// Profile Picture
$(document).on('change', '.btn-file :file', function() {
var input = $(this),
	label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
	input.trigger('fileselect', [label]);
});
$('.btn-file :file').on('fileselect', function(event, label) {
	var input = $(this).parents('.input-group').find(':text'),
		log = label;
	if( input.length ) {
		input.val(log);
	} else {
		if( log ) {
			console.log(log);
		}
	}
});
function readURL(input) {
	if (input.files && input.files[0]) {
		var reader = new FileReader();
		
		reader.onload = function (e) {
			$('#user-img-upload').attr('src', e.target.result);
		}
		reader.readAsDataURL(input.files[0]);
	}
}
$("#user-img-input").change(function(){
	readURL(this);
	//$('#item-selected-stories-gambar').attr('value', '');
});
</script>	
	