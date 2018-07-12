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
						<a href="<?= base_url($base_path . '/');?>">Home</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-bars"></i>
						<a href="<?= base_url($base_path . '/menu');?>">Menu</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<?php
					if (isset($collect['menu_data']) && count($collect['menu_data'])) {
						foreach ($collect['menu_data'] as $keval) {
							?>
							<li>
								<i class="fa fa-list"></i>
								<a href="<?= base_url($base_path . '/menu/lists/' . $keval->type_code);?>"><?=$keval->type_name;?></a>
								<i class="fa fa-angle-right"></i>
							</li>
							<li>
								<i class="fa fa-eye"></i>
								<?=$keval->menu_title;?>
							</li>
							<li class="btn-group">
								<a href="<?= base_url("{$base_path}/menu/edit/{$keval->seq}");?>" class="btn btn-sm green pull-right">
									Edit <i class="fa fa-pencil"></i>
								</a>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
				<div class="box table-responsive no-padding">
				<?php
				if (isset($collect['menu_data']) && count($collect['menu_data'])) {
					foreach ($collect['menu_data'] as $keval) {
						?>
						<table class="table table-hover">
							<thead>
								<tr>
									<th>Key</th>
									<th>Value</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Menu ID</td>
									<td><?=$keval->seq;?></td>
								</tr>
								<tr>
									<td>Menu Title</td>
									<td><?=$keval->menu_title;?></td>
								</tr>
								<tr>
									<td>Menu Path</td>
									<td><?=$keval->menu_path;?></td>
								</tr>
								<tr>
									<td>Menu Link</td>
									<td>
										<a href="<?= base_url($keval->menu_path);?>">
											<?= base_url($keval->menu_path);?>
										</a>
									</td>
								</tr>
								<tr>
									<td>Menu Type</td>
									<td><?=$keval->type_name;?></td>
								</tr>
								<tr>
									<td>Menu Ordering</td>
									<td><?=$keval->menu_order;?></td>
								</tr>
								<tr>
									<td>Is Parent</td>
									<td>
										<?php
										if ($keval->menu_is_parent === 'Y') {
											?>
											<button type="button" class="btn btn-sm btn-success"><?=$keval->menu_is_parent;?></button>
											<?php
										} else {
											?>
											<button type="button" class="btn btn-sm btn-warning"><?=$keval->menu_is_parent;?></button>
											<?php
										}
										?>
									</td>
								</tr>
								<tr>
									<td>Is Active</td>
									<td>
										<?php
										if ($keval->menu_is_active === 'Y') {
											?>
											<button type="button" class="btn btn-sm btn-success"><?=$keval->menu_is_active;?></button>
											<?php
										} else {
											?>
											<button type="button" class="btn btn-sm btn-warning"><?=$keval->menu_is_active;?></button>
											<?php
										}
										?>
									</td>
								</tr>
							</tbody>
						</table>
						<?php
					}
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
	$('#menu_type').change(function() {
		var menu_type_code = $(this).attr('value');
		var menu_ajax_url = '<?= base_url($base_path . '/menu/list-ajax/');?>' + menu_type_code;
		$("#menu-item-list-data").load(menu_ajax_url, function(responseTxt, statusTxt, xhr) {
			if (statusTxt == "success") {
				
				
			}
		});
		
	});
	<?php
	if (isset($collect['menu_type_data']->type_code)) {
		?>
		$("#menu-item-list-data").load('<?= base_url($base_path . '/menu/list-ajax/' . $collect['menu_type_data']->type_code);?>');
		<?php
	}
	?>
</script>
	
	
	