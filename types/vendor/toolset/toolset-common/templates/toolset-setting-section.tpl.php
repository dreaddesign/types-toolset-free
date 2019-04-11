<div<?php if ( isset( $item_data['slug'] ) ) { echo ' id="toolset-' . $item_data['slug'] . '"'; } ?> class="toolset-setting-container js-toolset-setting-container<?php if ( isset( $item_data['slug'] ) ) { echo ' js-toolset-' . $item_data['slug']; } ?>"<?php if ( isset( $item_data['hidden'] ) ) { echo ' style="display:none;"'; } ?>>
	<div class="toolset-settings-header">
		<h2><?php echo $item_data['title']; ?></h2>
	</div>
	<div class="toolset-setting">
		<?php 
		if ( isset( $item_data['content'] ) ) {
			echo $item_data['content'];
		}
		if ( 
			isset( $item_data['callback'] ) 
			&& is_callable( $item_data['callback'] )
		) {
			call_user_func( $item_data['callback'] );
		}
		?>
	</div>
</div>