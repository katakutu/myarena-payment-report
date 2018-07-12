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
						<i class="fa fa-list"></i> Banks
					</li>
					<li class="btn-group pull-right">
						<a href="<?= base_url($base_path . '/mutasi/addbank');?>" class="btn green pull-right">
							<i class="fa fa-plus"></i> Add
						</a>
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-md-6 col-xs-12 pull-right">
				<!-- BEGIN FORM-->
				<form action="<?= base_url($base_path . '/mutasi/listbank');?>" class="form-horizontal" role="form" id="searh-form" method="post">
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
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-body table-responsive no-padding">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Name</th>
									<th>Description</th>
									<th>Website</th>
									<th>Status</th>
									<th class="text-center">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (isset($collect['bank_instances']['data'])) {
									if (is_array($collect['bank_instances']['data']) && (count($collect['bank_instances']['data']) > 0)) {
										foreach ($collect['bank_instances']['data'] as $keval) {
											?>
											<tr>
												<td>
													<?=$keval->bank_name;?>
												</td>
												<td>
													<?=$keval->bank_description;?>
												</td>
												<td>
													<a href="<?=$keval->bank_url_address;?>"><?=$keval->bank_url_address;?></a>
												</td>
												<td>
													<?php
													if (strtoupper($keval->bank_is_active) === 'Y') {
														echo '<button class="btn btn-sm btn-success"><i class="fa fa-check-circle"></i> <span>Enabled</span></button>';
													} else {
														echo '<button class="btn btn-sm btn-danger"><i class="fa fa-ban"></i> <span>Disabled</span></button>';
													}
													?>
												</td>
												<td class="text-center">
													<a class="btn btn-sm btn-default" href="<?= base_url("{$base_path}/mutasi/listaccount/{$keval->bank_code}");?>">
														<i class="fa fa-eye"></i>
													</a>
													<a class="btn btn-sm btn-warning" href="<?= base_url("{$base_path}/mutasi/editbank/{$keval->seq}");?>">
														<i class="fa fa-pencil-square"></i>
													</a>
												</td>
											</tr>		
											<?php
										}
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="col-md-12">
				<?php
				echo $collect['pagination'];
				?>
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->



		
		
		
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
	});
</script>