<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		
		<div class="modal-body">
			<div class="row">
				<div class="col-md-12 product-information">
					<div id="quick-shop-container">
						<div class="text-center">
							<h4 id="quick-shop-title" class="alert alert-info">
								Delete Account Data
							</h4>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="box">
						<div class="box-header">
							<h2 class="box-title">
								<?=$collect['account_bank_data']->account_title;?>
							</h2>
							<p class="row">
								<?php
								if (isset($collect['account_bank_data']->rekening_data)) {
									if (is_array($collect['account_bank_data']->rekening_data) && (count($collect['account_bank_data']->rekening_data) > 0)) {
										foreach ($collect['account_bank_data']->rekening_data as $rekdata) {
											?>
											<div class="col-md-4">Bank</div>
											<div class="col-md-8">
												<a href="<?= base_url($base_path . '/mutasi/listaccount/' . $collect['account_bank_data']->bank_code);?>">
													<?=$collect['account_bank_data']->bank_name;?>
												</a>
											</div>
											<div class="col-md-4">Rekening</div>
											<div class="col-md-8"><?=$rekdata->rekening_number;?></div>
											<?php
										}
									}
								}
								?>
								<div class="col-md-4">Username</div>
								<div class="col-md-8"><?=$collect['account_bank_data']->account_username;?></div>
							</p>
						</div>
						
					</div>
				</div>
			</div>
		</div>
			
		<div class="modal-footer">
			<form id="form-delete-bank-account-data" action="<?= base_url($base_path . '/mutasi/delete-bank-account/delete/' . $collect['account_bank_data']->seq);?>" method="post">
				<div class="row">
					<div class="col-md-12 product-information">
						<div class="form-group text-center">
							<input type="hidden" class="input-group" id="account_bank_data_seq" name="account_bank_data_seq" value="<?=$collect['account_bank_data']->seq;?>" />
							<button id="btn-delete-this-item" type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i> Delete all data</button>
						</div>
					</div>
				</div>
			</form>
			<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>