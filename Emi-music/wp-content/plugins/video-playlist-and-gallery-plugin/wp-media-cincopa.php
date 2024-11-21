<?php
/*
Plugin Name: Cincopa video and media plug-in
Plugin URI: https://www.cincopa.com/media-platform/wordpress-plugin.aspx
Description: Post rich videos and photos galleries from your cincopa account
Author: Cincopa 
Version: 1.163
Text Domain: cincopa-video-and-media
*/

require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

function cincopa_mp_plugin_ver()
{
	return 'wp1.163';
}

function cincopa_mp_url()
{
	return '//www.cincopa.com';
}

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=cincopa') && !strpos($_SERVER['REQUEST_URI'], '&wrt=')) {
	header('Location: ' . cincopa_mp_url() . '/media-platform/start.aspx?utm_source=wpplugin&utm_medium=whatever&utm_campaign=' . cincopa_mp_plugin_ver() . '&rdt=' . urlencode(cincopa_mp_selfURL()));
	exit;
}

function cincopa_mp_mt_get_authorize_url()
{
	return "https://www.cincopa.com/authorize?application=Wordpress&&permissions=gallery.read|asset.read|asset.upload&redirect_url=" . urlencode(get_site_url() . "/wp-admin/options-general.php?page=cincopaoptions");
}

function cincopa_mp_selfURL()
{
	$s = empty($_SERVER["HTTPS"]) ? '' : (($_SERVER["HTTPS"] == "on") ? "s" : "");

	$protocol =  strtolower($_SERVER["SERVER_PROTOCOL"]);
	$protocol =  substr($protocol, 0, strpos($protocol, "/"));
	$protocol .= $s;

	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
	$ret = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

	return $ret;
}

function cincopa_mp_pluginURI()
{
	$s = empty($_SERVER["HTTPS"]) ? '' : (($_SERVER["HTTPS"] == "on") ? "s" : "");

	$protocol =  strtolower($_SERVER["SERVER_PROTOCOL"]);
	$protocol =  substr($protocol, 0, strpos($protocol, "/"));
	$protocol .= $s;

	$url = site_url('', $protocol);
	return $url . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__));
}

function cincopa_mp_WpMediaCincopa_init() // constructor
{
	add_action('media_buttons', 'cincopap_mp_addMediaButton', 20);

	add_action('media_upload_cincopa', 'cincopa_mp_media_upload');
	// No longer needed in WP 2.6
	if (!function_exists('wp_enqueue_style')) {
		add_action('admin_head_media_upload_type_cincopa', 'media_admin_css');
	}
}

function cincopa_mp_media_menu($tabs)
{
	$newtab = array('cincopabox' => __('Insert Media from Cincopa', 'cincopa'));
	return array_merge($tabs, $newtab);
}

function cincopap_mp_addMediaButton($admin = true)
{
	global $post_ID, $temp_ID;
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);

	$media_upload_iframe_src = get_option('siteurl') . "/wp-admin/media-upload.php?post_id=$uploading_iframe_ID";

	$media_cincopa_iframe_src = apply_filters('media_cincopa_iframe_src', "$media_upload_iframe_src&amp;type=cincopa&amp;tab=cincopa");

	$token = get_site_option('cincopa_cp_mt_api_token');

	if (!$token) {
		$token = get_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', true);
	}
	
?>
	<div id="cincopa_button">
		
		<?php if($token) { ?>
			<a class="cp-show-library button" title="Insert from Cincopa">Insert from Cincopa</a>
			<div class="cincopa-gallery-block"><img src="<?php echo (esc_html(cincopa_mp_pluginURI())); ?>/loading.gif"></div>
		<?php }else{ ?>
			<a href="<?php echo cincopa_mp_mt_get_authorize_url(); ?>" class="cp-login-cincopa button" title="Login to Cincopa">Login to Cincopa</a>
		<?php } ?>
	</div>
	
<?php
}

function cincopa_mp_modifyMediaTab($tabs)
{
	return array(
		'cincopa' =>  __('Cincopa photo', 'wp-media-cincopa'),
	);
}

function cincopa_mp_media_upload()
{
	wp_iframe('cincopa_mp_media_upload_type_cincopa');
}


function cincopa_mp_media_upload_type_cincopa()
{
	global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types;
	add_filter('media_upload_tabs', 'cincopa_mp_modifyMediaTab');
?>

	<br />
	<br />
	<h2>&nbsp;&nbsp;Please Wait...</h2>

	<script>
		function cincopa_mp_cincopa_stub() {
			var i = location.href.indexOf("&wrt=");

			if (i > -1) {
				top.send_to_editor(unescape(location.href.substring(i + 5)));
			}

			top.tb_remove();
		}

		window.onload = cincopa_mp_cincopa_stub;
	</script>

<?php
}

cincopa_mp_WpMediaCincopa_init();


// this new regex should resolve the problem of having unicode chars in the tag
define("CINCOPA_REGEXP", "/\[cincopa([^\]]*)\]/");


define('DEFAULT_TEMPLATES', array(
	'video' =>'A4HAcLOLOO68',
	'audio'=> 'AEFALSr3trK4',
	'image'=>'A4IA-RbWMFlu', //'A8AAFV8a-H5b',
	'unknown'=> 'AYFACCtYYllw'));


function cincopa_mp_cincopa_tag($fid)
{
	return cincopa_mp_plugin_callback(array($fid));
}

function cincopa_mp_plugin_callback($match, $fromShortcodeCallback = false)
{
	$fid = $fromShortcodeCallback ? $match[0] : $match[1];
	$fid = trim($fid);
	$uni =  str_replace(['@', '!'], '_', $fid); //uniqid(''); 
	if (isset($match[1]) && strpos($match[1], 'cincopawidget') > -1) {
		$uni .= '_' . $match[1];
	}

	$uni .= '_' . md5(uniqid(rand(), true));
	// don't remove it. used for open graph generation
	$ret = sprintf('<!-- %s -->', $match[0]);
	wp_enqueue_script('libasyncjs', '//rtcdn.cincopa.com/libasync.js');
	$ret .= '
<!-- Cincopa WordPress plugin ' . cincopa_mp_plugin_ver() . ': //www.cincopa.com/media-platform/wordpress-plugin.aspx -->
<div id="cp_widget_' . $uni . '"><img src="//www.cincopa.com/media-platform/runtime/loading.gif" style="border:0;" alt="Cincopa WordPress plugin" /></div>
<script type="text/javascript">
/* PLEASE CHANGE DEFAULT EXCERPT HANDLING TO CLEAN OR FULL (go to your Wordpress Dashboard/Settings/Cincopa Options ... */
// cp_load_widget("' . urlencode($fid) . '", "cp_widget_' . $uni . '");
</script>
';
	wp_add_inline_script('libasyncjs', 'cp_load_widget("' . urlencode($fid) . '", "cp_widget_' . $uni . '")');
	//$ret .= '<noscript>Powered by Cincopa Video Hosting.<br>';
	//$ret .= '</noscript>';

	return $ret;
}

