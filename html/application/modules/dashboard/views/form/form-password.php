<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>


<!-- BEGIN LOGIN -->
<div class="content">
	<h3 class="form-title">Reset Password.</h3>
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
	<form action="<?= base_url("{$base_path}/account/passwordForgetAction");?>" class="login-form" role="form" id="UserLoginForm" method="post" accept-charset="utf-8">
		<div class="form-group required">
			<div class="input-icon">
			<i class="fa fa-envelope"></i>
			<input name="user_email" class="form-control placeholder-no-fix" placeholder="Email" maxlength="255" type="text" id="UserUsername" required="required"/>
			</div>
		</div>
		<div class="form-actions">
			<button class="btn blue pull-right" type="submit">
				Send Email <i class="m-icon-swapright m-icon-white"></i>
			</button>
		</div>
		<div style="display:none;">
			<input type="hidden" name="user_server" value="localhost" id="user_server" />

		</div>
	</form>
	<p class="text-center">
		<a href="<?= base_url("{$base_path}/account/login");?>"><strong>Login</strong></a>
		<br />
		<a href="<?= base_url("{$base_path}/account/register");?>"><strong>Register New Account</strong></a>
	</p>
</div>
<!-- END LOGIN -->








