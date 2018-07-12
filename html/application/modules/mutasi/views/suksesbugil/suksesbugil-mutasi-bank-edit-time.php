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
						<a href="<?= base_url($base_path . '/mutasi/listbank');?>">Bank Instances</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-university"></i>
						<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $collect['bank_type_data']->bank_code);?>">
							<?=$collect['bank_type_data']->bank_name;?>
						</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-pencil"></i> Edit
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
				<div class="box box-primary">
					<div class="box-header">
						<h3 class="box-title">
							<?= (isset($collect['bank_type_data']->bank_name) ? strtoupper($collect['bank_type_data']->bank_name) : '-');?>
						</h3>
					</div>
					<div class="box-body table-responsive no-padding">
						<form id="form-edit-mutasi-time" action="<?= base_url($base_path . '/suksesbugil/mutasibanktimeaction/' . $collect['bank_type_data']->seq);?>" method="post">
							<div class="form-group">
								<label for="bank_restricted_datetime">Allow restricted active time</label>
								<select class="form-control" id="bank_restricted_datetime" aria-describedby="restricted-datetime-help" name="bank_restricted_datetime">
									<?php
									if (strtoupper($collect['bank_type_data']->bank_restricted_datetime) === 'Y') {
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
								<small id="restricted-datetime-help" class="form-text text-muted">Pilih apakah manual pull data dibatasin waktu aktif bank atau tidak</small>
							</div>
							<div class="form-group bootstrap-timepicker timepicker">
								<label for="bank_datetime_starting">Active Time Start</label>
								<input type="text" class="form-control" id="bank_datetime_starting" name="bank_datetime_starting" value="<?=$collect['bank_type_data']->bank_datetime_starting;?>" />
							</div>
							<div class="form-group bootstrap-timepicker timepicker">
								<label for="bank_datetime_stopping">Active Time End</label>
								<input type="text" class="form-control" id="bank_datetime_stopping" name="bank_datetime_stopping" value="<?=$collect['bank_type_data']->bank_datetime_stopping;?>" />
							</div>
							<button type="submit" class="btn btn-primary">Submit</button>
						</form>
						
					</div>
				</div>
			</div>
			
			<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-6 col-xs-6">
				<div class="box-body table-responsive no-padding">
					<table class="table table-hover">
						<thead>
							<tr>
								<th>No.</th>
								<th>Bank Name</th>
								<th>Restricted</th>
								<th>Time Start</th>
								<th>Time End</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php
							if (isset($collect['bank_type'])) {
								$for_i = 1;
								if (is_array($collect['bank_type']) && (count($collect['bank_type']) > 0)) {
									foreach ($collect['bank_type'] as $bank) {
										?>
										<tr>
											<td><?=$for_i;?></td>
											<td>
												<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $bank->bank_code);?>">
													<?=$bank->bank_name;?>
												</a>
											</td>
											<td>
												<?php
												if (strtoupper($bank->bank_restricted_datetime) === 'Y') {
													echo '<span class="text text-success"><i class="fa fa-check"></i> Enable</span>';
												} else {
													echo '<span class="text text-danger"><i class="fa fa-ban"></i> Disable</span>';
												}
												?>
											</td>
											<td><?=$bank->bank_datetime_starting;?></td>
											<td><?=$bank->bank_datetime_stopping;?></td>
											<td>
												<a class="btn btn-sm btn-warning" href="<?= base_url($base_path . '/suksesbugil/mutasibanktime/' . $bank->bank_code);?>">
													<i class="fa fa-pencil"></i>
												</a>
											</td>
										</tr>
										<?php
										$for_i++;
									}
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->
	</div>
</div>



<link href="<?= base_url('assets/plugins/bootstrap-timepicker/bootstrap-timepicker.min.css');?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/bootstrap-timepicker/bootstrap-timepicker.min.js');?>" type="text/javascript"></script>
<script type="text/javascript">
	$(document).ready(function() {
		
		$('#bank_datetime_starting').timepicker({
			minuteStep: 1,
			maxHours: 24,
			showMeridian: false,
			showSeconds: true,
			showInputs: false,
			disableFocus: false,
			defaultTime: '<?=$collect['bank_type_data']->bank_datetime_starting;?>',
		});
		$('#bank_datetime_stopping').timepicker({
			minuteStep: 1,
			maxHours: 24,
			showMeridian: false,
			showSeconds: true,
			showInputs: false,
			disableFocus: false,
			defaultTime: '<?=$collect['bank_type_data']->bank_datetime_stopping;?>',
		});
		
	});
</script>