function cincopa_mp_async_plugin_callback($match, $fromShortcodeCallback = false)
{
	//$fid = trim($match[1]);
	$fid = $fromShortcodeCallback ? $match[0] : $match[1];
	$fid = trim($fid);
	$uni =  str_replace(['@', '!'], '_', $fid); //uniqid(''); 
	if (isset($match[1]) && strpos($match[1], 'cincopawidget') > -1) {
		$uni .= '_' . $match[1];
	}

	$uni .= '_' . md5(uniqid(rand(), true));

	$ret = '
<!-- Cincopa WordPress plugin ' . cincopa_mp_plugin_ver() . ' (async engine): //www.cincopa.com/media-platform/wordpress-plugin.aspx -->
<div id="cincopa_' . urlencode($uni) . '" class="gallerydemo cincopa-fadein"><div style="width: 100%; height: auto; max-width: 100%;"><img class="cincopa-thumbnail" src="https://rtcdn.cincopa.com/thumb.aspx?fid=' . urlencode($fid) . '&size=medium" style="filter:blur(5px);heiXght:100%;object-fit:contain;width:100%;" onload="this.parentNode ? this.parentNode.style.opacity=1 : \'\'" /></div></div>
<script src="//rtcdn.cincopa.com/meta_json.aspx?fid=' . urlencode($fid) . '&ver=v2&id=cincopa_' . urlencode($uni) . '" type="text/javascript"></script>
';

	wp_enqueue_script('libasyncjs', '//rtcdn.cincopa.com/libasync.js');



	return $ret;
}


function cincopa_mp_feed_plugin_callback($match, $fromShortcodeCallback = false)
{
	$fid = $fromShortcodeCallback ? $match[0] : $match[1];
	$fid = trim($fid);
	$ret = '<img style="border:0;" src="//www.cincopa.com/media-platform/api/thumb.aspx?fid=' . urlencode($fid) . '&size=large" />';

	return $ret;
}

$opengraph_meta = array();

function cincopa_mp_plugin($content)
{
	global $opengraph_meta;
	$cincopa_rss = get_site_option('CincopaRss');

	$cincopa_async = get_site_option('CincopaAsync');
	if (strpos($_SERVER['REQUEST_URI'], 'tcapc=true'))
		$cincopa_async = 'async';
	else if (strpos($_SERVER['REQUEST_URI'], 'tcapc=false'))
		$cincopa_async = 'plain';

	if (strpos($_SERVER['REQUEST_URI'], 'cpdisable=true'))
		return $content;

	$cincopa_excerpt_rt = get_site_option('CincopaExcerpt');

	if ($cincopa_excerpt_rt == 'remove' && (is_search() || is_category() || is_archive() || is_home()))
		return preg_replace(CINCOPA_REGEXP, '', $content);
	else if (is_feed()) {
		if ($cincopa_rss == 'full') {
			if ($cincopa_async == 'async') {
				return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_async_plugin_callback', $content));
			} else {
				preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_opengraph_meta_tags_callback', $content);
				return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_plugin_callback', $content));
			}
		} else {
			return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_feed_plugin_callback', $content));
		}
	} else if ($cincopa_async == 'async')
		return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_async_plugin_callback', $content));
	else {
		// add cincopa did into array for open graph generation
		preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_opengraph_meta_tags_callback', $content);
		return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_plugin_callback', $content));
	}
}

