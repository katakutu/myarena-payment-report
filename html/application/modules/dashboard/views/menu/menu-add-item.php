<?php
if (!defined('BASEPATH')) { exit('Script cannot access directly.'); }
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
							<a href="<?= base_url($base_path . '/menu');?>">Menu</a>
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-list"></i> Lists
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-plus-circle"></i>
							<?= (isset($collect['menu_type_data']->type_name) ? $collect['menu_type_data']->type_name : 'Add Menu');?>
						</li>
					</ul>
					<!-- END PAGE TITLE & BREADCRUMB-->
				</div>
			</div>
			<!-- END PAGE HEADER-->
			
			<!-- START MAIN CONTENT -->
			<div class="row">
				<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					<div class="box box-primary">
						<div class="box-header">
							<h3 class="box-title">
								Add New Menu
							</h3>
						</div>
						<form id="menu-add" action="<?php echo base_url($base_path . '/menu/additem') ?>" method="post" role="form">
							<div class="box-body no-padding">
								<div class="form-body">
									<div class="form-group required">
										<label for="menu_title">Menu Title</label>
										<input type="text" class="form-control required" id="menu_title"  name="menu_title" maxlength="64" />
									</div>
									<div class="form-group required">
										<label for="menu_path">Menu Path</label>
										<input type="text" class="form-control required" id="menu_path"  name="menu_path" maxlength="128" />
									</div>
									<div class="form-group required">
										<label for="menu_type">Menu Type</label>
										<select class="form-control required" id="menu_type"  name="menu_type">
											<?php
											if (isset($collect['menu_type'])) {
												if (is_array($collect['menu_type']) && count($collect['menu_type'])) {
													foreach ($collect['menu_type'] as $val) {
														?>
														<option value="<?=$val->type_code;?>"><?=$val->type_name;?></option>
														<?php
													}
												}
											}
											?>
										</select>
									</div>
									<div class="form-group required">
										<label for="menu_is_parent">Menu is Parent?</label>
										<select class="form-control required" id="menu_is_parent"  name="menu_is_parent">
											<option value="Y">Yes</option>
											<option value="N" selected="selected">No</option>
										</select>
									</div>
									<div class="form-group required">
										<label for="menu_order">Menu Ordering</label>
										<input type="text" class="form-control required" id="menu_order"  name="menu_order" maxlength="2" placeholder="0" />
									</div>
									<div class="form-group">
										<input type="checkbox" class="form-control" id="menu_is_active" name="menu_is_active" value="Y" /> Menu is Active?
										<label for="menu_is_active"></label>
									</div>
								</div>
							</div>
						
							<div class="box-footer">
								<div class="form-group">
									<button id="save-this-item" type="submit" class="btn btn-primary">Save Menu</button>
									<button id="cancel-this-item" type="button" class="btn btn-default">Cancel</button>
								</div>
							</div>
						</form>
					</div>
					
					
					
				</div>
				
				
				
				
				
				
				<div id="menu-item-list-data" class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					
				</div>
			</div>
			
			<!-- END MAIN CONTENT -->



			
			
			
		</div>
	</div>
	
<script type="text/javascript">
	$('#menu_type').change(function() {
		var menu_type_code = $(this).attr('value');
		var menu_ajax_url = '<?= base_url($base_path . '/menu/list-ajax/');?>' + menu_type_code;
		$("#menu-item-list-data").load(menu_ajax_url, function(responseTxt, statusTxt, xhr) {
			if (statusTxt == "success") {
				
				
			}
		});
		
	});
	<?php
	if (isset($collect['menu_type'][0]->type_code)) {
		?>
		$("#menu-item-list-data").load('<?= base_url($base_path . '/menu/list-ajax/' . $collect['menu_type'][0]->type_code);?>');
		<?php
	}
	?>
</script>
	
	
	