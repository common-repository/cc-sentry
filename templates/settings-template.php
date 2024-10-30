<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h2><?php echo self::__( 'Error Reporting Settings' ); ?></h2>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'sentry' );
		do_settings_sections( 'sentry' );
		submit_button();
		?>
	</form>
</div>