function cincopa_mp_plugin_rss($content)
{
	$cincopa_rss = get_site_option('CincopaRss');

	$cincopa_async = get_site_option('CincopaAsync');
	if (strpos($_SERVER['REQUEST_URI'], 'tcapc=true'))
		$cincopa_async = 'async';
	else if (strpos($_SERVER['REQUEST_URI'], 'tcapc=false'))
		$cincopa_async = 'plain';

	if ($cincopa_rss == 'full') {
		if ($cincopa_async == 'async') {
			return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_async_plugin_callback', $content));
		} else {
			preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_opengraph_meta_tags_callback', $content);
			return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_plugin_callback', $content));
		}
	} else {
		return (preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_feed_plugin_callback', $content));
	}
}


function cincopa_plugin_shortcode($atts, $content, $tag)
{
	global $opengraph_meta;

	$cincopa_rss = get_site_option('CincopaRss');

	$cincopa_async = get_site_option('CincopaAsync');
	if (strpos($_SERVER['REQUEST_URI'], 'tcapc=true'))
		$cincopa_async = 'async';
	else if (strpos($_SERVER['REQUEST_URI'], 'tcapc=false'))
		$cincopa_async = 'plain';

	if (strpos($_SERVER['REQUEST_URI'], 'cpdisable=true'))
		return $content;

	$cincopa_excerpt_rt = get_site_option('CincopaExcerpt');

	if ($cincopa_excerpt_rt == 'remove' && (is_search() || is_category() || is_archive() || is_home()))
		return '';
	else if (is_feed()) {
		if ($cincopa_rss == 'full') {
			if ($cincopa_async == 'async') {
				return cincopa_mp_async_plugin_callback($atts, true);
			} else {
				cincopa_mp_opengraph_meta_tags_callback($atts, true);
				return cincopa_mp_plugin_callback($atts, true);
			}
		} else {
			return cincopa_mp_feed_plugin_callback($atts, true);
		}
	} else if ($cincopa_async == 'async') {
		return cincopa_mp_async_plugin_callback($atts, true);
	} else {
		cincopa_mp_opengraph_meta_tags_callback($atts, true);
		return cincopa_mp_plugin_callback($atts, true);
	}
}
add_shortcode('cincopa', 'cincopa_plugin_shortcode');
add_filter('the_content_feed', 'cincopa_mp_plugin_rss');
add_filter('the_excerpt_rss', 'cincopa_mp_plugin_rss');
add_filter('comment_text', 'cincopa_mp_plugin');

add_action('bp_get_activity_content_body', 'cincopa_mp_plugin');
add_action('bp_get_the_topic_post_content', 'cincopa_mp_plugin');


// Hook for adding admin menus
// http://codex.wordpress.org/Adding_Administration_Menus

// register CincopaWidget widget
add_action('widgets_init', 'cincopa_widget_init');
function cincopa_widget_init()
{
	return register_widget("CincopaWidget");
}

//wp_oembed_add_provider('//www.cincopa.com/*', '//www.cincopa.com/media-platform/oembed.aspx');


if (get_site_option('cincopa_welcome_notice') != cincopa_mp_plugin_ver())
	add_action('admin_notices', 'cincopa_mp_activation_notice');

$open_graph_mode = get_site_option('CincopaOpenGraph');
if ($open_graph_mode == 1) {
	add_action('wp_head', 'cincopa_mp_buffer_start');
	add_action('wp_footer', 'cincopa_mp_buffer_end');

	add_action('wp_head', 'cincopa_mp_opengraph_meta_tags');
	add_filter('language_attributes', 'cincopa_mp_opengraph_add_prefix');
}

function cincopa_mp_buffer_start()
{
	ob_start("cincopa_mp_content_callback");
}
function cincopa_mp_buffer_end()
{
	ob_end_flush();
}
function cincopa_mp_content_callback($buffer)
{

	$meta_list = cincopa_mp_get_opengraph_meta_list();

	if (!empty($meta_list)) {
		// modify buffer here, and then return the updated code
		$buffer = str_replace(cincopa_mp_opengraph_placeholder(), $meta_list, $buffer);
	}

	return $buffer;
}

function cincopa_mp_get_opengraph_meta_list()
{
	global $opengraph_meta;

	$opengraph_list = '';
	foreach ($opengraph_meta as $key => $item)
		$opengraph_list .= $item;

	return $opengraph_list;
}
/**
 * Add Open Graph XML prefix to <html> element.
 *
 * @uses apply_filters calls 'opengraph_prefixes' filter on RDFa prefix array
 */
function cincopa_mp_opengraph_add_prefix($output)
{
	$prefixes = array(
		'og' => '//ogp.me/ns#'
	);
	$prefixes = apply_filters('_cincopa_mp_opengraph_prefixes', $prefixes);

	$prefix_str = '';
	foreach ($prefixes as $k => $v) {
		$prefix_str .= $k . ': ' . $v . ' ';
	}
	$prefix_str = trim($prefix_str);

	if (preg_match('/(prefix\s*=\s*[\"|\'])/i', $output)) {
		$output = preg_replace('/(prefix\s*=\s*[\"|\'])/i', '${1}' . $prefix_str, $output);
	} else {
		$output .= ' prefix="' . $prefix_str . '"';
	}
	return $output;
}

function cincopa_mp_opengraph_meta_tags_callback($match, $fromShortcodeCallback = false)
{
	global $opengraph_meta;
	$fid = $fromShortcodeCallback ? $match[0] : $match[1];
	if (empty($fid))
		return '';

	$opengraph_meta_item =
		'<meta property="og:image" content="//www.cincopa.com/media-platform/api/thumb_open.aspx?fid=' . urlencode(trim($fid)) . '&size=large">' . "\n" .
		'<meta name="twitter:image" content="//www.cincopa.com/media-platform/api/thumb_open.aspx?fid=' . urlencode(trim($fid)) . '&size=large">' . "\n";
	if (count($match) > 1) {
		$opengraph_meta[trim($match[1])] = $opengraph_meta_item;
	} else {
		$opengraph_meta[trim($match[0])] = $opengraph_meta_item;
	}
}
function cincopa_mp_opengraph_placeholder()
{
	return '<!-- [CincopaOpenGraph] -->';
}
function cincopa_mp_opengraph_meta_tags()
{

	if (!is_singular()) {
		echo cincopa_mp_opengraph_placeholder();
		return;
	}

	$post = get_queried_object();
	$content = $post->post_content;
	$meta = preg_replace_callback(CINCOPA_REGEXP, 'cincopa_mp_opengraph_meta_tags_callback', $content);

	echo cincopa_mp_get_opengraph_meta_list();
}

/////////////////////////////////
// #AB# dropdown menu functions
/////////////////////////////////

// register js and style on initialization
add_action('init', 'cincopa_mp_register_script');
function cincopa_mp_register_script()
{

	if (is_admin()) {
		// $elementor_edit_active = \Elementor\Plugin::$instance->editor->is_edit_mode();

		/* detect WPBakery Page Builder */
		function is_WPBakery_build() {
			return function_exists( 'vc_is_inline' ) && vc_is_inline() ? true : false;
		}

		/* detect preview/editor mode in Elementor */
		$current_url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$elementor_active = strpos($current_url, 'action=elementor');

		function cincopa_elementor_frontend_scripts() {
			$token = get_site_option('cincopa_cp_mt_api_token');
			$cincopa_templates =get_site_option('CincopaTemplates');
			if(!$cincopa_templates){
				$cincopa_templates = DEFAULT_TEMPLATES;
			}

			if (!$token) {
				$token = get_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', true);
			}

			wp_register_script('cincopa-script', plugins_url('js.cincopa.js', __FILE__), array(), '20130425.4');
			wp_localize_script(
				'cincopa-script',
				'cincopa_cp_mt_options',
				array(
					'api_token' => $token,
					'site_url' => get_site_url(),
					'authorize_url' => cincopa_mp_mt_get_authorize_url(),
					'wp_bakery_build' => is_WPBakery_build(),
					'cincopa_defaults' => $cincopa_templates
				)
			);
			wp_enqueue_script('cincopa-script');
		}

		function cincopa_elementor_frontend_stylesheets(){
			wp_register_style('cincopa-style', plugins_url('css.cincopa.css', __FILE__), false, '20130425.4', 'all');
			wp_enqueue_style('cincopa-style');
		}

		if (cincopa_wpse_is_gutenberg_editor() && !$elementor_active && !is_WPBakery_build()) {
			cincopa_loadMyBlock();
		} else {
			if ($elementor_active) {
				add_action('elementor/editor/before_enqueue_scripts', 'cincopa_elementor_frontend_scripts');
				add_action('elementor/editor/before_enqueue_styles', 'cincopa_elementor_frontend_stylesheets');
			} else {	
				cincopa_elementor_frontend_scripts();
				cincopa_elementor_frontend_stylesheets();
			}		
		}
	}
}

function add_cincopa_oembed_provider() {
    wp_oembed_add_provider( '#https://([a-zA-Z0-9-]+\.)?cincopa\.com/.*#i', 'https://www.cincopa.com/media-platform/oembed.aspx', true );
}
add_action( 'init', 'add_cincopa_oembed_provider' );

/**
 * Check if Classic Editor plugin is active.
 *
 */
function cincopa_is_classic_editor_plugin_active()
{

	if (!function_exists('is_plugin_active')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if (is_plugin_active('classic-editor/classic-editor.php')) {
		return true;
	}

	return false;
}


function cincopa_wpse_is_gutenberg_editor()
{
	// Gutenberg plugin is installed and activated.
	$gutenberg = false !== has_filter('replace_editor', 'gutenberg_init');

	// Block editor since 5.0.
	$block_editor = version_compare($GLOBALS['wp_version'], '5.0-beta', '>');

	if (!$gutenberg && !$block_editor) {
		return false;
	}

	if (cincopa_is_classic_editor_plugin_active()) {
		$editor_option       = get_option('classic-editor-replace');
		$block_editor_active = array('no-replace', 'block');

		return in_array($editor_option, $block_editor_active, true);
	}

	return true;
}


function cincopa_loadMyBlock()
{

	wp_register_style(
		'cp-editor-styles',
		plugins_url('css.blockeditor.css', __FILE__),
		array('wp-edit-blocks'),
		filemtime(plugin_dir_path(__FILE__) . 'css.blockeditor.css')
	);

	wp_register_script(
		'cp-editor-script',
		plugin_dir_url(__FILE__) . 'js.cpblockeditor.js',
		array(
			'wp-blocks',
			'wp-dom-ready',
			'wp-element'
		)
	);

	register_block_type(
		'cincopa/embed',
		array(
			'editor_style' => 'cp-editor-styles',
			'editor_script' => 'cp-editor-script',
		)
	);

	$token = get_site_option('cincopa_cp_mt_api_token');
	if (!$token) {
		$token = get_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', true);
	}

	$cincopa_templates =get_site_option('CincopaTemplates');
	if(!$cincopa_templates){
		$cincopa_templates = DEFAULT_TEMPLATES;
	}


	wp_localize_script(
		'cp-editor-script',
		'cincopa_cp_mt_options',
		array(
			'api_token' => $token,
			'site_url' => get_site_url(),
			'authorize_url' => cincopa_mp_mt_get_authorize_url(),
			'cincopa_defaults' => $cincopa_templates
		)
	);
	wp_enqueue_script('cincopa-script');
}

/////////////////////////////////
// dashboard widget
//////////////////////////////////

// action function for above hook

function cincopa_mp_isAdmin()
{
	return !function_exists('is_site_admin') || is_site_admin() == true;
}



function cincopa_mp_mt_add_pages()
{
	// Add a new submenu under Options:	
	add_options_page('Cincopa Options', 'Cincopa Options', 'edit_pages', 'cincopaoptions', 'cincopa_mp_mt_options_page');
}
add_action('admin_menu', 'cincopa_mp_mt_add_pages');


/*
 * seting cookie in admin init hook in order to avoid unexpected errors
 */
function cincopa_mp_mt_setcookie()
{

	if (sanitize_text_field(isset($_GET['page'])) && sanitize_text_field($_GET['page'] == 'cincopaoptions')) {

		if (empty($_COOKIE["csrfToken"]))
			setcookie('csrfToken', md5(uniqid(rand(), true)));
	}
}

add_action('admin_init', 'cincopa_mp_mt_setcookie');

function cincopa_mp_mt_options_page()
{


	if (strpos($_SERVER['QUERY_STRING'], 'hide_note=welcome_notice')) {
		update_site_option('cincopa_welcome_notice', cincopa_mp_plugin_ver());
		echo "<script type=\"text/javascript\">	document.location.href = '" . $_SERVER['HTTP_REFERER'] . "'; </script>";
		exit;
	}

	$cincopa_excerpt = get_site_option('CincopaExcerpt');
	$cincopa_async = get_site_option('CincopaAsync');
	$cincopa_rss = get_site_option('CincopaRss');
	$cincopa_opengraph = get_site_option('CincopaOpenGraph');
	$cincopa_templates =get_site_option('CincopaTemplates');




	if(!$cincopa_templates){
		$cincopa_templates = DEFAULT_TEMPLATES;
	}

	if ($cincopa_opengraph === false) {
		// enabled by default
		update_site_option('CincopaOpenGraph', 1);
		$cincopa_opengraph = 1;
	}

	if (sanitize_text_field(isset($_POST['submit']))) {


		if (!isset($_POST['cincopa-settings']) || !wp_verify_nonce($_POST['cincopa-settings'], 'cincopa-settings')) {
			echo "nope";
			exit();
		}

		if (sanitize_text_field($_POST['csrfToken']) != $_COOKIE['csrfToken']) {
			echo "nope";
			exit();
		}

		if (cincopa_mp_isAdmin()) {

			if (sanitize_text_field(isset($_POST['asyncRel']))) {
				$cincopa_async = sanitize_text_field($_POST['asyncRel']);
				update_site_option('CincopaAsync', $cincopa_async);
			}

			if (sanitize_text_field(isset($_POST['rssRel']))) {
				$cincopa_rss = sanitize_text_field($_POST['rssRel']);
				update_site_option('CincopaRss', $cincopa_rss);
			}
		}

		if (sanitize_text_field(isset($_POST['embedRel']))) {
			$cincopa_excerpt = sanitize_text_field($_POST['embedRel']);
			update_site_option('cincopaexcerpt', $cincopa_excerpt);
		}
		if (sanitize_text_field(isset($_POST['open_graph'])) && sanitize_text_field($_POST['open_graph'] == 1)) {
			$cincopa_opengraph = 1;
			update_site_option('CincopaOpenGraph', 1);
		} else {
			update_site_option('CincopaOpenGraph', 0);
			$cincopa_opengraph = 0;
		}


		$cincopa_templates = DEFAULT_TEMPLATES;
		if (sanitize_text_field(isset($_POST['cp_video_template']))) {
			$cincopa_templates['video'] =  sanitize_text_field($_POST['cp_video_template']);

		}
		if (sanitize_text_field(isset($_POST['cp_image_template']))) {
			$cincopa_templates['image'] =  sanitize_text_field($_POST['cp_image_template']);

		}
		if (sanitize_text_field(isset($_POST['cp_audio_template']))) {
			$cincopa_templates['audio'] =  sanitize_text_field($_POST['cp_audio_template']);

		}
		if (sanitize_text_field(isset($_POST['cp_unknown_template']))) {
			$cincopa_templates['unknown'] =  sanitize_text_field($_POST['cp_unknown_template']);
		}

		update_site_option('CincopaTemplates', $cincopa_templates);




		echo "<div id=\"updatemessage\" class=\"updated fade\"><p>Cincopa settings updated.</p></div>\n";
		echo "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";
	}
	$tpl_checked = 'checked="checked"';
	$disp_excerpt2 = $cincopa_excerpt == 'clean' ? $tpl_checked : '';
	$disp_excerpt3 = $cincopa_excerpt == 'full' ? $tpl_checked : '';
	$disp_excerpt4 = $cincopa_excerpt == 'remove' ? $tpl_checked : '';
	$disp_excerpt1 = $cincopa_excerpt == '' || $cincopa_excerpt == 'nothing' ? $tpl_checked : '';

	$disp_async2 = $cincopa_async == 'async' ? $tpl_checked : '';
	$disp_async1 = $cincopa_async == '' || $cincopa_async == 'plain' ? $tpl_checked : '';

	$disp_rss2 = $cincopa_rss == 'full' ? $tpl_checked : '';
	$disp_rss1 = $cincopa_rss == '' || $cincopa_rss == 'thumb' ? $tpl_checked : '';

	$disp_opengraph = ($cincopa_opengraph == 1) ? $tpl_checked : '';

?>
	<div class="wrap">
		<h2>Cincopa Configuration </h2>
		<div class="postbox-container" style="clear: both;">
			<div class="metabox-holder">
				<div class="meta-box-sortables">
					<form action="" method="post" id="cincopa-conf">
						<?php $nonce = wp_create_nonce( 'cincopa-settings' ); ?>
						<input type="hidden" name="cincopa-settings" value="<?php echo $nonce; ?>" />
						<div class="handlediv" title="Click to toggle">

						</div>
						<input type="hidden" name="csrfToken" value="<?php echo (esc_html($_COOKIE['csrfToken'])); ?>" />
						<div id="cincopa_settings" class="postbox">
							<h3 class="hndle">
								<span>Cincopa Settings</span>
							</h3>
							<div class="inside" style="width:600px;">
								<?php cincopa_mp_mt_token_handler(); ?>
								<table class="form-table">

									<tr style="width:100%;">
										<th valign="top" scrope="row">
											<label>
												Excerpt Handling (<a target="_blank" href="//help.cincopa.com/entries/448859-wordpress-plugin-settings-page?utm_source=wpplugin&utm_medium=whatever&utm_campaign=#excerpt">what?</a>):
											</label>
										</th>
										<td valign="top">
											<input type="radio" <?php echo (esc_html($disp_excerpt1)); ?> id="embedCustomization0" name="embedRel" value="nothing" />
											<label for="embedCustomization0">Do nothing (default Wordpress behavior)</label>
											<br />
											<input type="radio" <?php echo (esc_html($disp_excerpt2)); ?> id="embedCustomization1" name="embedRel" value="clean" />
											<label for="embedCustomization1">Clean excerpt (do not show gallery)</label>
											<br />
											<input type="radio" <?php echo (esc_html($disp_excerpt4)); ?> id="embedCustomization3" name="embedRel" value="remove" />
											<label for="embedCustomization3">Remove gallery (do not show gallery in all non post pages)</label>
											<br />
											<input type="radio" <?php echo (esc_html($disp_excerpt3)); ?> id="embedCustomization2" name="embedRel" value="full" />
											<label for="embedCustomization2">Full excerpt (show gallery)</label>
											<br />

										</td>
									</tr>

									<tr>
										<th valign="top" scrope="row">
											<label for="open_graph">
												Use Open Graph Tags? (<a target="_blank" href="//help.cincopa.com/entries/448859-wordpress-plugin-settings-page?utm_source=wpplugin&utm_medium=whatever&utm_campaign=#opengraph">what?</a>):
											</label>
										</th>
										<td valign="top">
											<input type="checkbox" <?php echo (esc_html($disp_opengraph)); ?> id="open_graph" name="open_graph" value="1" />
											<br />
										</td>
									</tr>


									<?php

									if (cincopa_mp_isAdmin()) {
									?>


										<tr style="width:100%;">
											<th valign="top" scrope="row">
												<label for="cincopaasync">
													Async Engine (<a target="_blank" href="//help.cincopa.com/entries/448859-wordpress-plugin-settings-page?utm_source=wpplugin&utm_medium=whatever&utm_campaign=#async">what?</a>):
												</label>
											</th>
											<td valign="top">

												<input type="radio" <?php echo (esc_html($disp_async1)); ?> id="asyncCustomization0" name="asyncRel" value="plain" />
												<label for="asyncCustomization0">Plain Sync</label>
												<br />
												<input type="radio" <?php echo (esc_html($disp_async2)); ?> id="asyncCustomization1" name="asyncRel" value="async" />
												<label for="asyncCustomization1">Advanced Async </label>
												<br />


											</td>
										</tr>

										<tr style="width:100%;">
											<th valign="top" scrope="row">
												<label for="cincoparss">
													RSS handling (<a target="_blank" href="//help.cincopa.com/entries/448859-wordpress-plugin-settings-page?utm_source=wpplugin&utm_medium=whatever&utm_campaign=#rsshandling">what?</a>):
												</label>
											</th>
											<td valign="top">

												<input type="radio" <?php echo (esc_html($disp_rss1)); ?> id="rss0" name="rssRel" value="thumb" />
												<label for="rss0">Show thumbnail</label>
												<br />
												<input type="radio" <?php echo (esc_html($disp_rss2)); ?> id="rss1" name="rssRel" value="full" />
												<label for="rss1">Full gallery</label>
												<br />


											</td>
										</tr>

										<tr class="rid-container">
											<td valign="top" style="display: flex; flex-direction: column; gap: 6px; justify-content: flex-start; width:100%;">
												<label style="font-weight: 600;" for="cp_video_template">Video Template</label>
												<input type="text"  id="cp_video_template" name="cp_video_template" value="<?php echo $cincopa_templates['video']; ?>" />
												<br />
												<label style="font-weight: 600;" for="cp_video_template">Audio Template</label>
												<input type="text"  id="cp_video_template" name="cp_image_template" value="<?php echo $cincopa_templates['image']; ?>" />
												<br />
												<label style="font-weight: 600;" for="cp_video_template">Image Template</label>
												<input type="text"  id="cp_video_template" name="cp_audio_template" value="<?php echo $cincopa_templates['audio']; ?>" />
												<br />
												<label style="font-weight: 600;" for="cp_video_template">Non media Template</label>
												<input type="text"  id="cp_video_template" name="cp_unknown_template" value="<?php echo $cincopa_templates['unknown']; ?>" />
												<br />


											</td>
										</tr>




									<?php
									}

									?>


									<tr style="width:100%;">
										<th valign="top" scrope="row" colspan=2>
											Note:
											<ol>
												<li>Use this PHP code to add a gallery directly to your template : <br>&nbsp;&nbsp;&nbsp; <i>&lt;?php echo cincopa_mp_cincopa_tag("GALLERY ID"); ?&gt;</i></li>
											</ol>
										</th>
									</tr>


								</table>
							</div>
						</div>
						<div class="submit">
							<input type="submit" class="button-primary" name="submit" value="Update &raquo;" />
						</div>
					</form>
				</div>
			</div>
		</div>

	</div>
<?php


}

function cincopa_mp_mt_token_handler()
{


	$return_from_auth = false;
	$token = '';
	$token_type = '';
	if (sanitize_text_field(isset($_POST['submit']))) {

		if (sanitize_text_field(isset($_POST['delete_token']))) {

			if (is_super_admin()) {
				delete_site_option('cincopa_cp_mt_api_token');
				delete_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token');
			} else {
				delete_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token');
			}
		} else {
			$token  =  sanitize_text_field($_POST['api_token']);
			if (sanitize_text_field(isset($_POST['token_type']))) {
				$token_type  = sanitize_text_field($_POST['token_type']);
				if ($token_type == 'for_all') {
					update_site_option('cincopa_cp_mt_api_token', $token);
				} else {
					delete_site_option('cincopa_cp_mt_api_token');
					update_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', $token);
				}
			} else {
				update_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', $token);
			}
		}
		echo "<div id=\"updatemessage\" class=\"updated fade\"><p>Token saved for " . ($token_type == 'for_all' ? "all users" : "your account") . ".</p></div>\n";
		echo "<script type=\"text/javascript\">setTimeout(function(){jQuery('#updatemessage').hide('slow');}, 3000);</script>";
	} else {
		if (!empty($_GET['api_token'])  && esc_html($_GET['api_token'])) {
			$return_from_auth = true;
			$token = esc_html($_GET['api_token']);
		} else {
			$token = get_site_option('cincopa_cp_mt_api_token');
			if (!$token) {
				$token = get_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', true);
			}
		}
	}

	$api_token_for_all  = false;
	if (get_site_option('cincopa_cp_mt_api_token') || (isset($_GET['api_token']) && !isset($_POST['token_type'])) ) {
		$api_token_for_all = true;
	}

?>
	<script>
		jQuery(document).ready(function($) {
			var token = '<?php echo $token; ?>';
			var tokenModeforAll = '<?php echo $api_token_for_all; ?>' ? true  : false;
			if (token == 'not_authorized') {				
				badToken('not_authorized');
				jQuery('#cincopa-token').show();
				return;
			}
			jQuery.ajax({
				url: 'https://api.cincopa.com/v2/ping.json?api_token=' + token,
				dataType: 'json',
				success: function(data) {
					if (data.success) {
						if(data.permissions.indexOf('asset.*') == -1 &&  data.permissions.indexOf('gallery.*') == -1 ){
							if ( (data.permissions.indexOf('asset.*') == -1 &&  data.permissions.indexOf('asset.read') == -1 || data.permissions.indexOf('asset.upload') == -1 ) || (data.permissions.indexOf('gallery.*') == -1 &&  data.permissions.indexOf('gallery.read') == -1) ) {
								badToken('invalid');
							}
						}
					} else {
						badToken('invalid');
					}
				},
				error: function(err) {
					var error;
					try {
						error = JSON.parse(err.responseText);
					} catch (ex) {
						console.log(ex);
					}
					if (error && error.message) {
						badToken(error.message || 'invalid');
					}
				},
				complete: function() {
					jQuery('#cincopa-token').show();
				}
			})

			$('.cincopa_delete_token').on('click', function() {
				$('#cincopa-conf').append('<input type="hidden" name="delete_token" value="true" />');
				$('input[type="submit"]').click()
			})

			function badToken(message) {
				jQuery('.cincopa_token_status').html('Token status - ' + message);
				if( $('.cincopa_token_issue_not_admin').length && tokenModeforAll ){
					$('.cincopa_token_issue_not_admin').show();
				}else{
					jQuery('.cincopa_create_token').show();
				}
				jQuery('.cincopa_delete_token').remove();
			}
		});
	</script>
		<div action="" method="post" id="cincopa-token" style="display:none; padding-bottom: 20px;border-bottom: 1px solid #eee;">
			<input type="hidden" name="api_token" value="<?php echo $token ?>" />
			<?php if (is_super_admin()) { ?>

				<div class="inside" style="width:600px;padding: 0;margin-top: 0;">
					<table class="form-table">
						<tr style="width:100%;">
							<th valign="top" scrope="row">
								<label for="cincopaaccesstoken">
									Cincopa access token:
								</label>
							</th>
							<td valign="top">
								<input type="radio" <?php echo $api_token_for_all ? 'checked' : '' ?> id="allowTokenUsers" name="token_type" value="for_all" />
								<label for="allowTokenUsers">One token for all WordPress users</label>
								<br />
								<input type="radio" <?php echo !$api_token_for_all ? 'checked' : '' ?> id="allowTokenAdmin" name="token_type" value="for_user" />
								<label for="allowTokenAdmin">Individual tokens for each WordPress user. (each user will need his own Cincopa account)</label>
							</td>
						</tr>
					</table>
				</div>

			<?php } else { ?>

				<div class="inside" style="width:600px;padding: 0;margin-top: 0;">
					<table class="form-table">
						<tr style="width:100%;display: none;">
							<td valign="top" style="padding-left: 0;">
								<input type="hidden" id="allowTokenAdmin" name="token_type" value="for_user" />								
							</td>
						</tr>
						<tr style="width:100%;display:none" class="cincopa_token_issue_not_admin">
							<td valign="top" style="background: #ededed;padding: 10px;font-weight: 600;color: #2271b1;">
								Contact your site admin to fix this issue.
							</td>
						</tr>
					</table>
				</div>

			<?php } ?>
			<span class="cincopa_token_status" style="font-weight: bold;display: block;">Token status - Ok</span>
			<a class="cincopa_create_token button-primary" href="<?php echo cincopa_mp_mt_get_authorize_url(); ?>" style="display:none;margin-top: 10px;">Get token from Cincopa</a>
			<?php if (is_super_admin()) { ?>
				<?php if ($token) { ?>
					<a class="cincopa_delete_token button-primary" style="margin-top: 10px;">Delete token</a>
				<?php } ?>
			<?php } else { ?>
				<?php if (!$api_token_for_all) { ?>
					<a class="cincopa_delete_token button-primary" style="margin-top: 10px;">Delete token</a>
				<?php } ?>
			<?php } ?>
		</div>
	<?php }


if (!class_exists('CincopaWidget')) {

	/**
	 * CincopaWidget Class
	 */
	class CincopaWidget extends WP_Widget
	{
		function __construct()
		{
			parent::__construct(false, 'Cincopa Gallery Widget');
		}

		/** @see WP_Widget::widget */
		function widget($args, $instance)
		{
			extract($args);

			if (strpos($instance['galleryid'], 'cincopa')) {
				$galID = preg_replace(CINCOPA_REGEXP, '$1', $instance['galleryid']);
				$gallery = cincopa_plugin_shortcode([$galID, $args["widget_id"]], '', 'cincopa'); //  cincopa_mp_plugin($instance['galleryid'],true);	
			} else {
				$gallery =  cincopa_plugin_shortcode([$instance['galleryid'], $args["widget_id"]], '', 'cincopa');
			}

			echo $gallery;
		}

		/** @see WP_Widget::update */
		function update($new_instance, $old_instance)
		{
			return $new_instance;
		}

		/** @see WP_Widget::form */
		function form($instance)
		{
			if (isset($instance['galleryid']))
				$galleryid = esc_attr($instance['galleryid']);
			else
				$galleryid = '';
	?>
			<p>
				<label for="" <?php echo $this->get_field_id('galleryid'); ?>"><?php _e('Gallery ID:'); ?> <a target="_blank" href="//help.cincopa.com/entries/405593-how-do-i-add-a-gallery-to-my-wordpress-sidebar?utm_source=wpplugin&utm_medium=whatever&utm_campaign=">what?</a> <input class="widefat" id="" <?php echo $this->get_field_id('galleryid'); ?>" name="<?php echo $this->get_field_name('galleryid'); ?>" type="text" value="<?php echo (esc_html($galleryid)); ?>" />
				</label>
			</p>
	<?php
		}
	} // class CincopaWidget

}

// http://www.aaronrussell.co.uk/blog/improving-wordpress-the_excerpt/

function cincopa_mp_improved_trim_excerpt($text)
{
	global $post;
	if ('' == $text) {
		$text = get_the_content('');

		$cincopa_excerpt_rt = get_site_option('CincopaExcerpt');

		if ($cincopa_excerpt_rt == 'clean')
			$text = preg_replace(CINCOPA_REGEXP, '', $text);

		$text = apply_filters('the_content', $text);

		if ($cincopa_excerpt_rt == 'full')
			return $text;

		$text = str_replace(']]>', ']]&gt;', $text);
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);

		$text = strip_tags($text, '<' . 'p' . '>');
		$excerpt_length = 80;
		$words = explode(' ', $text, $excerpt_length + 1);
		if (count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '[...]');
			$text = implode(' ', $words);
		}
	}

	return $text;
}

