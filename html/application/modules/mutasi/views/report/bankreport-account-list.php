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
						<i class="fa fa-bar-chart"></i>
						<a href="<?= base_url($base_path . '/report');?>">Report</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-university"></i>
						<a href="<?= base_url($base_path . '/report/viewmutasi/' . $collect['bank_type_data']->bank_code);?>"><?=$collect['bank_type_data']->bank_name;?></a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-list"></i> Accounts
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-md-4 col-sm-12 col-xs-12">
				<div class="portlet box blue">
					<div class="portlet-title">
						<div class="caption">
							<i class="fa fa-info"></i> All Banks
						</div>
						<div class="tools">
							<a class="expand" href="javascript:;"></a>
						</div>
					</div>
					<div class="portlet-body form display-hide">
						<div class="form-body">
							<ul class="menu nav">
								<?php
								if (isset($collect['bank_type'])) {
									if (is_array($collect['bank_type']) && count($collect['bank_type'])) {
										foreach ($collect['bank_type'] as $val) {
											if ($val->bank_code == $bank_code) {
												$li_href_active = 'btn-primary';
											} else {
												$li_href_active = '';
											}
											?>
											<li class="<?=$li_href_active;?>">
												<a href="<?= base_url($base_path . '/report/viewmutasi/' . $val->bank_code);?>">
													<i class="fa fa-university"></i>
													<span><?=$val->bank_name;?></span>
												</a>
											</li>
											<?php
										}
									}
								}
								?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xs-12 pull-right">
				<!-- BEGIN FORM-->
				<form action="<?= base_url($base_path . '/report/viewmutasi/' . $collect['bank_type_data']->bank_code);?>" class="form-horizontal" role="form" id="searh-form" method="post">
					<div class="input-group">
						<input name="search_text" class="form-control" placeholder="Search...." type="text" id="search_text" value="<?= (isset($search_text)? base_safe_text($search_text, 64) : '');?>" />
						<span class="input-group-btn">
							<input  class="btn btn-primary" type="submit" value="Search"/>
						</span>
					</div>	
				</form>
				<!-- END FORM-->
			</div>
		</div>
		
		<div class="row">
			<?php
			if (isset($collect['bank_type'])) {
				if (is_array($collect['bank_type']) && count($collect['bank_type'])) {
					foreach ($collect['bank_type'] as $val) {
						if ($val->bank_code == $bank_code) {
							$div_href_active = 'btn btn-info';
						} else {
							$div_href_active = 'btn btn-default';
						}
						?>
						<div class="col-md-2 col-xs-4">
							<a class="<?=$div_href_active;?>" href="<?= base_url($base_path . '/report/viewmutasi/' . $val->bank_code);?>">
								<i class="fa fa-university"></i>
								<span><?=$val->bank_name;?></span>
							</a>
						</div>
						<?php
					}
				}
			}
			?>
		</div>
		
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-header">
						<h2 class="box-title">
							Accounts : 
							<span><?=$collect['bank_type_data']->bank_name;?></span>
						</h2>
					</div>
					<div class="box-body table-responsive no-padding">
						<?php
						if (isset($collect['bank_accounts']['data'])) {
							if(is_array($collect['bank_accounts']['data'])) {
								if (count($collect['bank_accounts']['data']) > 0) {
									?>
									<table class="table table-hover">
										<thead>
											<tr>
												<th>No.</th>
												<th>Name</th>
												<th>Bank</th>
												<th>Rekening</th>
												<th>Status</th>
												<th>Units</th>
												<th>Amounts</th>
												<th class="text-center">Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$for_i = 1;
											foreach($collect['bank_accounts']['data'] as $keval) {
												?>
												<tr>
													<td><?=$for_i;?></td>
													<td><?=$keval->account_title; ?></td>
													<td>
														<a href="<?= base_url("{$base_path}/mutasi/listaccount/{$keval->bank_code}");?>">
															<?=$keval->bank_name;?>
														</a>
													</td>
													<td>
														<?php
														if (strtoupper($keval->account_is_multiple_rekening) === 'Y') {
															?>
															<div class="rekening-number">
																<?=$keval->rekening_number;?>
															</div>
															<?php
														} else {
															?>
															<div class="rekening-number">
																<?=$keval->rekening_number;?>
															</div>
															<?php
														}
														?>
														
													</td>
													<td>
														<?php
														if (strtoupper($keval->account_is_active) === 'Y') {
															?><span class="btn btn-sm btn-success"><i class="fa fa-check-circle"></i></span><?php
														} else {
															?><span class="btn btn-sm btn-danger"><i class="fa fa-ban"></i></span><?php
														}
														?>
													</td>
													<td>
														<div class="row">
															<div class="col-md-5">
																Deposit
															</div>
															<div class="col-md-7">
																<?= (isset($keval->transaction_all['deposit']->count_unit) ? number_format($keval->transaction_all['deposit']->count_unit) : '-');?>
															</div>
														</div>
														<div class="row">
															<div class="col-md-5">
																Transfer
															</div>
															<div class="col-md-7">
																<?= (isset($keval->transaction_all['transfer']->count_unit) ? number_format($keval->transaction_all['transfer']->count_unit) : '-');?>
															</div>
														</div>
													</td>
													<td>
														<div class="row">
															<div class="col-md-5">
																Deposit
															</div>
															<div class="col-md-7">
																<?= (isset($keval->transaction_all['deposit']->sum_amount) ? number_format($keval->transaction_all['deposit']->sum_amount, 2) : '-');?>
															</div>
														</div>
														<div class="row">
															<div class="col-md-5">
																Transfer
															</div>
															<div class="col-md-7">
																<?= (isset($keval->transaction_all['transfer']->sum_amount) ? number_format($keval->transaction_all['transfer']->sum_amount, 2) : '-');?>
															</div>
														</div>
													</td>
													<td class="text-center">
														<a class="btn btn-sm btn-primary" href="<?php echo base_url("{$base_path}/report/viewmutasitransaction/{$keval->seq}"); ?>">
															<i class="fa fa-bars"></i> Details
														</a>
														
													</td>
												</tr>
												<?php
												$for_i += 1;
											}
											?>
										</tbody>
									</table>
									<?php
								} else {
									?>
									<div class="alert alert-danger alert-dismissable">
										<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
										There is no account data on this bank code
									</div>
									<?php
								}
							}
						}
						?>
					</div>
				</div>


			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<?=$collect['pagination'];?>
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->



		
		
		
		<div class="modal fade" id="quick-shop-modal"></div>
	</div>
</div>




<script src="<?= base_url('assets/plugins/datepick/jquery.plugin.js');?>" type="text/javascript"></script>
<link href="<?= base_url('assets/plugins/datepick/jquery.datepick.css');?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/datepick/jquery.datepick.js');?>" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		var datepickParams = {
			showSpeed: 'fast',
			dateFormat: 'yyyy-mm-dd',
			minDate: new Date(2017, 10 - 1, 01),
			maxDate: '0'
		};
		$('#transaction-date-starting').datepick(datepickParams);
		$('#transaction-date-stopping').datepick(datepickParams);
		//$('#inlineDatepicker').datepick({onSelect: showDate});
	});
</script>






