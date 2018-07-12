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
					if (isset($_SESSION['error']) && isset($_SESSION['action_message'])) {
						if ($_SESSION['error'] > 0) {
							$alert_div_dismissable = 'alert alert-danger alert-dismissable';
						} else {
							$alert_div_dismissable = 'alert alert-success alert-dismissable';
						}
						?>
						<div class='<?=$alert_div_dismissable;?>'>
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
							<?=$_SESSION['action_message'];?>
						</div>
						<?php
						unset($_SESSION['error']);
						unset($_SESSION['action_message']);
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
							<a href="<?= base_url($base_path . '/index.php/');?>">Home</a>
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-question-circle"></i> About
						</li>
					</ul>
					<!-- END PAGE TITLE & BREADCRUMB-->
				</div>
			</div>
			<!-- END PAGE HEADER-->
			
			<h3>About</h3>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="dashboard-stat green">
						<div class="visual"></div>
						<div class="details">
							<div class="number">Demo</div>
							<div class="desc">
								Demo purpose only
							</div>
						</div>
						<a href="#" class="more">
							More <i class="m-icon-swapright m-icon-white"></i>
						</a>		
					</div>
				</div>
			</div>
			
			
			
		</div>
	</div>
	
	
	