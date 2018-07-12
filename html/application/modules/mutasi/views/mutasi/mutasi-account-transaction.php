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
						<i class="fa fa-university"></i>
						<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $account_data->bank_code);?>"><?=$account_data->bank_name;?></a>
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-list"></i> Transactions
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<i class="fa fa-th-large"></i>
						<?= (isset($account_data->account_title) ? $account_data->account_title : '');?>
					</li>
					<li class="btn-group pull-right">
						<a id="refresh-transactions-data" href="<?= base_url($base_path . '/mutasi/transactions/' . $account_data->seq);?>" class="btn green pull-right">
							<i class="fa fa-refresh"></i> Refresh
						</a>
					</li>
				</ul>
				<!-- END PAGE TITLE & BREADCRUMB-->
			</div>
		</div>
		<!-- END PAGE HEADER-->
		
		<!-- START MAIN CONTENT -->
		<div class="row">
			<!-- BEGIN FORM-->
			<form action="<?= base_url($base_path . '/mutasi/transactions/' . $account_data->seq);?>" role="form" id="date-range-form" method="post">
				<div class="col-md-4 col-sm-12 col-xs-12">
					<div class="portlet box blue">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-university"></i> Bank Account Rekening
							</div>
						</div>
						<div class="portlet-body form">
							<ul class="menu nav form-group">
								<?php
								if (isset($account_data->rekening_data)) {
									if (is_array($account_data->rekening_data) && count($account_data->rekening_data)) {
										foreach ($account_data->rekening_data as $val) {
											if ($val->account_seq == $account_seq) {
												$li_href_active = 'btn-primary';
											} else {
												$li_href_active = '';
											}
											?>
											<li class="<?=$li_href_active;?>">
												<a href="<?= base_url($base_path . '/mutasi/transactions/' . $account_data->seq);?>">
													<i class="fa fa-bars"></i>
													<span><?=$val->rekening_number;?></span>
												</a>
											</li>
											<?php
										}
									}
								}
								?>
								<li class="row">
									<div class="col-md-12">
										<div class="input-group">
											<div class="row">
												<div class="col-md-6">
													<label for="transaction-date-starting">Date Start</label>
													<input id="transaction-date-starting" name="transaction_date[starting]" class="form-control" type="text" value="<?= (isset($transaction_date['starting'])? base_safe_text($transaction_date['starting'], 16) : '');?>" />
												</div>
												<div class="col-md-6">
													<label for="transaction-date-stopping">Date End</label>
													<input id="transaction-date-stopping" name="transaction_date[stopping]" class="form-control" type="text" value="<?= (isset($transaction_date['stopping'])? base_safe_text($transaction_date['stopping'], 16) : '');?>" />
												</div>
											</div>
											<div class="row">
												<div class="col-md-12 pull-right">
													<span class="input-group-btn">
														<input class="btn btn-sm btn-primary" type="submit" value="Submit" id="submit-this-transaction-date" />
													</span>
												</div>
											</div>
										</div>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xs-6 pull-right">
					<div class="input-group">
						<input name="search_text" class="form-control" placeholder="Search...." type="text" id="search_text" value="<?= (isset($search_text)? base_safe_text($search_text, 64) : '');?>" />
						<span class="input-group-btn">
							<input  class="btn btn-primary" type="submit" value="Search"/>
						</span>
					</div>	
				</div>
			</form>
			<!-- END FORM-->
		</div>
		
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-header">
						<h2 class="box-title">
							Mutasi Transactions : 
							<span><?=$account_data->account_title;?></span>
						</h2>
					</div>
					<div class="box-body table-responsive no-padding">
						<?php
						if (isset($collect['transaction_data']['data'])) {
							if(is_array($collect['transaction_data']['data'])) {
								if (count($collect['transaction_data']['data']) > 0) {
									?>
									<table class="table table-hover">
										<thead>
											<tr>
												<th>No.</th>
												<th>Date</th>
												<th>Type</th>
												<th>Amount</th>
												<th>Description</th>
												<th>Rekening</th>
												<th>Status</th>
												<th>Insert</th>
												<th class="text-center">Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$for_i = 1;
											foreach($collect['transaction_data']['data'] as $keval) {
												?>
												<tr>
													<td><?=$for_i;?></td>
													<td><?=$keval->transaction_date; ?></td>
													<td>
														<?php
														if (strtolower($keval->transaction_type) === 'deposit') {
															?><span class="btn btn-sm btn-success"><i class="fa fa-sign-in"></i> In</span><?php
														} else if (strtolower($keval->transaction_type) === 'transfer') {
															?><span class="btn btn-sm btn-default"><i class="fa fa-sign-out"></i> Out</span><?php
														} else {
															?><span class="btn btn-sm btn-danger"><i class="fa fa-question-circle"></i> ?</span><?php
														}
														?>
													</td>
													<td>
														<?= number_format($keval->transaction_amount, 2);?>
													</td>
													<td>
														<div class="form form-group">
															<?=$keval->transaction_description;?>
														</div>
													</td>
													<td>
														<?php
														try {
															$informasi_rekening = json_decode($keval->transaction_informasi_rekening, true);
														} catch (Exception $ex) {
															throw $ex;
															$informasi_rekening = array(
																'rekening_number'	=> '-',
																'rekening_name'		=> '-',
															);
														}
														?>
														<div class="portlet-body form">
															<ul class="menu nav form-group">
																<li><?=$informasi_rekening['rekening_name'];?></li>
																<li><?=$informasi_rekening['rekening_number'];?></li>
															</ul>
														</div>
													</td>
													<td>
														<?php
														switch (strtolower($keval->transaction_action_status)) {
															case 'transfer':
																echo '<span class="btn btn-sm btn-danger"><i class="fa fa-sign-out"></i> Transfer</span>';
															break;
															case 'approve':
																echo '<span class="btn btn-sm btn-success"><i class="fa fa-check-circle"></i> Approved</span>';
															break;
															case 'update':
																echo '<span class="btn btn-sm btn-warning"><i class="fa fa-clock-o"></i> Waiting</span>';
															break;
															case 'new':
															default:
																echo '<span class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> New</span>';
															break;
														}
														?>
													</td>
													<td>
														<?=$keval->transaction_datetime_insert;?>
													</td>
													<td class="text-center">
														<a class="btn btn-sm btn-warning" href="<?php echo base_url("{$base_path}/menu/view/{$keval->seq}"); ?>">
															<i class="fa fa-eye"></i>
														</a>
														<a class="btn btn-sm btn-info" href="<?php echo base_url("{$base_path}/menu/edit/{$keval->seq}"); ?>">
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
											?>
										</tbody>
									</table>
									<?php
								} else {
									?>
									<div class="alert alert-danger alert-dismissable">
										<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
										There is no transaction data on this date range selected
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
	
	
	$(document).ready(function(){
        $('#refresh-transactions-data').click(function (e) {
            e.preventDefault();            
            var url_fetch = '<?= base_url($base_path . '/mutasi/update-transaction-daily/' . $account_data->seq);?>';
			var transaction_date = {
				'starting': $('#transaction-date-starting').val(),
				'stopping': $('#transaction-date-stopping').val()
			};
			$.ajax({
				type: 'POST',
				url: url_fetch,
				data: {'transaction_date': transaction_date},
				success: function(response) {
					var parseStatus = JSON.parse(response);
					if (parseStatus.status == 'TRUE') {
						alert('Data already updated with following date.');
					} else {
						alert('Something error persist while updating data, please try again.');
					}
					window.location.href = '<?= base_url($base_path . '/mutasi/transactions/' . $account_data->seq);?>';
				}
			});
        });
    });
	
	$(document).ready(function(){
        $('ul.pagination li a').click(function (e) {
            e.preventDefault();            
            var link = $(this).get(0).href;            
            var value = link.substring(link.lastIndexOf('/') + 1);
            $("#date-range-form").attr("action", '<?= base_url($base_path . '/mutasi/transactions/' . $account_data->seq);?>'  + "/" + value);
            $("#date-range-form").submit();
        });
    });
</script>