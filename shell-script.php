<?php

class core_google_drive_embedder {
	
	protected function __construct() {
		$this->add_actions();
		register_activation_hook($this->my_plugin_basename(), array( $this, 'gdm_activation_hook' ) );
	}
	
	// May be overridden in basic or premium
	public function gdm_activation_hook($network_wide) {
		global $gdm_core_already_exists;
		if ($gdm_core_already_exists) {
			deactivate_plugins( $this->my_plugin_basename() );
			echo( 'Please Deactivate the free version of Google Drive Embedder before you activate the new Premium/Enterprise version.' );
			exit;
		}
	}
	
	public function gdm_gather_scopes($scopes) {
		return array_merge($scopes, Array('https://www.googleapis.com/auth/drive.readonly'));
	}

	public function gdm_media_button() {
		global $wp_version;
		$output = '';
		
        $img = '<span class="wp-media-buttons-icon" id="gdm-media-button"></span>';
        $output = '<a href="#TB_inline?width=700&height=484&inlineId=gdm-choose-drivefile" id="gdm-thickbox-trigger" class="thickbox button" title="Add Google File" style="padding-left: .4em;">'
                  .$img.' Add Google File</a>';
		echo $output;
	}

    public function gdm_register_scripts() {
        $extra_js_name = $this->get_extra_js_name();
        wp_register_script( 'gdm_simple_browser_js', $this->my_plugin_url().'js/gdm-simple-browser.js', array(), $this->PLUGIN_VERSION);
        if ($extra_js_name != 'basic') {
            wp_register_script( 'gdm_premium_drive_browser_js', $this->my_plugin_url().'js/gdm-premium-drive-browser.js', array('gdm_simple_browser_js'), $this->PLUGIN_VERSION );
        }
		
	    wp_register_script( 'gdm_colorbox_js', $this->my_plugin_url().'js/jquery.colorbox.js', array('jquery'), $this->PLUGIN_VERSION );
	    wp_register_style( 'gdm_colorbox_css', $this->my_plugin_url().'css/gdm-colorbox.css', array(), $this->PLUGIN_VERSION );

	    wp_register_script( 'gdm_base_servicehandler_js', $this->my_plugin_url().'js/gdm-base-servicehandler.js', ($extra_js_name != 'basic' ? array('gdm_premium_drive_browser_js') : array() ), $this->PLUGIN_VERSION);
        wp_register_script( 'gdm_'.$extra_js_name.'_drivefile_js', $this->my_plugin_url().'js/gdm-'.$extra_js_name.'-drivefile.js', array('jquery'), $this->PLUGIN_VERSION );
        wp_register_script( 'gdm_choose_drivefile_js', $this->my_plugin_url().'js/gdm-choose-drivefile.js',
            array('jquery', 'gdm_simple_browser_js', 'gdm_base_servicehandler_js', 'gdm_'.$extra_js_name.'_drivefile_js', 'gdm_colorbox_js'), $this->PLUGIN_VERSION );
    }
 
	public function gdm_admin_load_scripts() {
        $this->gdm_register_scripts();
		wp_localize_script( 'gdm_choose_drivefile_js', 'gdm_trans', $this->get_translation_array() );
		wp_enqueue_script( 'gdm_choose_drivefile_js' );

		wp_enqueue_script( 'google-js-api', 'https://apis.google.com/js/client.js?onload=gdmHandleGoogleJsClientLoad', array('gdm_choose_drivefile_js') );

		wp_enqueue_style( 'gdm_choose_drivefile_css', $this->my_plugin_url().'css/gdm-choose-drivefile.css' );

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

		wp_enqueue_script( 'gdm_colorbox_js' );
		wp_enqueue_style( 'gdm_colorbox_css' );
	}
	
	protected function get_translation_array() {
		// Get Google Client ID
		$clientid = apply_filters('gal_get_clientid', '');
		
		// Get current user's email address
		$current_user = wp_get_current_user();
		$email = $current_user ? $current_user->user_email : '';
		
		return Array( 'scopes' => implode(' ', $this->gdm_gather_scopes(Array()) ),
					  'clientid' => $clientid,
					  'useremail' => $email,
					  'allow_non_iframe_folders' => $this->allow_non_iframe_folders());
	}
	
	protected function get_extra_js_name() {
		return '';
	}

	protected function extra_drive_tabs() {
		?>
        <a href="#allfiles" id="allfiles-tab" class="nav-tab nav-tab-active">All Files</a>
        <a href="#calendar" id="calendar-tab" class="nav-tab">+</a>
		<?php
	}
	
