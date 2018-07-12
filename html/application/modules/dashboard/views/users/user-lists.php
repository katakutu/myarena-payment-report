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
						<?= (isset($title) ? $title : ''); ?>		
					</h3>
					<ul class="page-breadcrumb breadcrumb">
						<li class="btn-group">
							<a href="<?= base_url($base_path . '/users/add');?>" class="btn green pull-right">
								Add Data<i class="fa fa-plus"></i>
							</a>
						</li>
						<li>
							<i class="fa fa-users"></i>
							<a href="<?= base_url($base_path . '/users');?>">Users</a>
							<i class="fa fa-angle-right"></i>
						</li>
						<li>
							<i class="fa fa-user"></i><?= (isset($title) ? $title : ''); ?>
						</li>
					</ul>
					<!-- END PAGE TITLE & BREADCRUMB-->
				</div>
			</div>
			<!-- END PAGE HEADER-->
		
			<div class="row">
				<div class="col-xs-12 col-sm-12 col-md-6">
					<div class="portlet box blue">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-info-circle"></i> User Roles
							</div>
							<div class="tools">
								<a class="expand" href="javascript:;"></a>
							</div>
						</div>
						<div class="portlet-body form display-hide">
							<div class="form-body">
								<div class="form-group">
									<?php
									if (isset($collect['roles'])) {
										if (is_array($collect['roles']) && count($collect['roles'])) {
											foreach ($collect['roles'] as $val) {
												?>
												<span class="form-control"><i class="fa fa-check"></i> <?=$val->role_name;?></span>
												<?php
											}
										}
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- BEGIN FORM-->
				<div class="col-md-6 pull-right">
					<form action="<?= base_url($base_path . '/users/lists');?>" class="form-horizontal" role="form" id="searh-form" method="post">
						<div class="input-group">
							<input name="search_text" class="form-control" placeholder="Search User" type="text" id="search_text" value="<?=$search_text;?>" />
							<span class="input-group-btn">
								<input  class="btn btn-primary" type="submit" value="Search"/>
							</span>
						</div>
					</form>	
				</div>
				<!-- END FORM-->
			</div>
			
			<div class="row">
				<div class="col-md-12">
					<div class="table-responsive">
						
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-12 col-xs-12">
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Users List</h3>
							
						</div>
						<div class="box-body table-responsive no-padding">
							<table class="table table-hover">
								<tr>
									<th>No.</th>
									<th>Email</th>
									<th>Full Name</th>
									<th>Role</th>
									<th class="text-center">Actions</th>
								</tr>
								<?php
								if(!empty($collect['users'])) {
									$for_i = 1;
									foreach($collect['users'] as $keval) {
										?>
										<tr>
											<td><?php echo $for_i; ?></td>
											<td><?php echo $keval->account_email; ?></td>
											<td><?php echo $keval->account_fullname; ?></td>
											<td><?php echo $keval->role_name; ?></td>
											<td class="text-center">
												<a class="btn btn-sm btn-warning" href="<?php echo base_url("{$base_path}/users/view/{$keval->seq}"); ?>">
													<i class="fa fa-eye"></i>
												</a>
												<a class="btn btn-sm btn-info" href="<?php echo base_url("{$base_path}/users/edit/{$keval->seq}"); ?>">
													<i class="fa fa-pencil"></i>
												</a>
												<a class="btn btn-sm btn-danger" href="javascript:;" data-userid="<?=$keval->seq;?>" onclick="javascript:deleteuser('<?=$keval->seq;?>');">
													<i class="fa fa-trash"></i>
												</a>
											</td>
										</tr>
										<?php
										$for_i += 1;
									}
								}
								?>
							</table>
						</div>
					</div>
				</div>
			</div>
			
			
			
						
			<div class="row">
				<div class="col-md-12">
					<?=$collect['pagination'];?>
				</div>
			</div>
			
		</div>
	</div>
	<!-- END CONTENT -->
	
	
	
<script type="text/javascript">
	function deleteuser(user_seq) {
		var user_seq = parseInt(user_seq);
		var currentRow = $(this);
		var actionUrl = '<?= base_url("{$base_path}/users/deleteaction");?>';
		var confirmation = confirm("Are you sure to delete this user?");
		if (confirmation) {
			$.ajax({
				type : "POST",
				dataType : "json",
				url : actionUrl,
				data : { 'user_seq' : user_seq },
				success: function(response) {
					currentRow.parents('tr').remove();
					if (response.status = true) { 
						alert("User successfully deleted");
						location.reload(true);
					} else if (response.status = false) {
						alert("User deletion failed"); 
					} else { 
						alert("Access denied..!"); 
					}
				}
			});
		}
	}
</script>
	
	
	