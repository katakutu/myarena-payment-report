<?php
if (!defined('BASEPATH')) { exit('Script cannot access directly.'); }
?>

<div class="portlet box blue">
	<div class="portlet-title">
		<div class="caption">
			<span><?= (isset($collect['menu_type_data']->type_name) ? $collect['menu_type_data']->type_name : '');?></span>
		</div>
	</div>
	<div class="portlet-body no-padding">
		<!-- START MAIN CONTENT -->
		<div class="row">
			<!-- FLASH MESSAGE -->
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
			<!-- //FLASH MESSAGE -->
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-body table-responsive no-padding">
						<?php
						if (isset($collect['menu_items']['data'])) {
							if(is_array($collect['menu_items']['data'])) {
								if (count($collect['menu_items']['data']) > 0) {
									?>
									<table class="table table-hover">
										<thead>
											<tr>
												<th>No.</th>
												<th>Title</th>
												<th>Path</th>
												<th>Link</th>
												<th>Active</th>
												<th>Ordering</th>
												<th class="text-center">Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$for_i = 1;
											foreach($collect['menu_items']['data'] as $keval) {
												?>
												<tr>
													<td><?php echo $for_i; ?></td>
													<td><?php echo $keval->menu_title; ?></td>
													<td><?php echo $keval->menu_path; ?></td>
													<td><a href="<?= base_url($keval->menu_path);?>"><?php echo base_url($keval->menu_path); ?></a></td>
													<td>
														<?php 
														if ($keval->menu_is_active === 'Y') {
															echo '<span class="btn btn-sm btn-success">' . $keval->menu_is_active . '</span>';
														} else {
															echo '<span class="btn btn-sm btn-warning">' . $keval->menu_is_active . '</span>';
														}
														?>
													</td>
													<td><?=$keval->menu_order;?></td>
													<td class="text-center">
														<a class="btn btn-sm btn-warning" href="<?php echo base_url("{$base_path}/menu/view/{$keval->seq}"); ?>">
															<i class="fa fa-eye"></i>
														</a>
														<a class="btn btn-sm btn-info" href="<?php echo base_url("{$base_path}/menu/edit/{$keval->seq}"); ?>">
															<i class="fa fa-pencil"></i>
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
										There is no item menu on this menu type
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
				<?php
				if (isset($collect['pagination'])) {
					echo $collect['pagination'];
				}
				?>
			</div>
		</div>
		<!-- END MAIN CONTENT -->
	</div>
</div>