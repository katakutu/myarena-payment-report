<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>


<!-- BEGIN LOGIN -->
<div class="content">
	<h3 class="form-title">Create New Password.</h3>
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
	<form action="<?= base_url("{$base_path}/account/passwordChange");?>" class="login-form" role="form" id="UserLoginForm" method="post" accept-charset="utf-8">
		<div class="form-group required">
			<div class="input-icon">
				<i class="fa fa-unlock"></i>
				<input name="user_password_current" class="form-control placeholder-no-fix" placeholder="Current password" maxlength="64" type="hidden" id="user_password_current" required="required" value="<?= (isset($_SESSION['tmp_password']) ? $_SESSION['tmp_password'] : '');?>" />
			</div>
		</div>
		<div class="form-group required">
			<div class="input-icon">
				<i class="fa fa-lock"></i>
				<input name="user_password_new" class="form-control placeholder-no-fix" placeholder="New password" maxlength="64" type="password" id="user_password_new" required="required" />
			</div>
		</div>
		<div class="form-group required">
			<div class="input-icon">
				<i class="fa fa-lock"></i>
				<input name="user_password_confirm" class="form-control placeholder-no-fix" placeholder="Confirm new password" maxlength="64" type="password" id="user_password_confirm" required="required" />
			</div>
		</div>
		<div class="form-actions">
			<button class="btn blue pull-right" type="submit">
				Set Password <i class="m-icon-swapright m-icon-white"></i>
			</button>
		</div>
		<div style="display:none;">
			<input type="hidden" name="user_server" value="localhost" id="user_server" />

		</div>
	</form>
</div>
<!-- END LOGIN -->








