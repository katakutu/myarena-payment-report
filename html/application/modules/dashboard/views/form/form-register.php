<?php
if (!defined('PHP_MYSQL_CRUD_NATIVE')) { exit('Script cannot access directly.'); }
?>


<!-- BEGIN REGISTER -->
<div class="content">
	
	
	<h3 class="form-title">Register new account.</h3>
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
	<form action="<?= base_url("{$base_path}/account/registeraction");?>" class="login-form" role="form" id="UserSignupForm" method="post" accept-charset="utf-8">
		<div class="form-group required">
			<div class="input-icon">
			<i class="fa fa-user"></i>
			<input name="user_fullname" class="form-control placeholder-no-fix" placeholder="Full Name" maxlength="255" type="text" id="user_fullname" required="required"/>
			</div>
		</div>
		<div class="form-group required">
			<div class="input-icon">
			<i class="fa fa-envelope"></i>
			<input name="user_email" class="form-control placeholder-no-fix" placeholder="Email" maxlength="255" type="text" id="user_email" required="required"/>
			</div>
		</div>
		<div class="form-group required">
			<div class="input-icon">
				<i class="fa fa-lock"></i>
				<input name="user_password" class="form-control placeholder-no-fix" placeholder="Password" type="password" id="user_password" required="required"/>
			</div>
		</div>
		<div class="form-group required">
			<div class="input-icon">
				<i class="fa fa-unlock-alt"></i>
				<input name="user_password_confirm" class="form-control placeholder-no-fix" placeholder="Confirm Password" type="password" id="user_password_confirm" required="required"/>
			</div>
		</div>
		<div class="form-actions">
			<button class="btn blue pull-right" type="submit">
				Register <i class="m-icon-swapright m-icon-white"></i>
			</button>
		</div>
		<div style="display:none;">
			<input type="hidden" name="user_server" value="localhost" id="user_server" />

		</div>
	</form>
	<p class="text-center">
		<a href="<?= base_url("{$base_path}/account/login");?>"><strong>Login</strong></a>
		<br />
		<a href="<?= base_url("{$base_path}/account/activationForm");?>"><strong>Resend Activation Code</strong></a>
	</p>
</div>
<!-- END LOGIN -->








