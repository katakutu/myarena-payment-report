<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }


?>

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
					<!-- BEGIN PAGE TITLE & BREADCRUMB -->
					<h3 class="page-title">
						<?= (isset($title) ? $title : ''); ?>		
					</h3>
					<ul class="page-breadcrumb breadcrumb">
						<li class="btn-group">
							<a href="<?= base_url($base_path . '/profile/view/edit');?>" class="btn green pull-right">
								Edit Profile<i class="fa fa-pencil"></i>
							</a>
						</li>
						<li>
							<i class="fa fa-users"></i>
							<a href="<?= base_url($base_path . '/profile');?>">Profile</a>
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-eye"></i><?= (isset($title) ? $title : ''); ?>
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
							<h3 class="box-title">View User Details</h3>
						</div>
						<div class="box-body no-padding">
                            <table class="table table-hover">
								<thead>
									<tr>
										<th> &nbsp; </th>
										<th>
											<div class="form-group">
												<?php
												if (isset($collect['localuser']->account_picture)) {
													if (strlen($collect['localuser']->account_picture) > 0) {
														?>
														<img alt="profile-pict" src="<?=$collect['localuser']->account_picture;?>" />
														<?php
													} else {
														?>
														<img alt="profile-pict" src="<?= base_url('assets/media/images/no-image.png');?>" />
														<?php
													} 
												} else {
													?>
													<img alt="profile-pict" src="<?= base_url('assets/media/images/no-image.png');?>" />
													<?php
												}
												?>
											</div>
										</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Full Name</td>
										<td><?= (isset($collect['localuser']->account_fullname) ? $collect['localuser']->account_fullname : '');?></td>
									</tr>
									<tr>
										<td>Email</td>
										<td><?= (isset($collect['localuser']->account_email) ? $collect['localuser']->account_email : '');?></td>
									</tr>
									<tr>
										<td>Register Date</td>
										<td><?= (isset($collect['localuser']->account_inserting_datetime) ? $collect['localuser']->account_inserting_datetime : '');?></td>
									</tr>
									<tr>
										<td>Role</td>
										<td>
											<?= (isset($collect['localuser']->role_name) ? $collect['localuser']->role_name : '-');?>
										</td>
									</tr>
									<tr>
										<td>
											<div class="form-group">Address</div>
										</td>
										<td>
											<div class="form-group">
												<?= (isset($collect['localuser']->account_address) ? $collect['localuser']->account_address : '');?>
												<?= (isset($collect['address-values']['area']) ? $collect['address-values']['area'] . ', ' : '');?>
												<?= (isset($collect['address-values']['district']) ? $collect['address-values']['district'] . '<br/>' : '');?>
												<?= (isset($collect['address-values']['city']) ? $collect['address-values']['city'] . ', ' : '');?>
												<?= (isset($collect['address-values']['province']) ? $collect['address-values']['province'] : '');?>
											</div>
										</td>
									</tr>
									<tr>
										<td>Phone Number</td>
										<td>
											<div class="row">
												<div class="col-xs-4">Phone</div>
												<div class="col-xs-8"><?= (isset($collect['localuser']->account_phonenumber) ? $collect['localuser']->account_phonenumber : '-');?></div>
											</div>
											<div class="row">
												<div class="col-xs-4">Mobile</div>
												<div class="col-xs-8"><?= (isset($collect['localuser']->account_phonemobile) ? $collect['localuser']->account_phonemobile : '-');?></div>
											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						
						
						
						
						<div class="box-body no-padding">
						
							
						</div>
							
						
						
						
						<div class="box-footer">
							
						</div>

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
</script>	
	