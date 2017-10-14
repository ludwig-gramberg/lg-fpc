<?php
/**
 * Plugin Name: LG FPC Cache
 * Plugin URI: https://github.com/ludwig-gramberg/
 * Description: WP FPC Cache
 * Version: 0.1
 * Author: Ludwig Gramberg
 * Author URI: http://www.ludwig-gramberg.de/
 * Text Domain:
 * License: MIT
 */
require_once 'bootstrap.php';

function lg_fpc_flush() {
	\Lg\FullPageCache::getInstance()->flush();
}
function lg_fpc_refresh() {
	\Lg\FullPageCache::getInstance()->refreshAll();
}
function lg_fpc_settings_init() {
	register_setting('lg_fpc', 'lg_fpc', 'lg_fpc_settings_process');
}
function lg_fpc_settings_menu() {
	add_options_page('Full Page Cache', 'Full Page Cache', 'manage_options', 'lg_fpc', 'lg_fpc_settings_page');
}
function lg_fpc_settings_process() {
	if(array_key_exists('empty_fpc', $_POST)) {
		do_action('lg_fpc_flush');
	}
}
function lg_fpc_settings_page() {

	$stats = \Lg\FullPageCache::getInstance()->getStats();
	$size_fpc = $stats->getMemoryBytes();

	?>
	<div class="wrap">
		<h2>Full Page Cache</h2>
		<form method="post" action="options.php">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php echo __('Pages');?></th>
						<td>
							<?php echo $stats->getNumberOfPages() ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo __('Memory Consumption');?></th>
						<td>
							<?php
							$unit = 'byte';
							if($size_fpc > 1024) {
								$unit = 'Kib';
								$size_fpc /= 1024;
							}
							if($size_fpc > 1024) {
								$unit = 'Mib';
								$size_fpc /= 1024;
							}
							if($size_fpc > 1024) {
								$unit = 'Gib';
								$size_fpc /= 1024;
							}
							?>
							<?php echo number_format($size_fpc,1,',','.').' '.$unit ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php settings_fields( 'lg_fpc' ); ?>
			<?php submit_button(__('Empty Full Page Cache'), 'delete', 'empty_fpc'); ?>
		</form>
	</div>
	<?php
}

add_action('lg_fpc_flush', 'lg_fpc_flush', 10, 0);
add_action('lg_fpc_refresh', 'lg_fpc_refresh', 10, 0);
add_action('admin_init', 'lg_fpc_settings_init');
add_action('admin_menu', 'lg_fpc_settings_menu');