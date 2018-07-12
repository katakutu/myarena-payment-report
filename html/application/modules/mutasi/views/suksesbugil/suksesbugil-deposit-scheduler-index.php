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
						<a href="<?= base_url($base_path . '/suksesbugil');?>">Deposit</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-list"></i> Lists
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				<div class="box box-primary">
					<div class="box-header">
						<h3 class="box-title">
							<?= (isset($detail_code) ? strtoupper($detail_code) : '-');?>
						</h3>
					</div>
					<div class="box-body table-responsive no-padding">
						<table class="table table-hover">
							<thead>
								<tr>
									<th>No.</th>
									<th>Code</th>
									<th>Bank Name</th>
									<th>Status</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (isset($collect['scheduler_banks'])) {
									$for_i = 1;
									if (is_array($collect['scheduler_banks']) && (count($collect['scheduler_banks']) > 0)) {
										foreach ($collect['scheduler_banks'] as $scheduler_bank) {
											?>
											<tr>
												<td><?=$for_i;?></td>
												<td><?=$scheduler_bank->bank_code;?></td>
												<td>
													<?php
													echo $scheduler_bank->bank_name;
													?>
												</td>
												<td>
													<?php
													if (strtoupper($scheduler_bank->bank_is_active) === 'Y') {
														echo '<button class="btn btn-sm btn-success"><i class="fa fa-check-circle"></i> <span>Enabled</span></button>';
													} else {
														echo '<button class="btn btn-sm btn-danger"><i class="fa fa-ban"></i> <span>Disabled</span></button>';
													}
													?>
												</td>
												<td>
													
													<?php
													if (strtoupper($scheduler_bank->bank_is_active) === 'Y') {
														?>
														<a class="switch-power btn btn-sm btn-danger" title="Switch Off" href="javascript:;" data-switch-seq="<?=$scheduler_bank->seq;?>" data-switch-power="off">
															<i class="fa fa-toggle-off"></i>
														</a>
														<?php
													} else {
														?>
														<a class="switch-power btn btn-sm btn-success" title="Switch On" href="javascript:;" data-switch-seq="<?=$scheduler_bank->seq;?>" data-switch-power="on">
															<i class="fa fa-toggle-on"></i>
														</a>
														<?php
													}
													?>
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
			
			
			
			
			
			
			<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->
	</div>
</div>



<script type="text/javascript">
	$(document).ready(function(){
		$('.switch-power').click(function(el) {
			el.preventDefault();
			var switch_seq = $(this).attr('data-switch-seq');
			switch_seq = parseInt(switch_seq);
			var switch_power = $(this).attr('data-switch-power');
			$.ajax({
				type: 'POST',
				url: '<?= base_url($base_path . '/suksesbugil/switchsbdetailsofsbscheduler');?>/' + switch_seq,
				data: {'power': switch_power},
				success: function(response) {
					var parseStatus = JSON.parse(response);
					if (parseStatus.status == 'SUCCESS') {
						alert("Success enable/disable auto suksesbugil");
					} else {
						alert('Failed to enable/disable auto suksesbugil, please try again');
					}
					window.location.href = '<?= base_url($base_path . '/suksesbugil/sbdetailsofsbscheduler/all');?>';
				}
			});
		});
		
		$('.btn-modal-view-item').click(function(el) {
			el.preventDefault();
			var selected_index = $(this).get(0).href;
			$('#quick-shop-modal').load(selected_index, function() {
				$(this).modal('show');
			});
		});
	});
</script>



