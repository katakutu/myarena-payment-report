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
							<i class="fa fa-tachometer"></i> Dashboard
						</li>
					</ul>
					<!-- END PAGE TITLE & BREADCRUMB-->
				</div>
			</div>
			<!-- END PAGE HEADER-->
			
			<h3>Total Statistics</h3>
			<div class="row">
				<?php
				if (in_array($collect['userdata']['account_role'], base_config('admin_role'))) {
					?>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="dashboard-stat blue">
							<div class="visual"></div>
							<div class="details">
								<div class="number"><?= (isset($collect['users']['total']['total_users']) ? $collect['users']['total']['total_users'] : '-');?></div>
								<div class="desc">
									Total Users
								</div>
							</div>
							<a href="<?= base_url($base_path . '/users/lists');?>" class="more">
								More <i class="m-icon-swapright m-icon-white"></i>
							</a>		
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="dashboard-stat purple">
							<div class="visual"></div>
							<div class="details">
								<div class="number"><?= (isset($collect['payment_providers']) ? count($collect['payment_providers']) : '-');?></div>
								<div class="desc">
									Payment Providers
								</div>
							</div>
							<a href="<?= base_url('paymentreport' . '/paymentreport/listprovider');?>" class="more">
								More <i class="m-icon-swapright m-icon-white"></i>
							</a>		
						</div>
					</div>
					<?php
				} else {
					?>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="dashboard-stat purple">
							<div class="visual"></div>
							<div class="details">
								<div class="number">Demo</div>
								<div class="desc">
									Demo only
								</div>
							</div>
							<a href="#" class="more">
								More <i class="m-icon-swapright m-icon-white"></i>
							</a>		
						</div>
					</div>
					<?php
				}
				?>
				<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
					<div class="dashboard-stat green">
						<div class="visual"></div>
						<div class="details">
							<div class="number">
								<?php
								if (isset($collect['payment_yesterday'])) {
									if (isset($collect['payment_yesterday'][0])) {
										?>
										<span class="text-sm text-default"><?=$collect['payment_yesterday'][0]->payment_currency;?> <?= number_format($collect['payment_yesterday'][0]->payment_amounts, 2);?> (<?= number_format($collect['payment_yesterday'][0]->payment_units);?>)</span>
										<?php
									}
								} else {
									echo "-";
								}
								?>
							</div>
							<div class="desc">
								Lastest Summaries
							</div>
						</div>
						<a href="<?= base_url('paymentreport' . '/paymentreport/listpayments');?>" class="more">
							More <i class="m-icon-swapright m-icon-white"></i>
						</a>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
					<div class="dashboard-stat red">
						<div class="visual"></div>
						<div class="details">
							<div class="number">
								<?php
								if (isset($collect['payment_summaries']->value)) {
									echo number_format($collect['payment_summaries']->value);
								} else {
									echo "-";
								}
								?>
							</div>
							<div class="desc">
								All Transactions
							</div>
						</div>
						<a href="<?= base_url('paymentreport' . '/paymentreport/alltransactions');?>" class="more">
							More <i class="m-icon-swapright m-icon-white"></i>
						</a>
					</div>
				</div>
			</div>
			
			
			
		</div>
	</div>
	
	
	