	public function gdm_admin_footer() {
		?>
		<div id="gdm-choose-drivefile" style="display: none;">
			<h3 id="gdm-tabs" class="nav-tab-wrapper">
			<?php
                $this->extra_drive_tabs();
			?>
			</h3>
			
			<div class="wrap gdm-wrap">
				
				<div id="gdm-search-area">
					<input type="text" id="gdm-search-box" placeholder="Enter text to search (then press Enter)" disabled="disabled"></input>
					<a href="#" id="gdm-search-clear" style="display: none;">Clear Search</a>
				</div>

				<div id="gdm-file-browser-area">
				</div>
				
				<div id="gdm-linktypes-div" class="gdm-group">
					<div>
						<span class="gdm-linktypes-span">
							<input type="radio" name="gdm-linktypes" id="gdm-linktype-normal" />
							<label for="gdm-linktype-normal">Viewer file link</label>
						</span>
						<span id="gdm-linktype-normal-options" class="gdm-linktype-options">
							<input type="checkbox" id="gdm-linktype-normal-plain" checked="checked" /> 
							<label for="gdm-linktype-normal-plain">Show icon</label>
						
							&nbsp; &nbsp; &nbsp; &nbsp;
							<input type="checkbox" id="gdm-linktype-normal-window" checked="checked" /> 
							<label for="gdm-linktype-normal-window">Open in new window</label>
							
							&nbsp; &nbsp;
							<a href="#" id="gdm-linktype-normal-more" style="display: none;" class="gdm-linktype-more">Options...</a>
						</span>
					</div>
					
					<div class="gdm-downloadable-only">
						<span class="gdm-linktypes-span">
							<input type="radio" name="gdm-linktypes" id="gdm-linktype-download" />
							<label for="gdm-linktype-download">Download file link
								<span id="gdm-linktype-download-reasons"></span>
							</label>
						</span>
						<span id="gdm-linktype-download-options" class="gdm-linktype-options">
							<select name="gdm-linktype-download-type" id="gdm-linktype-download-type"></select>
							 &nbsp; &nbsp;
							<input type="checkbox" id="gdm-linktype-download-plain" checked="checked" /> 
							<label for="gdm-linktype-download-plain">Show icon</label>
						</span>
					</div>
					
					<div class="gdm-embeddable-only">
						<span class="gdm-linktypes-span">
							<input type="radio" name="gdm-linktypes" id="gdm-linktype-embed" checked="checked" />
							<label for="gdm-linktype-embed">Embed document
								<span id="gdm-linktype-embed-reasons"></span>
							</label>
						</span>
						
						<span id="gdm-linktype-embed-options" class="gdm-linktype-options">
							<label for="gdm-linktype-embed-width">Width</label> <input type="text" id="gdm-linktype-embed-width" size="7" value="100%" />
							&nbsp; &nbsp;
							<label for="gdm-linktype-embed-height">Height</label> <input type="text" id="gdm-linktype-embed-height" size="7" value="400" />
							&nbsp; &nbsp;
							<a href="#" id="gdm-linktype-embed-more" style="display: none;" class="gdm-linktype-more">Options...</a>
						</span>
					</div>

					<!-- START Template for Simple Browser -->
					<div id="gdm-simple-browser-template-html" style="display: none;">
						<div class="gdm-thinking gdm-browsebox">
							<div class="gdm-thinking-text">Loading...</div>
						</div>

						<div class="gdm-authbtn gdm-browsebox" style="display: none;">
							<div>
								<a href="#" class="gdm-start-browse2">Click to authenticate via Google</a>
							</div>
						</div>

						<div class="gdm-filelist gdm-browsebox" style="display: none;"></div>
						<div class="gdm-nextprev-div gdm-group">
							<a href="#" class="gdm-prev-link" style="display: none;">Previous</a>
							<a href="#" class="gdm-next-link" style="display: none;">Next</a>
						</div>
					</div>
                    <!-- END Template for Simple Browser -->

                    <!-- START Template for Premium Browser -->
                    <div id="gdm-premium-browser-template-html" style="display: none;">
                        <div class="gdm-thinking gdm-browsebox">
                            <div class="gdm-thinking-text">Loading...</div>
                        </div>

                        <div class="gdm-authbtn gdm-browsebox" style="display: none;">
                            <div>
                                <a href="#" class="gdm-start-browse2">Click to authenticate via Google</a>
                            </div>
                        </div>
                    </div>
                    <!-- END Template for Premium Browser -->
				</div>

				<?php $this->admin_footer_extra(); ?>
				
				<p class="submit">
				<script>
					if (typeof enable_append_btn === 'function') {
						//alert('loaded');
					}
					else{
						//alert("not loaded");
						function enable_append_btn(){}
						function close_insert_popup(){
							tb_remove();
						}
					}
					</script>
				</script>
				<?php
					/*if(function_exists('the_gutenberg_project')){
						echo "yes";
						echo $need_function;
					}else{
						echo "no";
						echo $need_function;
					}*/
				?>
					<input type="button" id="gdm-insert-drivefile" class="button-primary" onclick="enable_append_btn();"
							value="Insert File" disabled="disabled" />
					<!-- <a id="gdm-cancel-drivefile-insert" class="button-secondary" onclick="tb_remove();" title="Cancel">Cancel</a> -->
					<a id="gdm-cancel-drivefile-insert" class="button-secondary" onclick="close_insert_popup();" title="Cancel">Cancel</a>
						<!-- hidden field for the new test -->
					<input type="hidden" id="btn_classname_gde_editor" value="" />
					<span id="gdm-ack-owner-editor" style="display: none;">
					<input type="checkbox" id="gdm-ack-owner-editor-checkbox" class="gdm-ack-owner-editor" />
					<label for="gdm-ack-owner-editor-checkbox">I acknowledge that I will be demoted from owner to editor</label>
					</span>
				</p>
				
			</div>
		</div>
		<?php
	}
	
