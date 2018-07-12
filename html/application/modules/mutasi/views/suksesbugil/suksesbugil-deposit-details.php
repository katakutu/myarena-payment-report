<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>
<link href="<?= base_url('assets/plugins/bootstrap-summernote/summernote.css');?>" rel="stylesheet">
<script src="<?= base_url('assets/plugins/bootstrap-summernote/summernote.min.js');?>"></script>

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
						<i class="fa fa-pencil-square"></i> 
						<a href="<?= base_url($base_path . '/suksesbugil/sbdetails/all');?>">Edit</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<?php
						switch (strtolower($collect['auto_approve_description']->auto_approve_status)) {
							case 'waiting':
								echo '<i class="fa fa-clock-o"></i> ' . ucfirst($detail_code);
							break;
							case 'approved':
								echo '<i class="fa fa-check"></i> ' . ucfirst($detail_code);
							break;
							case 'already':
								echo '<i class="fa fa-repeat"></i> ' . ucfirst($detail_code);
							break;
							case 'deleted':
								echo '<i class="fa fa-trash"></i> ' . ucfirst($detail_code);
							break;
							case 'failed':
								echo '<i class="fa fa-exclamation-triangle"></i> ' . ucfirst($detail_code);
							break;
							case 'all':
							default:
								echo '<i class="fa fa-table"></i> ' . ucfirst($detail_code);
							break;
						}
						?>
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
							<?= $collect['auto_approve_description']->auto_approve_status;?>
						</h3>
					</div>
					<?php
					if (isset($collect['auto_approve_description'])) {
						?>
						<form id="sb-details-edit" action="<?php echo base_url($base_path . '/suksesbugil/sbdetailsedit/' . $collect['auto_approve_description']->seq) ?>" method="post" role="form">
							<div class="box-body no-padding">
								<div class="form-body">
									<div class="form-group required">
										<label for="auto_approve_status_description">Description Details</label>
										<textarea id="auto_approve_status_description" class="form-control required" name="auto_approve_status_description"><?= base_safe_text($collect['auto_approve_description']->auto_approve_status_description, 10240);?></textarea>
									</div>
								</div>
							</div>
						
							<div class="box-footer">
								<div class="form-group">
									<button id="save-this-item" type="submit" class="btn btn-primary">Edit Details</button>
									<a id="cancel-this-item" class="btn btn-default" href="<?= base_url($base_path . '/suksesbugil');?>">Cancel</a>
								</div>
							</div>
						</form>
						<?php
					}
					?>
				</div>
				
				
				
			</div>
			
			
			
			
			
			
			<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				
			</div>
		</div>
		
		<!-- END MAIN CONTENT -->
	</div>
</div>


<script type="text/javascript">
	$(document).ready(function() {
		$('#auto_approve_status_description').summernote({
			placeholder: 'Text here..',
			tabsize: 2,
			height: 100,
			toolbar: [
				// [groupName, [list of button]]
				['style', ['bold', 'italic', 'underline', 'clear']],
				['font', ['fontsize', 'fontname', 'color']],
				['codeview']
			]
		});
	});
</script>






