<form method="post" action="options.php">
	<?php settings_errors(); ?>
	<?php settings_fields('wordly-wise-settings'); ?>
	<?php do_settings_sections('wordly-wise-settings'); ?>
	<?php
	$enabled_languages = get_option('wordly_wise_enabled_languages');
	$available_languages = wordly_wise_get_available_languages();
	?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Wordly Wise API Server</th>
			<td><input type="text" width="500px" name="wordly_wise_api_server" value="<?php echo esc_attr(get_option('wordly_wise_api_server')); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Wordly Wise License Key</th>
			<td>
				<input type="password" name="wordly_wise_api_key" value="<?php echo esc_attr(get_option('wordly_wise_api_key')); ?>" />
				<a href="https://store.maliang.app" rel="noopener noreferrer" target="_blank" class="button button-primary">Buy License Keys</a>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Post Source Language</th>
			<td>
				<select name="wordly_wise_source_language">
					<option value="en-US">English</option>
					<?php
					foreach ($available_languages as $code => $language) {
						$selected = get_option('wordly_wise_source_language') === $code ? 'selected' : '';
						echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($language) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Enabled Languages</th>
			<td>
				<div style="display: flex; flex-wrap: wrap; gap: 5px;">
					<?php

					foreach ($available_languages as $code => $language) {
						$checked = is_array($enabled_languages) && in_array($code, $enabled_languages) ? 'checked' : '';
						echo '<label><input type="checkbox" name="wordly_wise_enabled_languages[]" value="' . esc_attr($code) . '" ' . $checked . ' />' . esc_html($language) . '</label><br>';
					}
					?>
				</div>
				<button style="margin-top: 10px;" type="button" onclick="selectAllLanguages()">Select All</button>
				<script>
					function selectAllLanguages() {
						const checkboxes = document.getElementsByName('wordly_wise_enabled_languages[]');
						const shouldCheck = !checkboxes[0].checked;
						for (var i = 0; i < checkboxes.length; i++) {
							checkboxes[i].checked = shouldCheck
						}
					}
				</script>
			</td>
		</tr>
	</table>
	<?php submit_button('Save Settings'); ?>
</form>