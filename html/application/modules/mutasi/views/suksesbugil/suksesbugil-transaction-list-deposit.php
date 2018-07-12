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
						<i class="fa fa-file"></i> Deposit
						<i class="fa fa-angle-right"></i>
					</li>
					<li>
						<?php
						switch (strtolower($this_method)) {
							case 'waiting':
								echo '<i class="fa fa-clock-o"></i> ' . ucfirst($this_method);
							break;
							case 'approved':
								echo '<i class="fa fa-check"></i> ' . ucfirst($this_method);
							break;
							case 'already':
								echo '<i class="fa fa-repeat"></i> ' . ucfirst($this_method);
							break;
							case 'deleted':
								echo '<i class="fa fa-trash"></i> ' . ucfirst($this_method);
							break;
							case 'failed':
								echo '<i class="fa fa-exclamation-triangle"></i> ' . ucfirst($this_method);
							break;
							case 'all':
							default:
								echo '<i class="fa fa-table"></i> ' . ucfirst($this_method);
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
				<div class="portlet box blue">
					<div class="portlet-title">
						<?= ucfirst($this_method);?>
					</div>
					<div class="portlet-body">
						<?php
						if (isset($collect['auto_approve_description']->auto_approve_status_description)) {
							echo $collect['auto_approve_description']->auto_approve_status_description;
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<?php
			if (isset($collect['bank_type_data'])) {
				$form_filter_url = base_url($base_path . '/suksesbugil/deposit/' . $this_method . '/' . $collect['bank_type_data']->bank_code);
			} else {
				$form_filter_url = base_url($base_path . '/suksesbugil/deposit/' . $this_method . '/' . 'all');
			}
			?>
			<!-- BEGIN FORM-->
			<form action="<?=$form_filter_url;?>" role="form" id="date-range-form" method="post">
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
							<input class="btn btn-primary" type="submit" value="Search" />
						</span>
					</div>	
				</div>
			</form>
			<!-- END FORM-->
		</div>
		
		
		
		
		
		<div class="row">
			<?php
			$bank_header_list = array();
			$for_bank_header_i = 0;
			$bank_header_list[$for_bank_header_i] = new stdClass();
			$bank_header_list[$for_bank_header_i]->bank_code = 'all';
			foreach ($collect['bank_type'] as $val) {
				$for_bank_header_i++;
				$bank_header_list[$for_bank_header_i] = $val;
			}
			foreach ($bank_header_list as $bank_header_val) {
				if ($bank_header_val->bank_code == $bank_code) {
					$a_href_active = 'btn btn-primary';
				} else {
					$a_href_active = 'btn btn-default';
				}
				?>
				<div class="col-md-2 col-xs-4">
					<a class="<?=$a_href_active;?>" href="<?= base_url($base_path . "/suksesbugil/deposit/{$this_method}/" . $bank_header_val->bank_code);?>">
						<i class="fa fa-university"></i>
						<span><?= strtoupper($bank_header_val->bank_code);?></span>
					</a>
				</div>
				<?php
			}
			?>
		</div>
		
		
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<div class="box">
					<div class="box-header">
						<h2 class="box-title">
							Accounts : 
							<?php if (isset($collect['bank_type_data'])) { ?>
								<span><?=$collect['bank_type_data']->bank_name;?></span><?php
							} else { ?>
								<span>All</span><?php
							} ?>
						</h2>
					</div>
					<div class="box-body table-responsive table-border">
						<table class="table table-hover">
							<thead>
								<tr>
									<th class="alert alert-sm alert-info"><i class="fa fa-rss"></i> Total Unit</th>
									<th class="alert alert-sm alert-success"><i class="fa fa-money"></i> Total Amount</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<?php
									if (isset($collect['bank_deposit']['summaries'])) {
										if (is_array($collect['bank_deposit']['summaries']) && (count($collect['bank_deposit']['summaries']) > 0)) {
											foreach ($collect['bank_deposit']['summaries'] as $sumval) {
												?>
												<td>
													<?= number_format($sumval->count_value);?>
												</td>
												<td>
													<?= number_format($sumval->amount_value);?>
												</td>
												<?php
											}
										}
									}
									?>
								</tr>
							</tbody>
							<tfoot>
								<tr>
									<td colspan="3" class="alert alert-sm alert-info">
										&nbsp;
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
					<div class="box-body table-responsive no-padding">
						<?php
						if (isset($collect['bank_deposit']['data'])) {
							if(is_array($collect['bank_deposit']['data'])) {
								if (count($collect['bank_deposit']['data']) > 0) {
									?>
									<table class="table table-hover">
										<thead>
											<tr>
												<th>No.</th>
												<th>Date</th>
												<th>From Account</th>
												<th>From Bank</th>
												<th>To Bank</th>
												<th>To Account</th>
												<th>Amount</th>
												<th>Status</th>
												<th>Insert</th>
												<th>Approved</th>
												<th>Apprv. By</th>
												<th class="text-center">Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$for_i = 1;
											foreach($collect['bank_deposit']['data'] as $keval) {
												?>
												<tr>
													<td><?=$for_i;?></td>
													<td><?=$keval->transaction_date;?></td>
													<td>
														<?php
														$transaction_sb_account = explode('Deposit', $keval->transaction_sb_account);
														if (isset($transaction_sb_account[0])) {
															echo $transaction_sb_account[0];
														} else {
															echo "-";
														}
														?>
													</td>
													<td>
														<ul class="list-unstyled">
															<li><?=$keval->transaction_from_acc_bank;?></li>
															<li><?=$keval->transaction_from_acc_rekening;?></li>
															<li><?=$keval->transaction_from_acc_name;?></li>
														</ul>
													</td>
													<td>
														<?php
														if (isset($keval->bank_data->bank_code)) {
															?><a href="<?= base_url($base_path . '/mutasi/listaccount/' . $keval->bank_data->bank_code);?>"><?=$keval->bank_data->bank_name;?></a><?php
														} else {
															echo "-";
														}
														?>
													</td>
													<td>
														<?php
														if (isset($keval->bank_account_data)) {
															if (is_array($keval->bank_account_data) && (count($keval->bank_account_data) > 0)) {
																foreach ($keval->bank_account_data as $accVal) {
																	?>
																	<a href="<?= base_url($base_path . '/mutasi/showmutasi/all/' . $accVal->seq);?>">
																		<?=$accVal->account_title;?>
																	</a>
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
														echo number_format($keval->transaction_amount);
														?>
													</td>
													<td>
														<?php
														switch (strtolower($keval->auto_approve_status)) {
															case 'approved':
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-success" title="' . $keval->auto_approve_status . '"><i class="fa fa-check"></i></a>';
															break;
															case 'deleted':
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-danger" title="' . $keval->auto_approve_status . '"><i class="fa fa-trash"></i></a>';
															break;
															case 'canceled':
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-default" title="' . $keval->auto_approve_status . '"><i class="fa fa-ban"></i></a>';
															break;
															case 'failed':
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-warning" title="' . $keval->auto_approve_status . '"><i class="fa fa-exclamation-triangle"></i></a>';
															break;
															case 'already':
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-default" title="' . $keval->auto_approve_status . '"><i class="fa fa-repeat"></i></a>';
															break;
															case 'waiting':
															default:
																echo '<a href="' . base_url($base_path . '/mutasi/mutasiaction/view/' . $keval->auto_approve_mutasi_trans_seq) . '" class="btn-mutasi-detail text-warning" title="' . $keval->auto_approve_status . '"><i class="fa fa-clock-o"></i></a>';
															break;
														}
														?>
													</td>
													<td><?=$keval->transaction_datetime;?></td>
													<td>
														<?php
														if (strlen($keval->auto_approve_datetime_executed) > 0) {
															echo $keval->auto_approve_datetime_executed;
														} else {
															echo "-";
														}
														?>
													</td>
													<td>
														<?php
														if (isset($keval->auto_approve_log_by_account_email)) {
															echo $keval->auto_approve_log_by_account_email;
														} else {
															echo "-";
														}
														?>
													</td>
													
													<td class="text-center">
														<?php
														switch (strtolower($keval->auto_approve_status)) {
															case 'approved':
																?>
																<a class="btn btn-sm btn-danger btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/delete/{$keval->seq}"); ?>">
																	<i class="fa fa-trash"></i>
																</a>
																<?php
															break;
															case 'deleted':
																?>
																<a class="btn btn-sm btn-default btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/undo/{$keval->seq}"); ?>" title="Undo delete">
																	<i class="fa fa-undo"></i>
																</a>
																<a class="btn btn-sm btn-success btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/move/{$keval->seq}"); ?>" title="Manual accept deposit">
																	<i class="fa fa-check"></i>
																</a>
																<?php
															break;
															case 'canceled':
															case 'failed':
																?>
																<a class="btn btn-sm btn-default btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/undo/{$keval->seq}"); ?>">
																	<i class="fa fa-undo"></i>
																</a>
																<?php
															break;
															case 'waiting':
															default:
																?>
																<a class="btn btn-sm btn-success btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/move/{$keval->seq}"); ?>">
																	<i class="fa fa-check"></i>
																</a>
																<a class="btn btn-sm btn-danger btn-modal-view-item" href="<?php echo base_url("{$base_path}/suksesbugil/depositaction/delete/{$keval->seq}"); ?>">
																	<i class="fa fa-trash"></i>
																</a>
																<?php
															break;
														}
														?>												
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
										There is no deposit data on this bank code
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



		
		
		<div class="modal fade" id="quick-shop-modal"></div>
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
        $('ul.pagination li a').click(function (e) {
            e.preventDefault();            
            var link = $(this).get(0).href;            
            var value = link.substring(link.lastIndexOf('/') + 1);
			<?php
			if (isset($collect['bank_type_data'])) {
				?>
				 $("#date-range-form").attr("action", '<?= base_url($base_path . '/suksesbugil/deposit/' . $this_method . '/' . $bank_code);?>'  + "/" + value);
				<?php
			} else {
				?>
				 $("#date-range-form").attr("action", '<?= base_url($base_path . '/suksesbugil/deposit/' . $this_method . '/' . 'all');?>'  + "/" + value);
				<?php
			}
			?>
            $("#date-range-form").submit();
        });
		
		// Modal
		/*
		$('.btn-modal-view-item').click(function(el) {
			el.preventDefault();
			var selected_index = $(this).get(0).href;
			//var modal_view_item = selected_index;
			$('#quick-shop-modal').load(selected_index, function() {
				$(this).modal('show');
			});
		});
		*/
		$('.btn-mutasi-detail').click(function(el) {
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
<?php
if (strtolower($this_method) === 'waiting') {
	?>
	<script src="<?= base_url('assets/plugins/howler/dist/howler.js');?>"></script>
	<script type="text/javascript">
		function cache_clear() {
			window.location.reload(true);
			// window.location.reload(); use this if you do not remove cache
		}
		function get_count_waiting_lists() {
			<?php
			if (isset($collect['bank_type_data'])) {
				$count_waiting_url = base_url($base_path . '/suksesbugil/count-deposit-data-by-status/' . $this_method . '/' . $collect['bank_type_data']->bank_code);
			} else {
				$count_waiting_url = base_url($base_path . '/suksesbugil/count-deposit-data-by-status/' . $this_method . '/' . 'all');
			}
			?>
			var url_fetch = '<?=$count_waiting_url;?>';
			var transaction_date = {
				'starting': '<?=$transaction_date['starting'];?>',
				'stopping': '<?=$transaction_date['stopping'];?>'
			};
			$.ajax({
				type: 'POST',
				url: url_fetch,
				data: {'transaction_date': transaction_date},
				success: function(response) {
					var countInt = parseInt(response);
					if (countInt > 0) {
						var sound = new Howl({
							src: ['<?= base_url('assets/media/musics/soundsnap-009.mp3');?>']
						});
						sound.play();
						/*
						setInterval(function() {
							cache_clear();
						}, 1500);
						*/
					}
				}
			});
		}
		$(document).ready(function() {
			
			
			
			
			
			
			
			// Auto Notification of new Incoming Deposit from Suksesbugil
			setInterval(function() {
				get_count_waiting_lists()
			}, 30000);
		});
		
	</script>
	<?php
}
?>