$cincopa_excerpt_rt = get_site_option('CincopaExcerpt');
if ($cincopa_excerpt_rt == 'full' || $cincopa_excerpt_rt == 'clean') {
	remove_filter('get_the_excerpt', 'wp_trim_excerpt');
	add_filter('get_the_excerpt', 'cincopa_mp_improved_trim_excerpt');
}

function cincopa_mp_activation_notice()
{ ?>
	<div id="message" class="updated fade">
		<p style="line-height: 150%">
			<a href="//www.cincopa.com/wordpress/welcome?utm_source=wpplugin&utm_medium=whatever" target=_blank><img alt="cincopa" src="https://www.cincopa.com/_cms/design15/icons/favicon-32.png?affdata=wordpress-plugin,welcome-msg" width="15px" /></a>&nbsp;<strong>Welcome to Cincopa Rich Media Plugin</strong> - the most popular way to add videos, photo galleries, slideshows, Cooliris gallery, podcast and music to your site.
		</p>
		<p>
			On every post page (above the text box) you'll find this <img src="<?php echo cincopa_mp_pluginURI() ?>/media-cincopa.gif" /> icon, click on it to start or use sidebar Widgets (Appearance menu).
			Visit <a href="//www.cincopa.com/wordpress/welcome?utm_source=wpplugin&utm_medium=whatever" target=_blank>Cincopa Welcome Page</a> for more info.
		</p>
		<p>

			<input type="button" class="button" value="Cincopa Options Page" onclick="document.location.href = 'options-general.php?page=cincopaoptions';" />

			<input type="button" class="button" value="Hide this message" onclick="document.location.href = 'options-general.php?page=cincopaoptions&amp;hide_note=welcome_notice';" />

			<input type="button" class="button" value="Video Tutorial" onclick="window.open('//webinars.cincopa.com/?utm_source=wpplugin&utm_medium=whatever#cat=wordpress');" />

		</p>

	</div>


	<?php

	if (get_site_option('cincopa_installed') != 'true') {
		update_site_option('cincopa_installed', 'true');
	}
}


