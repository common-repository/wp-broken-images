<?php
/*
Plugin Name: Broken Images
Plugin URI: http://kaloyan.info/blog/wp-broken-images-plugin/
Description: This plugin is designed to handle the broken images that might appear on the posts on your blog.
Author: Kaloyan K. Tsvetkov
Version: 0.2
Author URI: http://kaloyan.info/
*/

/////////////////////////////////////////////////////////////////////////////

/**
* @internal prevent from direct calls
*/
if (!defined('ABSPATH')) {
	return ;
	}

/**
* @internal prevent from second inclusion
*/
if (!class_exists('wp_broken_images')) {

/////////////////////////////////////////////////////////////////////////////

/**
* "Broken Images" WordPress Plugin
*
* @author Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/
Class wp_broken_images {

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Constructor
	*
	* This constructor attaches the needer plugin hook callbacks
	*/
	function wp_broken_images() {

		// attach the onError handlers
		//
		add_action('the_content',
			array(&$this, '_content')
			);
		add_action('the_excerpt',
			array(&$this, '_content')
			);
		
		// attach the JS snippet
		//
		add_action('wp_head',
			array(&$this, '_js')
			);
		/*
		add_action('wp_footer',
			array(&$this, '_js')
			);
		*/

		// attach to admin menu
		//
		if (is_admin()) {
			add_action('admin_menu',
				array(&$this, '_menu')
				);
			}
		
		// attach to plugin installation
		//
		add_action(
			'activate_' . str_replace(
				DIRECTORY_SEPARATOR, '/',
				str_replace(
					realpath(ABSPATH . PLUGINDIR) . DIRECTORY_SEPARATOR,
						'', __FILE__
					)
				),
			array(&$this, 'install')
			);
		}
	
	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Adds the `onError` handlers to the images
	*
	* @param string $content
	* @return string
	*/
	function _content($content) {
		
		$_ = (array) get_option('wp_broken_images_settings');
		if (!$_ || !$_['mode']) {
			return $content;
			}

		return preg_replace(
			'~<img~Uis',
			'<img onError="javascript: wp_broken_images = window.wp_broken_images || function(){}; wp_broken_images(this);" ',
			$content
			);
		}

	/**
	*/
	function _js() {
		
		$_ = (array) get_option('wp_broken_images_settings');
		
		switch($_['mode']) {
			
			case 'hide' :
				$handle = 'img.style.display=\'none\';';
				break;
			
			case 'swap' :
				$handle = 'img.src=\'' . $_['swap'] . '\';';
				$extra = 'var i = new Image(); i.src=\'' . $_['swap'] . '\';' . "\n";
				break;
				
			case 'css' :
				$handle = '';
				//if ($_['spacer']) {
					$handle = 'img.src=\'' . $_['spacer'] . '\';' . "\n\t";
				//	}
				$handle .= 'img.className +=\' ' . $_['css'] . '\';';
				break;
			
			default :
				return;
			}
		
		echo <<<BROKEN_IMAGES_JS
		
<script type="text/javascript">
<!--//
var wp_broken_images = wp_broken_images || function(img) {
	{$handle}
	img.onerror = function(){};
	}
{$extra}//-->
</script>
BROKEN_IMAGES_JS;
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Performs the routines required at plugin installation: 
	* in general introducing the settings array
	*/	
	function install() {
		add_option(
			'wp_broken_images_settings',
				array(
					'mode' => ''
				)
			);
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --
	
	/**
	* Attach the menu page to the `Options` tab
	*/
	function _menu() {
		add_submenu_page('options-general.php',
			 'Broken Images',
			 'Broken Images', 8,
			 __FILE__,
			 array($this, 'menu')
			);
		}
		
	/**
	* Handles and renders the menu page
	*/
	function menu() {

		// sanitize referrer
		//
		$_SERVER['HTTP_REFERER'] = preg_replace(
			'~&saved=.*$~Uis','', $_SERVER['HTTP_REFERER']
			);
		
		// information updated ?
		//
		if ($_POST['submit']) {
			
			// sanitize
			//
			$_POST['wp_broken_images_settings']['swap'] = stripSlashes(
				$_POST['wp_broken_images_settings']['swap']);
			$_POST['wp_broken_images_settings']['css'] = stripSlashes(
				$_POST['wp_broken_images_settings']['css']);

			// spacer ?
			//
			$_POST['wp_broken_images_settings']['spacer'] =
				(file_exists(	dirname(__FILE__)
							. DIRECTORY_SEPARATOR
							. 'wp-broken-images-transaprent-1x1.gif')
						)
					? (get_option('siteurl')
						. '/' . PLUGINDIR
						. '/' . dirname($_GET['page'])
						. '/wp-broken-images-transaprent-1x1.gif') : null;

			// save
			//
			update_option(
				'wp_broken_images_settings',
				$_POST['wp_broken_images_settings']
				);

			die("<script>document.location.href = '{$_SERVER['HTTP_REFERER']}&saved=settings:" . time() . "';</script>");
			}

		// operation report detected
		//
		if (@$_GET['saved']) {
			
			list($saved, $ts) = explode(':', $_GET['saved']);
			if (time() - $ts < 10) {
				echo '<div class="updated"><p>';
	
				switch ($saved) {
					case 'settings' :
						echo 'Settings saved.';
						break;
					}
	
				echo '</p></div>';
				}
			}

		// read the settings
		//
		$wp_broken_images_settings = get_option('wp_broken_images_settings');

?>
<div class="wrap">
	<h2>Broken Images</h2>
	<p>For more information please visit the <a href="http://kaloyan.info/blog/wp-broken-images-plugin/">Broken Images</a> homepage.</p>
	<form method="post">
	<fieldset class="options">
		
		<div>Choose from the scenarios below about how you want to handle the broken images:</div>
		
		<blockquote>
		<table>
			<tr><td>
			<input <?php echo (!$wp_broken_images_settings[mode]) ? 'checked="checked"' : ''; ?> type="radio" name="wp_broken_images_settings[mode]" value="" id="wp_broken_images_settings_mode_nothing" />
			</td><td>
			<label for="wp_broken_images_settings_mode_nothing"><b>Do nothing</b></label><br/>
			</td></tr><tr><td></td><td>
			Do nothing, and show the broken images as usual
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_broken_images_settings[mode] === 'hide') ? 'checked="checked"' : ''; ?> type="radio" name="wp_broken_images_settings[mode]" value="hide" id="wp_broken_images_settings_mode_hide" />
			</td><td>
			<label for="wp_broken_images_settings_mode_hide"><b>Hide broken images</b></label><br/>
			</td></tr><tr><td></td><td>
			Use this setting to hide the broken images
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_broken_images_settings[mode] === 'swap') ? 'checked="checked"' : ''; ?> type="radio" name="wp_broken_images_settings[mode]" value="swap" id="wp_broken_images_settings_mode_swap" />
			</td><td>
			<label for="wp_broken_images_settings_mode_swap"><b>Put image</b></label><br/>
			</td></tr><tr><td></td><td>
			Use this setting to replace all the broken images with a new one using the URL below:<br/>
			<input size="52" name="wp_broken_images_settings[swap]" value="<?php echo $wp_broken_images_settings['swap']; ?>" />
			<br/>&nbsp;</td></tr>
			
			<tr><td>
			<input <?php echo ($wp_broken_images_settings[mode] === 'css') ? 'checked="checked"' : ''; ?> type="radio" name="wp_broken_images_settings[mode]" value="css" id="wp_broken_images_settings_mode_css" />
			</td><td>
			<label for="wp_broken_images_settings_mode_css"><b>Put CSS class</b></label><br/>
			</td></tr><tr><td></td><td>
			Use this setting to append to all broken images the CSS class specified below:<br/>
			<input size="32" name="wp_broken_images_settings[css]" value="<?php echo $wp_broken_images_settings['css']; ?>" />
			<br/>&nbsp;</td></tr>

		</table>
		</blockquote>

		<p class="submit" style="text-align:left;"><input type="submit" name="submit" value="Update &raquo;" /></p>
	</fieldset>
	</form>
</div>
<?php
		}
	
	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	
	//--end-of-class
	}

}

/////////////////////////////////////////////////////////////////////////////

/**
* Initiating the plugin...
* @see wp_broken_images
*/
new wp_broken_images;

?>