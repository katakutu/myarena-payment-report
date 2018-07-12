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
						<a href="<?= base_url($base_path . '/mutasi/edititem/' . $account_data->seq);?>" class="btn green pull-right">
							<i class="fa fa-pencil"></i> Edit
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
			<form action="<?= base_url($base_path . '/mutasi/showmutasi/' . $show_type . '/' . $account_data->seq);?>" role="form" id="date-range-form" method="post">
				<div class="col-md-8 col-sm-12 col-xs-12">
					<div class="portlet box blue">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-info"></i> 
								Rekening: <?=$account_data->account_title;?>
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
									<div class="col-md-6">
										<div class="input-group">
											<div class="col-md-6">
												<label for="transaction-date-starting">Date Start</label>
												<input id="transaction-date-starting" name="transaction_date[starting]" class="form-control" type="text" value="<?= (isset($transaction_date['starting'])? base_safe_text($transaction_date['starting'], 16) : '');?>" />
											</div>
											<div class="col-md-6">
												<label for="transaction-date-stopping">Date End</label>
												<input id="transaction-date-stopping" name="transaction_date[stopping]" class="form-control" type="text" value="<?= (isset($transaction_date['stopping'])? base_safe_text($transaction_date['stopping'], 16) : '');?>" />
											</div>
										</div>
									</div>
									<div class="col-md-4">
										<div class="input-group">
											<div class="col-md-12">
												<label for="transaction-search-amount">Amount</label>
												<input name="transaction_search_amount" class="form-control" placeholder="Amount..." type="text" id="transaction-search-amount" value="<?= (isset($transaction_search_amount)? (((int)$transaction_search_amount > 0) ? base_safe_text($transaction_search_amount, 64) : '') : '');?>" />
											</div>
										</div>
									</div>
									<div class="col-md-2 pull-right">
										<label for="submit-this-transaction-date"> &nbsp; </label>
										<span class="input-group-btn">
											<input class="btn btn-primary" type="submit" value="Submit" id="submit-this-transaction-date" />
										</span>
									</div>
								</li>
								<li class="row">
									<div class="col-md-12">
										&nbsp;
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-xs-12 pull-right">
					<div class="input-group">
						<input name="search_text" class="form-control" placeholder="Search...." type="text" id="search_text" value="<?= (isset($search_text)? base_safe_text($search_text, 64) : '');?>" />
						<span class="input-group-btn">
							<input class="btn btn-primary" type="submit" value="Search"/>
						</span>
					</div>	
				</div>
			</form>
			<!-- END FORM-->
		</div>
		
		
		
		<div class="row">
			<?php
			if (isset($collect['show_types'])) {
				if (is_array($collect['show_types']) && (count($collect['show_types']) > 0)) {
					foreach ($collect['show_types'] as $type) {
						if ($type === $show_type) {
							$a_href_active = "btn btn-sm btn-info active";
						} else {
							$a_href_active = "btn btn-sm btn-default";
						}
						?>
						<div class="col-md-2 col-xs-4">
							<a href="<?= base_url("{$base_path}/mutasi/showmutasi/{$type}/{$account_data->seq}");?>">
								<span class="<?=$a_href_active;?>">
									<?php
									$type_explode = explode("-", $type);
									switch (strtolower($type_explode[0])) {
										case 'all':
										default:
											echo '<i class="fa fa-bars"></i> ';
										break;
										case 'new':
											echo '<i class="fa fa-plus-circle"></i> ';
										break;
										case 'unprocessed':
											echo '<i class="fa fa-asterisk"></i> ';
										break;
										case 'approved':
											echo '<i class="fa fa-check"></i> ';
										break;
										case 'already':
											echo '<i class="fa fa-repeat"></i> ';
										break;
										case 'deleted':
											echo '<i class="fa fa-trash"></i> ';
										break;
									}
									if (isset($type_explode[1])) {
										echo ucfirst($type_explode[0]) . " " . ucfirst($type_explode[1]);
									} else {
										echo ucfirst($type_explode[0]);
									}
									?>
								</span>
							</a>
						</div>
						<?php
					}
				}
			}
			?>
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
					<div class="box-body table-responsive table-border">
						<table class="table table-hover">
							<thead>
								<tr>
									<th class="alert alert-sm alert-success"><i class="fa fa-sign-in"></i> Incoming</th>
									<th class="alert alert-sm alert-danger"><i class="fa fa-sign-out"></i> Outgoing</th>
									<th class="alert alert-sm alert-info">Total Mutasi</th>
									<th class="alert alert-sm alert-warning">
										<a id="pull-mutasi-data" class="btn btn-info" href="javascript:;">
											<i class="fa fa-download"></i> Pull Data
										</a>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<?php
									$summaries_i = 0;
									if (isset($collect['transaction_data']['summaries'])) {
										$sum_total_value = array();
										if (is_array($collect['transaction_data']['summaries']) && (count($collect['transaction_data']['summaries']) > 0)) {
											foreach ($collect['transaction_data']['summaries'] as $sumval) {
												?>
												<td>
													<div class="row">
														<div class="col-md-4">Unit</div>
														<div class="col-md-8 pull-right"><?= number_format($sumval->count_value);?></div>
													</div>
													<div class="row">
														<div class="col-md-4">Amount</div>
														<div class="col-md-8 pull-right"><?= number_format($sumval->sum_value);?></div>
													</div>
												</td>
												<?php
												$sum_total_value[] = $sumval->sum_value;
												$summaries_i += 1;
											}
											if ($summaries_i !== 2) {
												?>
												<td>
													<div class="row">
														<div class="col-md-4">Unit</div>
														<div class="col-md-8 pull-right">-</div>
													</div>
													<div class="row">
														<div class="col-md-4">Amount</div>
														<div class="col-md-8 pull-right">-</div>
													</div>
												</td>
												<?php
											}
											?>
											<td>
												<div class="row"><div class="col-md-12"> &nbsp; </div></div>
												<div class="row">
													<div class="col-md-12">
														<?php 
														if (isset($sum_total_value[0]) && isset($sum_total_value[1])) {
															echo number_format($sum_total_value[0] - $sum_total_value[1]);
														} else {
															echo "-";
														}
														?>
													</div>
												</div>
											</td>
											<?php
										}
									}
									?>
									<td>
										<div class="row">
											<div class="col-md-12"> &nbsp; </div>
										</div>
									</td>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<td colspan="4" class="alert alert-sm alert-default">
										&nbsp;
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
					<div class="box-body table-responsive no-padding">
						<form id="form-managing-mutasi-data" action="<?= base_url($base_path . "/mutasi/massedit/{$account_data->seq}");?>" method="post">
						<?php
						if (isset($collect['transaction_data']['data'])) {
							if(is_array($collect['transaction_data']['data'])) {
								if (count($collect['transaction_data']['data']) > 0) {
									?>
									<table class="table table-hover" id="transaction-data-lists">
										<thead>
											<tr>
												<th width="10%">
													Tick
													<div class="form-check form-check-inline">
														
														<label class="form-check-label" for="select-all-checkbox">Select All</label>
														<input id="select-all-checkbox" type="checkbox" class="form-check-input form-check-input-sm" />
													</div>
												</th>
												<th width="5%">Date</th>
												<th width="3%">Type</th>
												<th width="5%" style="max-width:120px;">Acc Name</th>
												<th width="5%">Amount</th>
												<th width="10%" style="max-width:160px;">Description</th>
												<th width="5%">Rekening</th>
												<th width="5%">Status</th>
												<th width="10%">Insert</th>
												<th width="6%">Saldo</th>
												<th width="2%">Deposit</th>
												<th style="max-width:120px;">Keterangan</th>
												<!--
												<th class="text-center">Action</th>
												-->
											</tr>
										</thead>
										<tbody>
											<?php
											$for_i = 1;
											foreach($collect['transaction_data']['data'] as $keval) {
												?>
												<tr>
													<td>
														<div class="form-check form-check-inline">
															<label class="form-check-label" for="transactionseq<?=$keval->seq;?>">
																<?=$for_i;?>
															</label>
															<input class="form-check-input form-check-input-sm group" type="checkbox" id="transactionseq<?=$keval->seq;?>" name="transactionseq[]" value="<?=$keval->seq;?>" />
														</div>
													</td>
													<td><?=$keval->transaction_date; ?></td>
													<td>
														<?php
														if (strtolower($keval->transaction_type) === 'deposit') {
															?><span class="btn btn-sm text-success"><i class="fa fa-sign-in"></i></span><?php
														} else if (strtolower($keval->transaction_type) === 'transfer') {
															?><span class="btn btn-sm text-default"><i class="fa fa-sign-out"></i></span><?php
														} else {
															?><span class="btn btn-sm text-danger"><i class="fa fa-question-circle"></i> ?</span><?php
														}
														?>
													</td>
													<td style="max-width:120px;">
														<div class="text-truncate" style="display:block;overflow-wrap:break-word;">
															<?=$keval->transaction_from_acc_name;?>
														</div>
													</td>
													<td>
														<?= number_format($keval->transaction_amount, 2);?>
													</td>
													<td style="max-width:160px;">
														<div class="text-truncate" style="display:block;overflow-wrap:break-word;">
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
															<ul class="nav list-unstyled">
																<li><?=$informasi_rekening['rekening_name'];?></li>
																<li><?=$informasi_rekening['rekening_number'];?></li>
															</ul>
														</div>
													</td>
													<td>
														<?php
														switch (strtoupper($keval->is_deleted)) {
															case 'Y':
																echo '<span class="text-sm text-default"><i class="fa fa-trash"></i></span>';
															break;
															case 'N':
																switch (strtolower($keval->transaction_action_status)) {
																	case 'transfer':
																		echo '<span class="text-sm text-danger"><i class="fa fa-sign-out"></i></span>';
																	break;
																	case 'approve':
																		echo '<span class="text-sm text-success"><i class="fa fa-check-circle"></i></span>';
																	break;
																	case 'update':
																		echo '<span class="text-sm text-warning"><i class="fa fa-clock-o"></i></span>';
																	break;
																	case 'new':
																	default:
																		echo '<span class="text-sm text-primary"><i class="fa fa-plus-circle"></i></span>';
																	break;
																}
															break;
														}
														?>
													</td>
													<td>
														<?=$keval->transaction_datetime_insert;?>
													</td>
													<td>
														<?= (isset($keval->actual_rekening_saldo) ? number_format($keval->actual_rekening_saldo, 2) : '-');?>
													</td>
													<td>
														<?php
														if (strtoupper($keval->transaction_code === 'DB')) {
															echo '<span class="text-sm text-default"><i class="fa fa-close"></i></span>';
														} else {
															if ((int)$keval->auto_deposit_trans_seq === 0) {
																if (strtolower($keval->transaction_action_status) === 'new') {
																	echo '<span class="text-sm text-default"><i class="fa fa-clock-o"></i></span>';
																} else {
																	switch ($keval->transaction_action_status) {
																		case 'failed':
																			echo '<span class="text-sm text-warning"><i class="fa fa-exclamation-triangle"></i></span>';
																		break;
																		case 'approve':
																			echo '<span class="text-sm text-success"><i class="fa fa-check"></i></span>';
																		break;
																		case 'already':
																			echo '<span class="text-sm text-danger"><i class="fa fa-check-circle"></i></span>';
																		break;
																		case 'update':
																		case 'waiting':
																		default:
																			echo '<span class="text-sm text-info"><i class="fa clock-o"></i></span>';
																		break;
																	}
																}
															} else {
																?>
																<a class="btn-modal-view-item" href="<?= base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->seq);?>">
																	<?php
																	switch ($keval->transaction_action_status) {
																		case 'failed':
																			echo '<span class="text-sm text-warning"><i class="fa fa-eye"></i></span>';
																		break;
																		case 'approve':
																			echo '<span class="text-sm text-success"><i class="fa fa-eye"></i></span>';
																		break;
																		case 'already':
																			echo '<span class="text-sm text-danger"><i class="fa fa-eye"></i></span>';
																		break;
																		case 'update':
																		case 'new':
																		case 'waiting':
																		default:
																			echo '<span class="text-sm text-info"><i class="fa fa-eye"></i></span>';
																		break;
																	}
																	?>
																</a>
																<?php
															}
														}
														?>
													</td>
													<td style="max-width:120px;">
														<?php
														echo $keval->auto_deposit_trans_response;
														?>
													</td>
													<!--
													<td class="text-center">
														<button class="btn btn-sm btn-info" type="button">
															<i class="fa fa-pencil"></i>
														</button>
														<button class="btn btn-sm btn-danger" type="button">
															<i class="fa fa-trash"></i>
														</button>
													</td>
													-->
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
							?>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label for="massedit-picked">Set Transaction Status</label>
										<select class="form-control" id="massedit-picked" name="auto_deposit_trans_response">
											<?php
											foreach ($collect['mutasi_actions'] as $mutasi_action) {
												?><option value="<?=$mutasi_action;?>"><?= ucfirst($mutasi_action);?></option><?php
											}
											?>
										</select>
									</div>
									<div class="form-group">
										<button type="submit" id="massedit-submit" class="btn btn-primary">
											<i class="fa fa-check"></i> Submit
										</button>
										<a id="massedit-cancel" class="btn btn-default" href="#">
											<i class="fa fa-ban"></i> Cancel
										</a>
									</div>
								</div>
								<div class="col-md-8">
								
								</div>
							</div>
							<?php
						}
						?>
						</form>
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



		
		
		<div class="modal fade" id="quick-shop-modal" data-backdrop="static"></div>
	</div>
</div>




<script src="<?= base_url('assets/plugins/datepick/jquery.plugin.js');?>" type="text/javascript"></script>
<link href="<?= base_url('assets/plugins/datepick/jquery.datepick.css');?>" rel="stylesheet" />
<script src="<?= base_url('assets/plugins/datepick/jquery.datepick.js');?>" type="text/javascript"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var datepickParams = {
			showSpeed: 'fast',
			dateFormat: 'yyyy-mm-dd',
			minDate: new Date(2017, 10 - 1, 01),
			maxDate: '0'
		};
		$('#transaction-date-starting').datepick(datepickParams);
		$('#transaction-date-stopping').datepick(datepickParams);
		//$('#inlineDatepicker').datepick({onSelect: showDate});
		
		// Checkbox Tick
		$('#select-all-checkbox').on('click',function(){
			if(this.checked){
				$('.group').each(function(){
					this.checked = true;
				});
			} else {
				 $('.group').each(function(){
					this.checked = false;
				});
			}
		});
		$('.group').on('click',function(){
			if($('.group:checked').length == $('.group').length){
				$('#select-all-checkbox').prop('checked',true);
			}else{
				$('#select-all-checkbox').prop('checked',false);
			}
		});
		
		/*
		$('#select-all-checkbox').click(function() {
			if ($(this).attr("checked")) {
				$('.checker').find('span').addClass('checked');  
				$('.checker').find('input').prop('checked',true);        
			} else {
				$('.checker').find('span').removeClass('checked');
			} 
		});
		
		$('#select-all-checkbox').click(function(){
			if($(this).is(':checked')) {
				
				$('.group').attr("checked",true);
			} else {
				$('.group').attr("checked",false);
			}
		});
		*/
		// PULL MUTASI
        $('#pull-mutasi-data').click(function (e) {
            e.preventDefault();            
            var url_fetch = '<?= base_url($base_path . '/mutasi/pull-mutasi-data/' . $account_data->seq);?>';
			var transaction_date = {
				'starting': $('#transaction-date-starting').val(),
				'stopping': $('#transaction-date-stopping').val()
			};
			$.ajax({
				type: 'POST',
				url: url_fetch,
				data: {
					'transaction_date': transaction_date,
					'push_to_database': 'TRUE'
				},
				success: function(response) {
					$('#quick-shop-modal').html(response);
					$('#quick-shop-modal').modal('show');
				}
			});
        });
    });
	
	
	
	$(document).ready(function(){
        $('ul.pagination li a').click(function (e) {
            e.preventDefault();            
            var link = $(this).get(0).href;            
            var value = link.substring(link.lastIndexOf('/') + 1);
            $("#date-range-form").attr("action", '<?= base_url($base_path . '/mutasi/showmutasi/' . $show_type . '/' . $account_data->seq);?>'  + "/" + value);
            $("#date-range-form").submit();
        });
		
		$('.btn-modal-view-item').click(function(el) {
			el.preventDefault();
			var selected_index = $(this).get(0).href;
			var modal_view_item = '<?= base_url("{$base_path}/mutasi/mutasiaction/view");?>' + "/" + selected_index.substring(selected_index.lastIndexOf('/'));
			$('#quick-shop-modal').load(modal_view_item, function() {
				$(this).modal({
					show: true,
					keyboard: false,
					backdrop: 'static'
				});
				
			});
		});
		
    });
</script>