add_action('admin_footer-post-new.php', 'cincopa_mediaDefault_script');
add_action('admin_footer-post.php', 'cincopa_mediaDefault_script');
add_action('admin_footer-index.php', 'cincopa_mediaDefault_script');

function cincopa_mediaDefault_script()
{
	?>
	<script type="text/javascript">
		var cincopa_popup_timer = null;

		function cincopa_check_popup() {
			if (jQuery(".media-menu-item:contains('Cincopa')").closest('.supports-drag-drop').css('display') == 'none')
				return;

			if (jQuery(".media-menu-item:contains('Cincopa')").length > 0) {
				jQuery(".media-menu-item:contains('Cincopa')")[0].click();
				clearInterval(cincopa_popup_timer);
			}
		}
	</script>
<?php
}


function cp_embed_wrapper( $html, $url, $attr, $post_ID ) {
    $style = '';
	$classes = [];
	if (stripos($url, "cincopa.com") !== FALSE) {
		$classes[] = 'wp-cp_embed_wrapper';
		$style='<style>.wp-cp_embed_wrapper iframe{width:100%;height:100%;aspect-ratio:16/9;}</style>';
    }

	return '<div class="' . implode( ' ', $classes ) . '">' . $style . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'cp_embed_wrapper', 10, 4 );


