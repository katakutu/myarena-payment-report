<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

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
				<!-- BEGIN PAGE TITLE & BREADCRUMB-->
				<h3 class="page-title">
					<?= (isset($title) ? $title : '');?>	
				</h3>
				<ul class="page-breadcrumb breadcrumb">
					<li>
						<i class="fa fa-home"></i>
						<a href="<?= base_url($base_path . '/');?>">Home</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-bars"></i>
						<a href="<?= base_url($base_path . '/mutasi');?>">Mutasi</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-plus-circle"></i> Add
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<div class="box box-primary">
					<div class="box-header">
						<h3 class="box-title">
							Add New Bank Account
						</h3>
					</div>
					<form id="mutasi-add" action="<?php echo base_url($base_path . '/mutasi/addaccount') ?>" method="post" role="form">
						<div class="box-body no-padding">
							<div class="form-body">
								<div class="form-group required">
									<label for="account_title">Account Title</label>
									<input type="text" class="form-control required" id="account_title"  name="account_title" maxlength="64" value="<?= (isset($input_params['account_title']) ? base_safe_text($input_params['account_title'], 64) : '');?>" />
								</div>
								<div class="form-group">
									<input type="checkbox" class="form-control" id="account_is_active" name="account_is_active" value="Y" /> Account is Active?
									<label for="account_is_active"></label>
								</div>
								<div class="form-group required">
									<label for="account_username">Account Username</label>
									<input type="text" class="form-control required" id="account_username"  name="account_username" maxlength="128" value="<?= (isset($input_params['account_username']) ? base_safe_text($input_params['account_username'], 128) : '');?>" />
								</div>
								<div class="form-group required">
									<label for="account_password">Account Password</label>
									<input type="password" class="form-control required" id="account_password"  name="account_password" maxlength="128" value="<?= (isset($input_params['account_password']) ? base_safe_text($input_params['account_password'], 128) : '');?>" />
								</div>
								<div class="form-group required">
									<label for="account_password_confirm">Confirm Password</label>
									<input type="password" class="form-control required" id="account_password_confirm"  name="account_password_confirm" maxlength="128" value="<?= (isset($input_params['account_password_confirm']) ? base_safe_text($input_params['account_password_confirm'], 128) : '');?>" />
								</div>
								<div class="form-group required">
									<label for="account_bank_seq">Bank</label>
									<select class="form-control required" id="account_bank_seq" name="account_bank_seq">
										<?php
										if (isset($collect['bank_type'])) {
											if (is_array($collect['bank_type']) && count($collect['bank_type'])) {
												foreach ($collect['bank_type'] as $val) {
													if (strtoupper($val->bank_is_active) === 'Y') {
														?><option value="<?=$val->seq;?>"><?=$val->bank_name;?></option><?php
													}
												}
											}
										}
										?>
									</select>
								</div>
								<?php
								/*
								<div class="form-group required">
									<label for="account_is_multiple_rekening">Account Have Multiple Rekening Number?</label>
									<select class="form-control required" id="account_is_multiple_rekening"  name="account_is_multiple_rekening">
										<option value="Y">Yes</option>
										<option value="N" selected="selected">No</option>
									</select>
								</div>
								<div class="form-group required">
									<label for="account_ordering">Account Ordering</label>
									<input type="text" class="form-control required" id="account_ordering"  name="account_ordering" maxlength="2" placeholder="0" value="<?= (isset($input_params['account_ordering']) ? base_safe_text($input_params['account_ordering'], 1) : '');?>" />
								</div>
								*/
								?>
								<div class="form-group">
									<input type="hidden" name="account_is_multiple_rekening" value="N" />
									<input type="hidden" name="account_ordering" value="<?= time();?>" />
								</div>
							</div>
						</div>
					
						<div class="box-footer">
							<div class="form-group">
								<button id="save-this-item" type="submit" class="btn btn-primary">Save Account</button>
								<a id="cancel-this-item" href="<?= base_url($base_path . '/mutasi/listaccount');?>" class="btn btn-default">Cancel</a>
							</div>
						</div>
					</form>
				</div>
				
				
				
			</div>
			
			
			
			
			
			
			<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->



		
		
		
	</div>
</div>