<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

?>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<i class="close fa fa-times" title="" data-dismiss="modal" aria-hidden="true" data-original-title="Close"></i>
		</div>
		<?php
		if (isset($error)) {
			?>
			<div class="modal-body">
				<?php
				if (isset($error['message'])) {
					if (is_array($error['message']) && (count($error['message']) > 0)) {
						foreach ($error['message'] as $message) {
							if (is_string($message) || is_numeric($message)) {
								?>
								<div class="alert alert-danger alert-dismissable">
									<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
									<?=$message;?>
								</div>
								<?php
							} else {
								// Nothing to do
								unset($message);
							}
						}
					}
				}
				?>
			</div>
			<?php
		} else {
			?>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12 product-information">
						<div id="quick-shop-container">
							<div class="text-center">
								<h4 id="quick-shop-title" class="alert alert-info">
									Transaction Information
								</h4>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="box">
							<div class="box-body table-responsive no-padding">
								<?php
								
								
								
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">(&times;) Close</button>
		</div>
	</div>
</div>