/* WPBakery Builder Addon */
function generateRandomFidClass($length = 25) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if(function_exists('cincopa_mp_mt_get_authorize_url')){
	if (function_exists('vc_add_shortcode_param')) {
		vc_add_shortcode_param( 'insert_button_cincopa', 'insert_gallery_cincopa' );
		function insert_gallery_cincopa() {
		return '<button class="btn_insert_from_cincopa">Insert from Cincopa</button>';
		}

		vc_add_shortcode_param( 'login_button_cincopa', 'login_cincopa' );
		function login_cincopa() {
		return '<button class="btn_login_cincopa">Login to Cincopa</button>';
		}
	}

	add_action('vc_before_init', 'add_cincopa_vidget_vc' );

	function add_cincopa_vidget_vc(){
		$token = get_site_option('cincopa_cp_mt_api_token');

		if (!$token) {
			$token = get_user_meta(get_current_user_id(), 'cincopa_cp_mt_api_token', true);
		}

		if($token){
			vc_map( 
				array(
				"name" => __("Cincopa Gallery", "cincopa_gallery_widget"),    
				"base" => "cincopa_gallery_widget",      
				"description" => __("Cincopa video and media plug-in", "cincopa_gallery_widget"),
				"category" => __("Cincopa", "cincopa_gallery_widget"),
				"params" => array(
					array(
						"type" => "textfield",
						"holder" => "div",
						"class" => "input_insert_cincopa",
						"heading" => esc_html__("Gallery FID", 'cincopa_gallery_widget'),
						"param_name" => "cincopa-input-insert",
						"value" => "",
						"description" => esc_html__("Insert Gallery FID", 'cincopa_gallery_widget')
					), 
					array(
						"type" => 'insert_button_cincopa',
						"holder" => "div",
						"heading" => esc_html__("", 'cincopa_gallery_widget'),
						"param_name" => "cincopa-btn-insert",
						"value" => "",				
						"description" => esc_html__("", 'cincopa_gallery_widget')
					), 
				)		
				)
			);
		}else{
			vc_map( 
				array(
				"name" => __("Cincopa Login", "cincopa_gallery_widget"),    
				"base" => "cincopa_gallery_widget",      
				"description" => __("Cincopa video and media plug-in", "cincopa_gallery_widget"),
				"category" => __("Cincopa", "cincopa_gallery_widget"),
				"params" => array( 
					array(
						"type" => 'login_button_cincopa',
						"holder" => "div",
						"heading" => esc_html__("", 'cincopa_gallery_widget'),
						"param_name" => "login_button_cincopa",
						"value" => "",				
						"description" => esc_html__("", 'cincopa_gallery_widget')
					), 
				)		
				)
			);
		}		
	}

	add_shortcode('cincopa_gallery_widget','cincopa_gallery_widget_add');

	function cincopa_gallery_widget_add($atts){
		$random = generateRandomFidClass(10);
		if(isset($atts['cincopa-input-insert']))$fid=esc_html($atts['cincopa-input-insert']); else $fid='';
		if($fid !=''){
		?>
			<div class="cincopa-gallery-video">
				<div id="cp_widget_<?php echo $random; ?>"><img src="//www.cincopa.com/media-platform/runtime/loading.gif" style="border:0;" alt="Cincopa WPBakery Addon" /></div>
				<script src="//www.cincopa.com/media-platform/runtime/libasync.js" type="text/javascript"></script>
				<script type="text/javascript">
					cp_load_widget('<?php echo $fid; ?>', "cp_widget_<?php echo $random; ?>");
				</script>
			</div>
		<?php
		}
	}
}