	protected function allow_non_iframe_folders() {
		return false;
	}
	
	// Extended in premium
	protected function admin_footer_extra() {
	}
	
	public function gdm_admin_downloads_icon() {
		
		$images_url  = $this->my_plugin_url() . 'images/';
		$icon_url    = $images_url . 'gdm-media.png';
		?>
		<style type="text/css" media="screen">
			#gdm-media-button {
				background: url(<?php echo $icon_url; ?>) no-repeat;
				background-size: 20px 20px;
			}
		</style>
		<?php
	}
	
	// SHORTCODES
	
	public function gdm_shortcode_display_drivefile($atts, $content=null) {
		
		if (!isset($atts['url'])) {
			return '<b>google-drive-embed requires a url attribute</b>';
		}
		$url = $atts['url'];
		$title = isset($atts['title']) ? $atts['title'] : $url; // Should be html-encoded already
		
		$linkstyle = isset($atts['style']) && in_array($atts['style'], Array('normal', 'plain', 'download', 'embed')) 
						? $atts['style'] : 'normal';
		$extra = isset($atts['extra']) ? $atts['extra'] : '';

		$returnhtml = '';
		switch ($linkstyle) {
			case 'normal':
			case 'download':
			case 'plain':
				$imghtml = '';
				if (isset($atts['icon'])) {
					$imghtml = '<img src="'.$atts['icon'].'" width="16" height="16" />';
				}
				$newwindow = isset($atts['newwindow']) && $atts['newwindow']=='yes' ? ' target="_blank"' : '';
				$ahref = '<a href="'.$url.'"'.$newwindow.'>'.$title.'</a>';
				if ((isset($atts['plain']) && $atts['plain']=='yes') || $linkstyle=='plain') {
					$returnhtml = $ahref;
				}
				else {
					$returnhtml = '<p><span class="gdm-drivefile-embed">'.$imghtml.' '.$ahref.'</span></p>';
				}
				break;
			case 'embed':
				$width = isset($atts['width']) ? $atts['width'] : '100%';
				$height = isset($atts['height']) ? $atts['height'] : '400';
				$scrolling = isset($atts['scrolling']) && strtolower($atts['scrolling']) == 'no' ? 'no' : 'yes';
				$allowfullscreen = isset($atts['allowfullscreen']) && strtolower($atts['allowfullscreen']) == 'no' ? '' : 'allowfullscreen';
				$returnhtml = "<iframe width='${width}' height='${height}' frameborder='0' scrolling='${scrolling}' src='${url}' ${allowfullscreen}></iframe>";

				if ($extra == 'image') {
					$width = isset($atts['width']) ? $atts['width'] : '';
					$height = isset($atts['height']) ? $atts['height'] : '';
					$sizeattrs = '';
					if ($width) {
						$sizeattrs .= " width=\"${width}\"";
					}
					if ($height) {
						$sizeattrs .= " height=\"${height}\"";
					}
					$returnhtml = "<img src=\"${url}\"${sizeattrs} />";
                }

				break;
		}
		if (!is_null($content)) {
			$returnhtml .= do_shortcode($content);
		}
		return $returnhtml;
	}
	
	// ADMIN OPTIONS
	// *************

	protected function get_options_menuname() {
		return 'gdm_list_options';
	}
	
	protected function get_options_pagename() {
		return 'gdm_options';
	}
	
	protected function get_settings_url() {
		return is_multisite()
		? network_admin_url( 'settings.php?page='.$this->get_options_menuname() )
		: admin_url( 'options-general.php?page='.$this->get_options_menuname() );
	}
	
	public function gdm_admin_menu() {
		if (is_multisite()) {
			add_submenu_page( 'settings.php', 'Google Drive Embedder settings', 'Google Drive Embedder',
			'manage_network_options', $this->get_options_menuname(),
			array($this, 'gdm_options_do_page'));
		}
		else {
			add_options_page( 'Google Drive Embedder settings', 'Google Drive Embedder',
			'manage_options', $this->get_options_menuname(),
			array($this, 'gdm_options_do_page'));
		}
	}
	
	public function gdm_options_do_page() {
	
		$submit_page = is_multisite() ? 'edit.php?action='.$this->get_options_menuname() : 'options.php';
	
		if (is_multisite()) {
			$this->gdm_options_do_network_errors();
		}
		?>
			  
		<div>
		
		<h2>Google Drive Embedder setup</h2>
		
		<?php $this->output_instructions_button(); ?>
		
		<div id="gdm-tablewrapper">

		<?php $this->draw_admin_settings_tabs(); ?>
		
		<form action="<?php echo $submit_page; ?>" method="post" id="gdm_form">
		
		<?php 
		settings_fields($this->get_options_pagename());

		$this->gdm_extrasection_text();
		
		$this->gdm_mainsection_text();
		
		$this->gdm_options_submit();
		?>
				
		</form>
		</div>
		
		</div>  <?php
	}

	// Override in Enterprise
	protected function output_instructions_button() {
	}
	
	// Override in professional
	protected function draw_admin_settings_tabs() {
	}
	
	protected function gdm_options_submit() {
	?>
		<p class="submit">
			<input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit">
		</p>
	<?php
	}
	
	// Extended in basic and premium
	protected function gdm_mainsection_text() {
	}
	protected function gdm_extrasection_text() {
	}
	
	public function gdm_options_validate($input) {
		$newinput = Array();
		$newinput['gdm_version'] = $this->PLUGIN_VERSION;
		return $newinput;
	}
	
	protected function get_error_string($fielderror) {
		return 'Unspecified error';
	}
	
	public function gdm_save_network_options() {
		check_admin_referer( $this->get_options_pagename().'-options' );
	
		if (isset($_POST[$this->get_options_name()]) && is_array($_POST[$this->get_options_name()])) {
			$inoptions = $_POST[$this->get_options_name()];
			
			$outoptions = $this->gdm_options_validate($inoptions);
			
			$error_code = Array();
			$error_setting = Array();
			foreach (get_settings_errors() as $e) {
				if (is_array($e) && isset($e['code']) && isset($e['setting'])) {
					$error_code[] = $e['code'];
					$error_setting[] = $e['setting'];
				}
			}
	
			update_site_option($this->get_options_name(), $outoptions);
				
			// redirect to settings page in network
			wp_redirect(
			add_query_arg(
			array( 'page' => $this->get_options_menuname(),
			'updated' => true,
			'error_setting' => $error_setting,
			'error_code' => $error_code ),
			network_admin_url( 'admin.php' )
			)
			);
			exit;
		}
	}
	
	protected function gdm_options_do_network_errors() {
		if (isset($_REQUEST['updated']) && $_REQUEST['updated']) {
			?>
					<div id="setting-error-settings_updated" class="updated settings-error">
					<p>
					<strong>Settings saved</strong>
					</p>
					</div>
				<?php
			}
	
			if (isset($_REQUEST['error_setting']) && is_array($_REQUEST['error_setting'])
				&& isset($_REQUEST['error_code']) && is_array($_REQUEST['error_code'])) {
				$error_code = $_REQUEST['error_code'];
				$error_setting = $_REQUEST['error_setting'];
				if (count($error_code) > 0 && count($error_code) == count($error_setting)) {
					for ($i=0; $i<count($error_code) ; ++$i) {
						?>
					<div id="setting-error-settings_<?php echo $i; ?>" class="error settings-error">
					<p>
					<strong><?php echo htmlentities2($this->get_error_string($error_setting[$i].'|'.$error_code[$i])); ?></strong>
					</p>
					</div>
						<?php
				}
			}
		}
	}
	
	// OPTIONS
	
	protected function get_default_options() {
		return Array('gdm_version' => $this->PLUGIN_VERSION);
	}
	
	protected $gdm_options = null;
	protected function get_option_gdm() {
		if ($this->gdm_options != null) {
			return $this->gdm_options;
		}
	
		$option = get_site_option($this->get_options_name(), Array());
	
		$default_options = $this->get_default_options();
		foreach ($default_options as $k => $v) {
			if (!isset($option[$k])) {
				$option[$k] = $v;
			}
		}
	
		$this->gdm_options = $option;
		return $this->gdm_options;
	}
	
	// ADMIN

    public function gdm_init() {
	    add_shortcode( 'google-drive-embed', Array($this, 'gdm_shortcode_display_drivefile') );

	    // Gutenberg block
	    /*if (function_exists('register_block_type')) {
		    register_block_type( 'gdm/google-drive-embedder-viewer', array(
			    'render_callback' => array($this, 'gdm_shortcode_display_drivefile')
		    ) );
	    }*/

	    add_action( 'enqueue_block_assets', array($this, 'gutenberg_enqueue_block_assets') );
    }
	
	public function gdm_admin_init() {
		register_setting( $this->get_options_pagename(), $this->get_options_name(), Array($this, 'gdm_options_validate') );

		global $pagenow;

		// Check Google Apps Login is configured - display warnings if not
		if (apply_filters('gal_get_clientid', '') == '') {
			add_action('admin_notices', Array($this, 'gdm_admin_auth_message'));
			if (is_multisite()) {
				add_action('network_admin_notices', Array($this, 'gdm_admin_auth_message'));
			}
		}
		
		// If on post/page edit screen, set up Add Drive File button
		if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php')) ) {
			add_action( 'admin_head', Array($this, 'gdm_admin_downloads_icon') );
			add_action( 'media_buttons', Array($this, 'gdm_media_button'), 11 );
			add_action( 'admin_enqueue_scripts', Array($this, 'gdm_admin_load_scripts') );
			add_action( 'admin_footer', Array($this, 'gdm_admin_footer') );

			add_action( 'enqueue_block_editor_assets', array($this, 'gutenberg_enqueue_block_editor_assets') );
		}		
	}
	
	public function gdm_admin_auth_message() {
		?>
			<div class="error">
	        	<p>You will need to install and configure 
	        		<a href="http://wp-glogin.com/google-apps-login-premium/?utm_source=Admin%20Configmsg&utm_medium=freemium&utm_campaign=Drive" 
	        		target="_blank">Google Apps Login</a>  
	        		plugin in order for Google Drive Embedder to work. (Free, Premium, or Enterprise version)
	        	</p>
	    	</div> <?php
		}


	protected function add_actions() {
	    /* No longer want to request access to scopes through 'Login with Google'.
        * Now wait until the user clicks 'Add Google File' or accesses a folder.
        * This is best practice, and also defers any 'Invalid Scope' error further down the chain
        * so it is clearly a 'Drive' issue.
		* add_filter('gal_gather_scopes', Array($this, 'gdm_gather_scopes') );
	    */

		add_action( 'init', array($this, 'gdm_init'), 5, 0 );
		
		if (is_admin()) {
			add_action( 'admin_init', array($this, 'gdm_admin_init'), 5, 0 );
			
			add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', array($this, 'gdm_admin_menu'));
			
			if (is_multisite()) {
				add_action('network_admin_edit_'.$this->get_options_menuname(), array($this, 'gdm_save_network_options'));
			}
		}
	}

	// Gutenberg enqueues

	function gutenberg_enqueue_block_editor_assets() {
		wp_enqueue_script(
			'gdm-gutenberg-block-js', // Unique handle.
			$this->my_plugin_url(). 'js/gdm-blocks.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
			$this->PLUGIN_VERSION
		);

		wp_enqueue_style(
			'gdm-gutenberg-block-css', // Handle.
			$this->my_plugin_url(). 'css/gdm-blocks.css', // editor.css: This file styles the block within the Gutenberg editor.
			//array( 'wp-edit-blocks' ), // Dependencies, defined above.
			$this->PLUGIN_VERSION
		);
	}

	function gutenberg_enqueue_block_assets() {
		wp_enqueue_style(
			'gdm-gutenberg-block-backend-js', // Handle.
			$this->my_plugin_url(). 'css/gdm-blocks.css', // style.css: This file styles the block on the frontend.
			//array( 'wp-blocks' ), // Dependencies, defined above.
			$this->PLUGIN_VERSION
		);
	}

}


class gdm_Drive_Exception extends Exception {

}

?>