<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>
<div class="modal-dialog modal-lg" style="width:89%;">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		<div class="modal-header">
			<h3>Error</h3>
		</div>
		
		<div class="modal-body">
			<div class="row">
				<div class="col-md-12">
					<div class="box">
						<div class="box-header">
							
						</div>
						<div class="box-body table-responsive no-padding">
							<?php
							if (isset($collect)) {
								?>
								<div class="alert alert-danger alert-dismissable">
									<?php
									if (is_array($collect) && (count($collect) > 0)) {
										foreach ($collect as $error_msg) {
											if (!empty($error_msg) && (strlen($error_msg) > 0)):
											?>
											<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
											<?=$error_msg;?>
											<?php
											endif;
										}
									}
									?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
			
		<div class="modal-footer">
			<button type="button" class="btn btn-sm btn-danger" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>