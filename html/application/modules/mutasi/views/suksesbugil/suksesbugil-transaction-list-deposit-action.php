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
						<i class="fa fa-list"></i>
						<a href="<?= base_url($base_path . '/mutasi/listbank');?>">Banks</a>
						<i class="fa fa-angle-right"></i>
					</li>
					<?php
					if (isset($collect['bank_type_data'])) {
						?>
						<li>
							<i class="fa fa-university"></i>
							<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $collect['bank_type_data']->bank_code);?>"><?=$collect['bank_type_data']->bank_name;?></a>
							<i class="fa fa-angle-right"></i>
						</li>
						<?php
					}
					?>
					<li>
						<i class="fa fa-pencil"></i> Action
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<?php
						switch (strtolower($action_type)) {
							case 'delete':
								echo '<i class="fa fa-trash"></i> Delete';
							break;
							case 'move':
							default:
								echo '<i class="fa fa-repeat"></i> Move';
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
			<div class="col-md-12">
				<div class="box">
					<div class="box-header">
						<h2 class="box-title">
							<?php
							switch (strtolower($action_type)) {
								case 'delete':
									echo "Delete Deposit";
								break;
								case 'move':
								default:
									echo "Moving Deposit";
								break;
							}
							?>
						</h2>
					</div>
					<div class="box-body table-responsive no-padding">
						<?php
						if (isset($trans_data->seq)) {
							?>
							<table class="table table-hover">
								<thead>
									<tr>
										<th>Date</th>
										<th>From Account</th>
										<th>From Bank</th>
										<th>To Bank Account</th>
										<th>Amount</th>
										<th>Mutasi Data</th>
										<th>Status</th>
										<th>Insert</th>
										<th>Approved</th>
										<th class="text-center">Action</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?=$trans_data->transaction_date;?></td>
										<td>
											<?php
											$transaction_sb_account = explode('Deposit', $trans_data->transaction_sb_account);
											if (isset($transaction_sb_account[0])) {
												echo $transaction_sb_account[0];
											} else {
												echo "-";
											}
											?>
										</td>
										<td>
											<ul class="list-unstyled">
												<li><?=$trans_data->transaction_from_acc_bank;?></li>
												<li><?=$trans_data->transaction_from_acc_rekening;?></li>
												<li><?=$trans_data->transaction_from_acc_name;?></li>
											</ul>
										</td>
										<td>
											<?php
											if (isset($trans_data->trans_details_bank_to)) {
												echo $trans_data->trans_details_bank_to;
											} else {
												echo "-";
											}
											?>
										</td>
										<td>
											<?php
											echo number_format($trans_data->transaction_amount);
											?>
										</td>
										<td>
											<?php
											if (isset($collect['mb_trans_data'])) {
												if (is_array($collect['mb_trans_data']) && (count($collect['mb_trans_data']) > 0)) {
													foreach ($collect['mb_trans_data'] as $mbVal) {
														?>
														<div class="row">
															<div class="col-md-12">
																<?=$mbVal->transaction_description;?>
															</div>
														</div>
														<?php
													}
												}
											} else {
												echo "-";
											}
											?>
										</td>
										
										<td>
											<?php
											switch (strtolower($trans_data->auto_approve_status)) {
												case 'approved':
													echo '<span class="btn btn-sm btn-primary" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-check"></i></span>';
												break;
												case 'deleted':
													echo '<span class="btn btn-sm btn-default" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-trash"></i></span>';
												break;
												case 'canceled':
													echo '<span class="btn btn-sm btn-default" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-ban"></i></span>';
												break;
												case 'failed':
													echo '<span class="btn btn-sm btn-warning" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-exclamation-triangle"></i></span>';
												break;
												case 'already':
													echo '<span class="btn btn-sm btn-default" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-repeat"></i></span>';
												break;
												case 'waiting':
												default:
													echo '<span class="btn btn-sm btn-warning" title="' . $trans_data->auto_approve_status . '"><i class="fa fa-clock-o"></i></span>';
												break;
											}
											?>
										</td>
										<td><?=$trans_data->transaction_datetime;?></td>
										<td>
											<?php
											if (strlen($trans_data->auto_approve_datetime_executed) > 0) {
												echo $trans_data->auto_approve_datetime_executed;
											} else {
												echo "-";
											}
											?>
										</td>
										<td>
											<?php
											if (in_array($trans_data->auto_approve_status, array('approved', 'failed', 'already'))) {
												?>
												<div class="row">
													<div class="col-md-12">
														<span class="btn btn-sm btn-default">-</span>
													</div>
												</div>
												<?php
											} else {
												?>
												<div class="row">
													<div class="col-md-4">
														<form action="<?= base_url($base_path . '/suksesbugil/depositaction/action/' . $trans_data->seq);?>" role="form" id="form-undo-this-item" method="post">
															<input type="hidden" name="selected_mutasi_seq" value="<?=$trans_data->seq;?>" />
															<input type="hidden" name="selected_mutasi_action" value="undo" />
															<button title="Set Waiting" type="submit" class="btn btn-sm btn-warning"><i class="fa fa-clock-o"></i></button>
														</form>
													</div>
													<div class="col-md-4">
														<form action="<?= base_url($base_path . '/suksesbugil/depositaction/action/' . $trans_data->seq);?>" role="form" id="form-reject-this-item" method="post">
															<input type="hidden" name="selected_mutasi_seq" value="<?=$trans_data->seq;?>" />
															<input type="hidden" name="selected_mutasi_action" value="reject" />
															<button title="Set Rejected to Suksesbugil" type="submit" class="btn btn-sm btn-danger">
																<i class="fa fa-ban"></i>
															</button>
														</form>
													</div>
													<div class="col-md-4">
														<form action="<?= base_url($base_path . '/suksesbugil/depositaction/action/' . $trans_data->seq);?>" role="form" id="form-delete-this-item" method="post">
															<input type="hidden" name="selected_mutasi_seq" value="<?=$trans_data->seq;?>" />
															<input type="hidden" name="selected_mutasi_action" value="delete" />
															<button title="Set Deleted" type="submit" class="btn btn-sm btn-default"><i class="fa fa-trash"></i></button>
														</form>
													</div>
												</div>
												<?php
											}
											?>
										</td>
									</tr>
								</tbody>
							</table>
							<?php
						}
						?>
					</div>
				</div>
			</div>
		</div>
		
		<?php
		if (in_array($trans_data->auto_approve_status, array('approved', 'failed', 'already'))) {
			?>
			<div class="alert alert-danger alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				Deposit data already in status "<?=$trans_data->auto_approve_status;?>" and cannot be moved.
			</div>
			<?php
		} else {
			?>
			<div class="row">
				<!-- BEGIN FORM-->
				<form action="<?= base_url($base_path . '/suksesbugil/depositaction/' . $action_type . '/' . $trans_data->seq);?>" role="form" id="date-range-form" method="post">
					<div class="col-md-8 col-sm-12 col-xs-12">
						<div class="portlet box blue">
							<div class="portlet-title">
								<div class="caption">
									<i class="fa fa-filter"></i> 
									Filter
								</div>
							</div>
							<div class="portlet-body form">
								<ul class="menu nav form-group">
									<li class="row">
										<div class="col-md-6">
											<div class="input-group">
												<div class="col-md-6">
													<label for="transaction-date-starting">Date Start</label>
													<input id="transaction-date-starting" name="transaction_date[starting]" class="form-control" type="text" value="<?= (isset($transaction_date['starting']) ? base_safe_text($transaction_date['starting'], 16) : '');?>" />
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
								<input class="btn btn-primary" type="submit" value="Search" />
							</span>
						</div>	
					</div>
				</form>
				<!-- END FORM-->
			</div>
			
			<div class="row">
				<div class="box col-md-12">
					<?php
					if (strtolower($action_type) === 'move') {
						if (isset($collect['mutasi_data'])) {
							?>
							<div class="box-body table-responsive no-padding">
								<form action="<?= base_url($base_path . '/suksesbugil/depositaction/action/' . $trans_data->seq);?>" id="form-moving-deposit" method="post" role="form">
									<table class="table table-hover">
										<thead>
											<tr>
												<th>Select</th>
												<th>Date</th>
												<th>From Acc</th>
												<th>Amount</th>
												<th>Remark</th>
												<th>Status</th>
												<th>Description</th>
												<th>Insert</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<?php
											if (is_array($collect['mutasi_data']) && (count($collect['mutasi_data']) > 0)) {
												$for_i = 0;
												foreach ($collect['mutasi_data'] as $val) {
													?>
													<tr>
														<td>
															<div class="form-check">
																<label class="radio-inline" for="selected_mutasi_seq<?=$val->seq;?>" role='button'>
																	<input class="form-check-input" name="selected_mutasi_seq" type="radio" id="selected_mutasi_seq<?=$val->seq;?>" value="<?=$val->seq;?>" />
																</label>
															</div>
															<?php
															switch (strtolower($action_type)) {
																case 'delete':
																	echo "<input type='hidden' name='selected_mutasi_action' value='delete' />";
																break;
																case 'move':
																	echo "<input type='hidden' name='selected_mutasi_action' value='move' />";
																break;
																case 'undo':
																default:
																	echo "<input type='hidden' name='selected_mutasi_action' value='undo' />";
																break;
															}
															?>
														</td>
														<td><?=$val->transaction_insert_date;?></td>
														<td><?=$val->transaction_from_acc_name;?></td>
														<td><?= number_format($val->transaction_amount, 2);?></td>
														<td><?=$val->transaction_remark_date;?></td>
														<td>
															<?php
															switch (strtoupper($val->is_deleted)) {
																case 'Y':
																	echo "<span class='btn btn-sm btn-danger'><i class='fa fa-trash'></i></span>";
																break;
																case 'N':
																default:
																	echo "<span class='btn btn-sm btn-default'><i class='fa fa-ban'></i></span>";
																break;
															}
															?>
														</td>
														<td><?=$val->transaction_description;?></td>
														<td><?=$val->transaction_datetime_insert;?></td>
														<td>
															<?php
															switch (strtolower($action_type)) {
																case 'delete':
																	echo '<a type="submit" class="btn btn-sm btn-danger btn-submit-item-modal" href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $val->seq) . '"><i class="fa fa-trash"></i></a>';
																break;
																case 'move':
																	echo '<a type="submit" class="btn btn-sm btn-primary btn-submit-item-modal" href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $val->seq) . '"><i class="fa fa-check"></i></a>';
																break;
																case 'undo':
																default:
																	echo '<a type="submit" class="btn btn-sm btn-warning btn-submit-item-modal" href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $val->seq) . '"><i class="fa fa-undo"></i></a>';
																break;
															}
															?>
														</td>
													</tr>
													<?php
													$for_i++;
												}
											} else {
												?>
												<tr>
													<td colspan="9" class="text-center">
														<div class="alert alert-danger alert-dismissable">
															<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
															There is no mutasi data for your filter criteria
														</div>
													</td>
												</tr>
												<?php
											}
											?>
										</tbody>
										<tfoot>
											<tr>
												<td colspan="9" class="text-center">
													<button type="submit" id="save-this-item" class="btn btn-sm btn-primary">
														<i class="fa fa-save"></i> Move Deposit
													</button>
													<a class="btn btn-sm btn-danger" href="<?= base_url($base_path . '/suksesbugil/deposit/all/' . $trans_data->transaction_from_acc_bank);?>"><i class="fa fa-ban"></i> Cancel</a>
												</td>
											</tr>
										</tfoot>
									</table>
								</form>
							</div>
							<?php
						}
					}
					?>
				</div>
			</div>
			
			<?php
		}
		?>
		
		
		
		
		
		
		
	</div>
	<div class="modal fade" id="quick-shop-modal"></div>
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
	
	
	$(document).ready(function() {
		$('.btn-submit-item-modal').click(function(el) {
			el.preventDefault();
			var selected_index = $(this).get(0).href;
			var modal_view_item = '<?= base_url("{$base_path}/mutasi/mutasiactionprepare/view");?>' + selected_index.substring(selected_index.lastIndexOf('/'));
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