/* WPBakery Builder Addon */


/* Recommends Plugins */

add_action( 'tgmpa_register', 'cincopa_mp__register_required_plugins' );

function cincopa_mp__register_required_plugins() {
	$plugins = [];

	if (is_plugin_active( 'elementor/elementor.php' )) {
		$plugins[] = [
			'name'               => 'Elementor Addon - Cincopa video and media plugin',
			'slug'               => 'elementor-addon',
			'source'             => dirname( __FILE__ ) . '/addons/elementor-addon-1.1.zip',
			'required'           => false,
			'version'            => '1.1',
			'force_activation'   => false,
			'force_deactivation' => false,
			'external_url'       => '',
			'is_callable'        => '',
		];
	}

	if(is_plugin_active('oxygen/functions.php')) {
		
		$plugins[] = [
				'name'               => 'Oxygen Addon - Cincopa video and media plugin',
				'slug'               => 'oxygen-cincopa-addon',
				'source'             => dirname( __FILE__ ) . '/addons/oxygen-cincopa-addon-1.0.zip',
				'required'           => false,
				'version'            => '1.0',
				'force_activation'   => false,
				'force_deactivation' => false,
				'external_url'       => '',
				'is_callable'        => '',
		];
	}

	$config = array(
		'id'           => 'cincopa-video-and-media',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'parent_slug'  => 'plugins.php',
		'capability'   => 'manage_options',
		'has_notices'  => true,
		'dismissable'  => true,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
		'strings' => array(
			'notice_can_install_required'     => _n_noop(
				'This plugin requires the following plugin: %1$s.',
				'This plugin requires the following plugins: %1$s.',
				'cincopa-video-and-media'
			),
			'notice_can_install_recommended'  => _n_noop(
				'This plugin recommends the following plugin: %1$s.',
				'This plugin recommends the following plugins: %1$s.',
				'cincopa-video-and-media'
			),
			'notice_ask_to_update'            => _n_noop(
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'cincopa-video-and-media'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'cincopa-video-and-media'
			),
			'notice_can_activate_required'    => _n_noop(
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'cincopa-video-and-media'
			),
			'notice_can_activate_recommended' => _n_noop(
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'cincopa-video-and-media'
			),			
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'cincopa-video-and-media'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'cincopa-video-and-media'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'cincopa-video-and-media'
			),
			'return'                          => __( 'Return to Required Plugins Installer', 'cincopa-video-and-media' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'cincopa-video-and-media' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'cincopa-video-and-media' ),
			'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'cincopa-video-and-media' ),
			'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'cincopa-video-and-media' ),
			'dismiss'                         => __( 'Dismiss this notice', 'cincopa-video-and-media' ),
			'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'cincopa-video-and-media' ),
			'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'cincopa-video-and-media' ),
			'nag_type'                        => '', 
		)
	);

	tgmpa( $plugins, $config );
}

/* Recommends Plugins */
