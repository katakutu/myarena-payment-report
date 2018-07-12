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
									<th>Title</th>
									<th>Description</th>
									<th class="text-center">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (isset($collect['auto_approve_description'])) {
									$for_i = 1;
									if (is_array($collect['auto_approve_description']) && (count($collect['auto_approve_description']) > 0)) {
										foreach ($collect['auto_approve_description'] as $auto_approve_description) {
											?>
											<tr>
												<td><?=$for_i;?></td>
												<td><?=$auto_approve_description->auto_approve_status;?></td>
												<td>
													<?php
													echo $auto_approve_description->auto_approve_status_description;
													?>
												</td>
												<td>
													<a class="btn btn-sm btn-warning" href="<?php echo base_url("{$base_path}/suksesbugil/sbdetails/{$auto_approve_description->auto_approve_status}"); ?>">
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
			
			
			
			
			
			
			<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->
	</div>
</div>







