<?php
/**
 * Noreve functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package noreve
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function noreve_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on noreve, use a find and replace
		* to change 'noreve' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'noreve', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'noreve' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'noreve_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'noreve_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function noreve_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'noreve_content_width', 640 );
}
add_action( 'after_setup_theme', 'noreve_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function noreve_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'noreve' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'noreve' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'noreve_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function noreve_styles(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	if ($lang == "en" || $lang == "fr") {

		wp_register_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', array(), '1.0', 'all');
		wp_enqueue_style('bootstrap');

		wp_register_style('owl.carousel', get_template_directory_uri() . '/css/owl.carousel.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.carousel');

		wp_register_style('owl.theme.default', get_template_directory_uri() . '/css/owl.theme.default.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.theme.default');

		wp_register_style('owl.animate', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.animate');

		wp_register_style('locomotive', get_template_directory_uri() . '/css/locomotive-scroll.css', array(), '1.0', 'all');
		wp_enqueue_style('locomotive');

		wp_register_style('main', get_template_directory_uri() . '/css/main.css', array(), '2.3', 'all');
		wp_enqueue_style('main');

		wp_register_style('responsive', get_template_directory_uri() . '/css/responsive.css', array(), '2.4', 'all');
		wp_enqueue_style('responsive');
	}

	if ($lang == "ar") {

		wp_register_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.rtl.min.css', array(), '1.0', 'all');
		wp_enqueue_style('bootstrap');

		wp_register_style('owl.carousel', get_template_directory_uri() . '/css/owl.carousel.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.carousel');

		wp_register_style('owl.theme.default', get_template_directory_uri() . '/css/owl.theme.default.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.theme.default');

		wp_register_style('owl.animate', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', array(), '1.0', 'all');
		wp_enqueue_style('owl.animate');

		wp_register_style('locomotive', get_template_directory_uri() . '/css/locomotive-scroll.css', array(), '1.0', 'all');
		wp_enqueue_style('locomotive');

		wp_register_style('main', get_template_directory_uri() . '/css/main-rtl.css', array(), '2.3', 'all');
		wp_enqueue_style('main');

		wp_register_style('responsive', get_template_directory_uri() . '/css/responsive-rtl.css', array(), '2.4', 'all');
		wp_enqueue_style('responsive');
		
	}
}
add_action('wp_enqueue_scripts', 'noreve_styles');

function noreve_scripts(){

	wp_enqueue_script('popperjs', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js', array(), _S_VERSION, true);
	wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js', array(), _S_VERSION, true);
	wp_enqueue_script('fontawesome', 'https://kit.fontawesome.com/aa611fc61f.js', array(), _S_VERSION, true);	
	wp_enqueue_script('jquery', 'https://code.jquery.com/jquery-2.2.4.min.js', array(), _S_VERSION, true);
	wp_enqueue_script('jquery-ui', 'https://code.jquery.com/ui/1.14.1/jquery-ui.min.js', array(), _S_VERSION, true);
	wp_enqueue_script('owl.carousel', get_template_directory_uri().'/js/owl.carousel.min.js', array(), _S_VERSION, true);
	wp_enqueue_script('locomotive-scroll', get_template_directory_uri().'/js/locomotive-scroll.min.js', array(), _S_VERSION, true);	

    // wp_localize_script('ajax-script', 'noreveAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);

}
add_action( 'wp_enqueue_scripts', 'noreve_scripts' );

function enqueue_noreve_last_script(){

    wp_enqueue_script('main', get_template_directory_uri().'/js/main.js', array(), '3.1', true);

}
add_action('wp_enqueue_scripts', 'enqueue_noreve_last_script', 9999);


function noreve_body_class($classes){

	$postId = get_the_id();
	$arrayBBids = getB2b();

    if (!is_front_page() || !$postId == 149 || !$postId == 151 || !$postId == 153){
			$classes[] = 'not-front';
	}
	if(in_array($postId,$arrayBBids)){
		$classes[] = 'b2b-page';
	}else{
		$classes[] = 'b2c-page';
	}
	
    return $classes;
}
add_filter( 'body_class', 'noreve_body_class' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}

/**
 * Generated by the WordPress Option Page generator
 * at http://jeremyhixon.com/wp-tools/option-page/
 */

class SocialMediaAndContacts {
	private $social_media_and_contacts_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'social_media_and_contacts_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'social_media_and_contacts_page_init' ) );
	}

	public function social_media_and_contacts_add_plugin_page() {
		add_menu_page(
			'Social Media and Contacts', // page_title
			'Social Media and Contacts', // menu_title
			'manage_options', // capability
			'social-media-and-contacts', // menu_slug
			array( $this, 'social_media_and_contacts_create_admin_page' ), // function
			'dashicons-admin-generic', // icon_url
			2 // position
		);
	}

	public function social_media_and_contacts_create_admin_page() {
		$this->social_media_and_contacts_options = get_option( 'social_media_and_contacts_option_name' ); ?>

		<div class="wrap">
			<h2>Social Media and Contacts</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'social_media_and_contacts_option_group' );
					do_settings_sections( 'social-media-and-contacts-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function social_media_and_contacts_page_init() {
		register_setting(
			'social_media_and_contacts_option_group', // option_group
			'social_media_and_contacts_option_name', // option_name
			array( $this, 'social_media_and_contacts_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'social_media_and_contacts_setting_section', // id
			'Settings', // title
			array( $this, 'social_media_and_contacts_section_info' ), // callback
			'social-media-and-contacts-admin' // page
		);

		add_settings_field(
			'address', // id
			'Address', // title
			array( $this, 'address_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'email_0', // id
			'Email', // title
			array( $this, 'email_0_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'phone_1', // id
			'Phone', // title
			array( $this, 'phone_1_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'mobile_2', // id
			'Mobile', // title
			array( $this, 'mobile_2_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'whatsapp', // id
			'Whatsapp', // title
			array( $this, 'whatsapp_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);		

		add_settings_field(
			'instagram_3', // id
			'Instagram', // title
			array( $this, 'instagram_3_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'linked_in_4', // id
			'Linked In', // title
			array( $this, 'linked_in_4_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'twitter_x_5', // id
			'Twitter X', // title
			array( $this, 'twitter_x_5_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);

		add_settings_field(
			'tiktok_6', // id
			'Tiktok', // title
			array( $this, 'tiktok_6_callback' ), // callback
			'social-media-and-contacts-admin', // page
			'social_media_and_contacts_setting_section' // section
		);
	}

	public function social_media_and_contacts_sanitize($input) {
		$sanitary_values = array();

		if ( isset( $input['address'] ) ) {
			$sanitary_values['address'] = sanitize_text_field( $input['address'] );
		}

		if ( isset( $input['email_0'] ) ) {
			$sanitary_values['email_0'] = sanitize_text_field( $input['email_0'] );
		}

		if ( isset( $input['phone_1'] ) ) {
			$sanitary_values['phone_1'] = sanitize_text_field( $input['phone_1'] );
		}

		if ( isset( $input['mobile_2'] ) ) {
			$sanitary_values['mobile_2'] = sanitize_text_field( $input['mobile_2'] );
		}

		if ( isset( $input['whatsapp'] ) ) {
			$sanitary_values['whatsapp'] = sanitize_text_field( $input['whatsapp'] );
		}

		if ( isset( $input['instagram_3'] ) ) {
			$sanitary_values['instagram_3'] = sanitize_text_field( $input['instagram_3'] );
		}

		if ( isset( $input['linked_in_4'] ) ) {
			$sanitary_values['linked_in_4'] = sanitize_text_field( $input['linked_in_4'] );
		}

		if ( isset( $input['twitter_x_5'] ) ) {
			$sanitary_values['twitter_x_5'] = sanitize_text_field( $input['twitter_x_5'] );
		}

		if ( isset( $input['tiktok_6'] ) ) {
			$sanitary_values['tiktok_6'] = sanitize_text_field( $input['tiktok_6'] );
		}

		return $sanitary_values;
	}

	public function social_media_and_contacts_section_info() {
		
	}

	public function address_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[address]" id="address" value="%s">',
			isset( $this->social_media_and_contacts_options['address'] ) ? esc_attr( $this->social_media_and_contacts_options['address']) : ''
		);
	}	

	public function email_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[email_0]" id="email_0" value="%s">',
			isset( $this->social_media_and_contacts_options['email_0'] ) ? esc_attr( $this->social_media_and_contacts_options['email_0']) : ''
		);
	}

	public function phone_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[phone_1]" id="phone_1" value="%s">',
			isset( $this->social_media_and_contacts_options['phone_1'] ) ? esc_attr( $this->social_media_and_contacts_options['phone_1']) : ''
		);
	}

	public function mobile_2_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[mobile_2]" id="mobile_2" value="%s">',
			isset( $this->social_media_and_contacts_options['mobile_2'] ) ? esc_attr( $this->social_media_and_contacts_options['mobile_2']) : ''
		);
	}

	public function whatsapp_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[whatsapp]" id="whatsapp" value="%s">',
			isset( $this->social_media_and_contacts_options['whatsapp'] ) ? esc_attr( $this->social_media_and_contacts_options['whatsapp']) : ''
		);
	}

	public function instagram_3_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[instagram_3]" id="instagram_3" value="%s">',
			isset( $this->social_media_and_contacts_options['instagram_3'] ) ? esc_attr( $this->social_media_and_contacts_options['instagram_3']) : ''
		);
	}

	public function linked_in_4_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[linked_in_4]" id="linked_in_4" value="%s">',
			isset( $this->social_media_and_contacts_options['linked_in_4'] ) ? esc_attr( $this->social_media_and_contacts_options['linked_in_4']) : ''
		);
	}

	public function twitter_x_5_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[twitter_x_5]" id="twitter_x_5" value="%s">',
			isset( $this->social_media_and_contacts_options['twitter_x_5'] ) ? esc_attr( $this->social_media_and_contacts_options['twitter_x_5']) : ''
		);
	}

	public function tiktok_6_callback() {
		printf(
			'<input class="regular-text" type="text" name="social_media_and_contacts_option_name[tiktok_6]" id="tiktok_6" value="%s">',
			isset( $this->social_media_and_contacts_options['tiktok_6'] ) ? esc_attr( $this->social_media_and_contacts_options['tiktok_6']) : ''
		);
	}

}
if ( is_admin() )
	$social_media_and_contacts = new SocialMediaAndContacts();

/* 
 * Retrieve this value with:
 * $social_media_and_contacts_options = get_option( 'social_media_and_contacts_option_name' ); // Array of All Options
 * $address = $social_media_and_contacts_options['address']; // Address
 * $email_0 = $social_media_and_contacts_options['email_0']; // Email
 * $phone_1 = $social_media_and_contacts_options['phone_1']; // Phone
 * $mobile_2 = $social_media_and_contacts_options['mobile_2']; // Mobile
 * $whatsapp = $social_media_and_contacts_options['whatsapp']; // Address
 * $instagram_3 = $social_media_and_contacts_options['instagram_3']; // Instagram
 * $linked_in_4 = $social_media_and_contacts_options['linked_in_4']; // Linked In
 * $twitter_x_5 = $social_media_and_contacts_options['twitter_x_5']; // Twitter X
 * $tiktok_6 = $social_media_and_contacts_options['tiktok_6']; // Tiktok
 */


/*Custom Functions*/
function get_custom_main_menu_items(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	// $lang = 'en';
	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items(354);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items(31);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items(32);
	}

	$arrayMainMenu = array();
	foreach ($getMenu as $item) {


		//$arrayMainMenu[] = '<li class="nav-item" ><a href="' . $item->url . '" class="nav-link-item">' . $item->title . '</a></li>';
		$arrayMainMenu[] = '
			<li class="nav-item">
				<a class="nav-link" href="' . $item->url . '">' . $item->title . '</a>
			</li>';
		
	}

	$mainMenuItems = implode('', $arrayMainMenu);

	return $mainMenuItems;
}

function get_bb_main_menu_items(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	// $lang = 'en';
	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items(151);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items(152);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items(153);
	}

	$arrayMainMenu = array();
	foreach ($getMenu as $item) {


		//$arrayMainMenu[] = '<li class="nav-item" ><a href="' . $item->url . '" class="nav-link-item">' . $item->title . '</a></li>';
		$arrayMainMenu[] = '
			<li class="nav-item">
				<a class="nav-link" href="' . $item->url . '">' . $item->title . '</a>
			</li>';
		
	}

	$mainMenuItems = implode('', $arrayMainMenu);

	return $mainMenuItems;
}

function get_custom_bb_top_menu_items(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	// $lang = 'en';
	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items(146);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items(147);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items(148);
	}

	$arrayBBids = getB2b();
	$postId = get_the_id();
	$i = 0;
	$t = 0;
	$arrayMainMenu = array();

	foreach ($getMenu as $item) {

		$btnBrown = "";

		if($i++ == 1){
			$btnBrown = "btn-brown";
		}

		//$arrayMainMenu[] = '<li class="nav-item" ><a href="' . $item->url . '" class="nav-link-item">' . $item->title . '</a></li>';

		$arrayMainMenu[] = '
			<li class="nav-item b2b b2b-'.$t++.' '.$btnBrown.'">
				<a class="nav-link" href="'.$item->url.'">'.$item->title.'</a>
			</li>';
		
	}

	$mainMenuItems = implode('', $arrayMainMenu);

	return $mainMenuItems;
}

function get_custom_bb_top_menu_mobile_items(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	// $lang = 'en';
	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items(146);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items(147);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items(148);
	}

	$arrayBBids = getB2b();
	$postId = get_the_id();
	$i = 0;
	$t = 0;
	$arrayMainMenu = array();

	foreach ($getMenu as $item) {

		$btnBrown = "";

		if($i++ == 1){
			$btnBrown = "bg-gold";
		}

		//$arrayMainMenu[] = '<li class="nav-item" ><a href="' . $item->url . '" class="nav-link-item">' . $item->title . '</a></li>';

		$arrayMainMenu[] = '
			<div class="col-6 b2b b2b-'.$t++.' '.$btnBrown.'">
				<div class="spacer-20"></div>
				<center>
					<a class="nav-link" href="'.$item->url.'">'.$item->title.'</a>
				</center>
				<div class="spacer-20"></div>
			</div>';
		
	}

	$mainMenuItems = implode('', $arrayMainMenu);

	return $mainMenuItems;
}

function get_custom_main_menu_subs(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$menu_activator_options = get_option( 'menu_activator_option_name' ); 
	$activate_menu_0 = $menu_activator_options['activate_menu_0']; 

	$menu_en = 30;
	$menu_ar = 31;
	$menu_fr = 32;

	if($activate_menu_0 == "test"){
		$menu_en = "";
		$menu_ar = "";
		$menu_fr = "";
	}

	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items($menu_en);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items($menu_ar);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items($menu_fr);
	}


	//print_r($getMenu);

	//exit();

	$ParentArray = array();
	$count = 0;
	$submenu = false;
	$output = "";

	//print_r($getMenu);
	//exit();

	foreach ($getMenu as $menu_item) {

		//print_r($menu_item);

		$link = $menu_item->url;
		$title = $menu_item->title;

		$caret = '<i class="fa-solid fa-angle-down"></i>';


		// print_r($menu_item);
		// exit();

	
		if(!in_array("hideThis", $menu_item->classes)){

			if ($menu_item->menu_item_parent == 0) {
				$parent_id = $menu_item->ID;
				// print_r($menu_item->classes);
				if ($menu_item->classes[0] == "dd") {

					$rightClassMenu = "";
					if (array_key_exists(2, $menu_item->classes)) {
						$rightClassMenu = $menu_item->classes[2];
					}

					$output .= '<li class="nav-item dropdown ' . $rightClassMenu . '">
						<a class="nav-link dropdown-toggle " href="" data-bs-toggle="dropdown">' 
						. $menu_item->title . 
						$caret.
						'</a>';

					$output .= '<ul class="dropdown-menu">';
					$output .= get_dropdown_menus($parent_id);
					$output .= '</ul>';
					$output .= '</li>';
				} else {
					$output .= '<li class="nav-item"><a class="nav-link" href="' . $menu_item->url . '">' . $menu_item->title . '</a></li>';
				}
			}			


		}			




	}



	return $output;
}

function get_dropdown_menus($parent_id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items(30);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items(31);
	}
	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items(32);
	}
	//$menuitems = wp_get_nav_menu_items('main-menu');
	$output = '';

	$i = 0;
	$arraySubs = array();

	foreach ($getMenu as $menu_item) {


		$link = $menu_item->url;
		$title = $menu_item->title;

		if ($menu_item->menu_item_parent == $parent_id) {

			$divider = '';
			if (is_array($menu_item)){
				if (next($menu_item)) {
					$divider = '';
				}
			}

			$arraySubs[] = '<li><a class="dropdown-item" href="' . $link . '">' . $title . '</a></li>';
			$arraySubs[] = '<li><hr class="dropdown-divider" /></li>';

			//$output .= '<div class="sub-menu--item col-md-2"><img src="'.	$icons.'" class="dd-icons"> <a href="'.$link.'">'.$title.'</a></div>';
		}
	}

	$remove = array_pop($arraySubs);

	$output = implode('', $arraySubs);

	return $output;
}

function get_footer_menu_items($col){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	if($col == 1){
		$menuEn = 291;
		$menuAR = 292;
		$menuFR = 291;		
	}

	if($col == 2){
		$menuEn = 293;
		$menuAR = 294;
		$menuFR = 293;
	}

	if($col == 3){
		$menuEn = 295;
		$menuAR = 296;
		$menuFR = 295;
	}

	if($col == 4){
		$menuEn = 297;
		$menuAR = 298;
		$menuFR = 297;
	}

	if ($lang == 'en') {
		$getMenu = wp_get_nav_menu_items($menuEn);
	}
	if ($lang == 'ar') {
		$getMenu = wp_get_nav_menu_items($menuAR);
	}

	if ($lang == 'fr') {
		$getMenu = wp_get_nav_menu_items($menuFR);
	}

	$arrayFooterMenu = array();
	foreach ($getMenu as $item) {

		// $arrayFooterMenu[] = '<li><a class="footer-link fnt-20 text-uppercase" href="' . $item->url . '">' . $item->title . '</a></li>';
		$arrayFooterMenu[] = '
			<li class="nav-item '.implode(' ', $item->classes).'">
				<a class="nav-link" href="' . $item->url . '">' . $item->title . '</a>
			</li>';
		
	}

	$mainFooterItems = implode('', $arrayFooterMenu);

	return $mainFooterItems;
}

function forceTranslate($en, $ar){
	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	if ($lang == "en") {
		return $en;
	} else {
		return $ar;
	}
}

function getTranslatedLink($postId){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	if ($lang == "en") {
		$slug = "ar";
	} else {
		$slug = "en";
	}

	$result = get_the_permalink(pll_get_post($postId, $slug));

	return $result;
}

function get_pll_links($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$transPostId = pll_get_post($id, $lang);

	$permalink = get_permalink($transPostId);

	return $permalink;
}

function get_language_switcher($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$getLinkLangEN = get_permalink(pll_get_post($id,"en"));
	$getLinkLangAR = get_permalink(pll_get_post($id,"ar"));
	$getLinkLangFR = get_permalink(pll_get_post($id,"fr"));

	$arraylist = array();
	$arraylist['en'] = "#";
	$arraylist['ar'] = "#";
	$arraylist['fr'] = "#";
	if($getLinkLangEN){$arraylist['en'] = $getLinkLangEN;}
	if($getLinkLangAR){$arraylist['ar'] = $getLinkLangAR;}
	if($getLinkLangFR){$arraylist['fr'] = $getLinkLangFR;}

	return $arraylist;
}

function get_featured_image_url($post_id,$type = null){

	$custom_img = "";
	if($type != null){
		$custom_img = $type;
	}

	$post_thumbnail_id = get_post_thumbnail_id($post_id);

	//print_r( wp_get_attachment_metadata($post_thumbnail_id ));

	if ($post_thumbnail_id) {
		$image = wp_get_attachment_image_src( $post_thumbnail_id,$custom_img);
		$post_thumbnail_url = $image[0];
		return $post_thumbnail_url;
	}
	return false;
}

function customExcerpt($string,$length){
    $str_len = strlen($string ?? '');
    $string = strip_tags($string ?? '');

    if ($str_len > $length) {

        // truncate string
        $stringCut = substr($string, 0, $length-15);
        $string = $stringCut.'...';
    }
    return $string;
}

function getB2b(){
	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$parent_ids = array(115,154);
	$translated_id = array();
	foreach($parent_ids as $termId){
		$translated_id[] = pll_get_term($termId, $lang);
	}	

	$child_term_ids = array();

	$terms = get_terms(array(
	    'taxonomy'   => 'product_cat',
	    'hide_empty' => false,
	    'parent'     => 0,
	));

	foreach ($translated_id as $parent_id) {

	    $children = get_terms(array(
	        'taxonomy'   => 'product_cat',
	        'hide_empty' => false,
	        'parent'     => $parent_id,
	        'fields'     => 'ids',
	    ));

	    if (!empty($children) && !is_wp_error($children)) {
	        $child_term_ids = array_merge($child_term_ids, $children);
	    }
	}

	$child_term_ids = array_unique($child_term_ids);

	$term_ids = array_merge($translated_id, $child_term_ids);

	$postsIds = get_posts(array(
	    'post_type'      => 'products-b2b',
	    'posts_per_page' => -1,
	    'fields'         => 'ids',

	    'tax_query' => array(
	        array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $term_ids,
	            'operator' => 'IN',
	        ),
	    ),
	));

	$arrayBBids = array(149,151,153,167,169,171,165,163,161,192,194,196,198,200,202,574,577,579);

	$mergedIds = array_merge($arrayBBids, $postsIds);

	return $mergedIds;
}

function getProductCatsByParent($en, $ar, $fr){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$parentId = $en;
	if($lang == "ar"){$parentId = $ar;}
	if($lang == "fr"){$parentId = $fr;}

	$term = get_term($parentId, 'product_cat');

	$terms = get_terms([
	    'taxonomy'   => 'product_cat',   // or your taxonomy
	    'hide_empty' => false,
	    'parent'     => $parentId,
	    'meta_key'   => 'weight', // ACF field name
	    'orderby'    => 'meta_value_num',
	    'order'      => 'ASC',
	]);

	$arraylist = array();

	$arraylist['parent_id'] = $parentId;
	$arraylist['parent_name'] = $term->name;

	foreach ($terms as $rowTerm) {
		$arraylist['child'][] = array(
			"term_id" => $rowTerm->term_id,
			"term_name" => $rowTerm->name,
		);
	}

	return $arraylist;
}

function getProducts($type){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$translated_id = "";

	if($type == "b2c"){
		$termId = "103";
		$translated_id = array(pll_get_term($termId, $lang));
	}
	if($type == "b2b"){
		$termIds = array(115,154);

		$translated_id = array();
		foreach($termIds as $termId){
			$translated_id[] = pll_get_term($termId, $lang);
		}
	}

	if($type == "b2c"){
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'tax_query' => array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $translated_id,
	            'operator' => 'IN',

			),	
		);
	}

	if($type == "b2b"){
		$args = array(
			'post_type' => 'products-b2b',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'tax_query' => array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $translated_id,
	            'operator' => 'IN',

			),	
		);
	}

	$query = get_posts($args);

	$arrayList = array();

	$arrayList["count"] = count($query);

	if($type == "b2c"){
		foreach($query as $row){

			$details = wc_get_product($row->ID);
			$terms = wp_get_post_terms($row->ID, 'product_cat');
			$term_ids = wp_list_pluck($terms, 'term_id');
			$currency = get_woocommerce_currency();

			if($lang == "ar"){
				$currency = "ر.س";
			}

			$arrayList['products'][] = array(
				'id' => $row->ID,
				'title' => $row->post_title,
				'subtitle' => get_field("subtitle", $row->ID),
				'excerpt' => $row->post_excerpt,
				'content' => $row->post_content,
				'link' =>  get_permalink($row->ID),
				'image' => get_featured_image_url($row->ID),
				'categories' => implode(",",$term_ids),
				'details' => array(
					'currency' => $currency,
					'price' => $details->get_price(),
					'sale_price' =>$details->get_sale_price(),
				),

			);

		}		
	}

	if($type == "b2b"){
		foreach($query as $row){

			$terms = wp_get_post_terms($row->ID, 'product_cat');
			$term_ids = wp_list_pluck($terms, 'term_id');

			$arrayList['products'][] = array(
				'id' => $row->ID,
				'title' => $row->post_title,
				'excerpt' => $row->post_excerpt,
				'content' => $row->post_content,
				'link' =>  get_permalink($row->ID),
				'image' => get_featured_image_url($row->ID),
				'categories' => implode(",",$term_ids),
				'gallery' => get_field("gallery", $row->ID),
				'size' =>  get_field("package_size", $row->ID),
			);

		}		
	}

	//print_r($arrayList);
	return $arrayList;

}

function getProductsHome($type){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$translated_id = "";

	if($type == "b2c"){
		$termId = "103";
		$translated_id = array(pll_get_term($termId, $lang));

		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 5,
			'post_status' => 'publish',
			'tax_query' => array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $translated_id,
	            'operator' => 'IN',

			),	
		);

	}
	if($type == "b2b"){
		$termIds = array(115,154);

		$translated_id = array();
		foreach($termIds as $termId){
			$translated_id[] = pll_get_term($termId, $lang);
		}

		$args = array(
			'post_type' => 'products-b2b',
			'posts_per_page' => 5,
			'post_status' => 'publish',
			'tax_query' => array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $translated_id,
	            'operator' => 'IN',

			),	
		);
	}



	$query = get_posts($args);

	$arrayList = array();

	$arrayList["count"] = count($query);

	if($type == "b2c"){

		foreach($query as $row){

			$details = wc_get_product($row->ID);
			$terms = wp_get_post_terms($row->ID, 'product_cat');
			$term_ids = wp_list_pluck($terms, 'term_id');
			$currency = get_woocommerce_currency();

			if($lang == "ar"){
				$currency = "ر.س";
			}

			$arrayList['products'][] = array(
				'id' => $row->ID,
				'title' => $row->post_title,
				'subtitle' => get_field("subtitle", $row->ID),
				'excerpt' => $row->post_excerpt,
				'content' => $row->post_content,
				'link' =>  get_permalink($row->ID),
				'image' => get_featured_image_url($row->ID),
				'categories' => implode(",",$term_ids),
				'details' => array(
					'currency' => $currency,
					'price' => $details->get_price(),
					'sale_price' =>$details->get_sale_price(),
				),

			);

		}		
	}

	if($type == "b2b"){
		
		foreach($query as $row){

			$terms = wp_get_post_terms($row->ID, 'product_cat');
			$term_ids = wp_list_pluck($terms, 'term_id');

			$arrayList['products'][] = array(
				'id' => $row->ID,
				'title' => $row->post_title,
				'excerpt' => $row->post_excerpt,
				'content' => $row->post_content,
				'link' =>  get_permalink($row->ID),
				'image' => get_featured_image_url($row->ID),
				'categories' => implode(",",$term_ids),
				'gallery' => get_field("gallery", $row->ID),
				'size' =>  get_field("package_size", $row->ID),
			);

		}		
	}


	//print_r($arrayList);
	return $arrayList;

}

function getRelatedProducts($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$categories = wp_get_post_terms($id, 'product_cat', [
	    'fields' => 'ids'
	]);

	$args = array(
	    'post_type' => 'product',
	    'post_status' => 'publish',
	    'posts_per_page' => 5,
	    'orderby' => 'rand',
	    'tax_query' => array(
	        array(
	            'taxonomy' => 'product_cat',
	            'field'    => 'term_id',
	            'terms'    => $categories,
	        ),
	    ),
	);	

	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){
		
		$details = wc_get_product($row->ID);
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');
		$currency = get_woocommerce_currency();

			if($lang == "ar"){
				$currency = "ر.س";
			}		

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'subtitle' => get_field("subtitle", $row->ID),
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'categories' => implode(",",$term_ids),
			'details' => array(
				'currency' => $currency,
				'price' => $details->get_price(),
				'sale_price' =>$details->get_sale_price(),
			),
		);

	}

	return $arrayList;

}

function getBanner($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$type = get_field("banner_type", $id);

	switch ($type) {
	    case 'none':
	        return false;
	        break;
	    case 'image':
	        return getBannerImage($id);
	        break;
	    case 'video':
	        return getBannerVideo($id);
	        break;
	    case 'gallery':
	        return getBannerGallery($id);
	        break;
	}

}

function getBannerImage($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$arraylist = array();

	$args = array(
		'post_type' => 'page',
		'post_status' => 'publish',                    
		'posts_per_page' => 1,
		'post__in'  => array($id),  	    
	);

	$query = get_posts($args);

	$arrayList = array();

		foreach($query as $row) {
				
			$arrayList = array(
				'id' => $row->ID,
				'title' => $row->post_title,
				'content' => $row->post_content,
			);
		}



	// $output = "";

	// $imgLight = get_field("image_light", $id);
	// $imgDark = get_field("image_dark", $id);
	// $bannerCaption =  get_field("banner_caption", $id);

	// $output .= '<section id="banner" class="vh-100 bg-full bg-change" style="background-image:url(\''.$imgLight.'\')" data-bg-dark="'.$imgDark.'" data-bg-light="'.$imgLight.'" data-scroll-section>';
	// 	$output .= '<div class="container">';
	// 		$output .= '<div class="vh-100 d-flex justify-content-center align-items-center">';
	// 			$output .= '<h1 class="banner-title fnt-white fnt-25">'.$bannerCaption.'</h1>';
	// 		$output .= '</div>';
	// 	$output .= '</div>';
	// $output .= '</section>';


	return $arraylist;

}

function getBannerVideo($id){

		    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$arrayList = array(
		'id' => $id,
		'type' => get_field("banner_type", $id),
		'image' => array(
			'light' => get_field("image-light", $id),
			'dark' => get_field("image-dark", $id),
		),
		'video' => get_field('video', $id),
		'gallery' => get_field('gallery', $id),
	);	


	return false;

}

function getBannerGallery($id){

	$output = "";

	$args = array(
		'post_type' => 'page',
		'post_status' => 'publish',                    
		'posts_per_page' => 1,
		'post__in'  => array($id),  	    
	);

	$query = get_posts($args);	

	$bannerGallery =  getGallery($id);
	$bannerCaption1 =  get_field("banner_caption_line_1", $id);
	$bannerCaption2 =  get_field("banner_caption_line_2", $id);
	$bannerButtonText =  get_field("button_text", $id);
	$bannerButtonLink =  get_field("button_link", $id);

	$arrayList = array();

	foreach($query as $row) {
			
		$arrayList = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'content' => $row->post_content,
			'caption_1' =>$bannerCaption1,
			'caption_2' =>$bannerCaption2,
			'button_text' =>$bannerButtonText,
			'button_link' =>$bannerButtonLink,
			'gallery' => $bannerGallery
		);

	}	

	return $arrayList;

}

function getGallery($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$gallery =  get_field("gallery", $id);

	$arraylist = array();
    foreach ($gallery as $item){

    	//print_r($item);

        $mime_type = $item['attachment']->post_mime_type;
        $link = "";

        $media = "";

        if($mime_type == "video/mp4") {
            $media = "video";
            $link = $item['metadata']['file']['file_url'];
        }else{
            $media = "image";
            $link = $item['metadata']['full']['file_url'];
        }

        $arraylist[] = array(
        	'type' => $media,
        	'link' => $link
        );
    }

    return $arraylist;
}

function getSections($postId){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$translated_id = pll_get_post($postId, $lang);

    $args = array(
      'post_type' => 'section',
      'post_status' => 'publish',                    
      'posts_per_page' => 1,
	  'post__in'  => array($postId),      
  	);	

  	$query = get_posts($args);

  	//gallery
  	$gallery = get_field("gallery", $postId);
  	$arrayGallery = array();
  	if(!empty($gallery)){
	  	foreach($gallery as $rowGallery){
				$arrayGallery[] = $rowGallery['metadata']['full']['file_url'];
	  	}
  	}
  	
  	//accordion
  	$accordion = get_field("accordion", $postId);


  	$getAccPost = array();
  	if(!empty($accordion)){
  		$getAccPost = getAccordion($accordion);
  	}

  	//$shortCode = do_shortcode(get_field("short_code", $row->ID)); 

  	$arrayList = array();
  	foreach ($query as $row){

  		$arrayList = array(
  			"id" => $row->ID,
  			"title" => $row->post_title,
  			"content" => $row->post_content, 
  			"featImg" => get_featured_image_url($row->ID), 
  			"title_top" => get_field("title_top",$row->ID), 
  			"title_middle" => get_field("title_middle",$row->ID), 
  			"title_bottom" => get_field("title_bottom",$row->ID), 
  			"button_text" => get_field("button_text", $row->ID), 
  			"button_link" => get_field("button_link", $row->ID),
  			"background" => get_field("background", $row->ID),
  			"background_mobile" => get_field("background_mobile", $row->ID),
  			"attachment" => array(
  				"type" => get_field("attachment", $row->ID),
  				"image" => get_field("image", $row->ID),
  				"video" => get_field("video", $row->ID),
  				"gallery" => $arrayGallery,
  				"short_code" => get_field("short_code", $row->ID),
  				"accordion" => $getAccPost,
  				"editor" => clean_acf_content("editor",$row->ID),
  			)
  		);

  	}

  	return $arrayList;

}

function getOtherBlogs($id){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	if($id == 0){

		$args = array(
		    'post_type'      => 'post',
	      	'post_status' => 'publish',  	    
		    'posts_per_page' => -1,
		    'orderby' => 'date',
		    'order' => 'DESC',
		);		

	}else{

		$args = array(
		    'post_type'      => 'post',
	      	'post_status' => 'publish',  	    
		    'posts_per_page' => 3,
		    'orderby'        => 'rand',
		    'post__not_in'   => array($id),
		);

	}


	$query = get_posts($args);

	//print_r($query);

	$arraylist = array();

	foreach($query as $row){

		$arraylist[] = array(
			'id' => $row->ID,
			"title" => $row->post_title,
			"featImg" => get_featured_image_url($row->ID), 
			"link" => get_permalink($row->ID), 
		);

	}

	return $arraylist;

}

function getGlobalTestimony($type){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$args = array(
	    'post_type'  => 'testimony',
	  	'post_status' => 'publish',  	    
	    'posts_per_page' => 5,
	    'meta_query' => array(
			'key' => 'type',
            'value' => $type,
            'compare' => '='	    	
	    ),
	    'orderby' => 'date',
	    'order' => 'DESC',
	);	

	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){

		$getFeatured = get_template_directory_uri()."/img/icons/defaultavatar.png";

		if(get_featured_image_url($row->ID,"testimony-featured") != "" || !empty(get_featured_image_url($row->ID,"testimony-featured"))){
			$getFeatured = get_featured_image_url($row->ID,"testimony-featured");
		}

		$arrayList[] = array(
			'id' => $row->ID,
			"title" => $row->post_title,
			"featImg" => $getFeatured, 
			"content" => $row->post_content,
			"type" => get_field("type",$row->ID) 
		);

	}

	return $arrayList;

}

function getAds(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$args = array(
	    'post_type'  => 'ad',
	  	'post_status' => 'publish',  	    
	    'posts_per_page' => 5,
	    'orderby' => 'date',
	    'order' => 'ASC',
	);	

	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){

		$arrayList[] = array(
			'id' => $row->ID,
			"title" => $row->post_title,
			"content" => $row->post_content,
		);

	}

	return $arrayList;

}

function noreve_theme_proj_feat_setup() {
    add_theme_support( 'post-thumbnails' );
	add_image_size( 'testimony-featured', 150, 150, array( 'top', 'center' ), true);
}
add_action( 'after_setup_theme', 'noreve_theme_proj_feat_setup' );

function countBirdDots($array){

	$countarray = count($array);

	$output = "";

	for($i = 0; $i < $countarray; $i++){

		$active = "";
		if($i == 0){
			$active = "active";
		}

		$output .= '<a class="btn-dot '.$active.'" data-slide="'.$i.'"></a>';

	}

	return $output;
}

function remove_admin_bar() {
  show_admin_bar(false);
}
add_action('after_setup_theme', 'remove_admin_bar');

/* woocommerce */
add_filter( 'woocommerce_breadcrumb_defaults', 'custom_woocommerce_breadcrumb_delimiter' );

function custom_woocommerce_breadcrumb_delimiter( $defaults ) {
    $defaults['delimiter'] = ' > '; // change to what you want
    return $defaults;
}

function remove_extra_br($content) {
    $content = preg_replace('/(<br\s*\/?>\s*)+/', '<br>', $content);
    return $content;
}
add_filter('the_content', 'remove_extra_br');

function clean_empty_paragraphs($content){
    $content = preg_replace('/<p>(?:\s|&nbsp;)*<\/p>/i', '', $content);
    return $content;
}
add_filter('the_content', 'clean_empty_paragraphs', 20);


add_filter( 'woocommerce_checkout_fields', function( $fields ) {

    foreach ( $fields['billing'] as $key => $field ) {
        
        $fields['billing'][$key]['class'][] = 'fnt-15'; // add your class
        $fields['billing'][$key]['input_class'][] = 'form-control gernatic-form'; // input field classes
  

    }

    return $fields;
});


add_filter( 'woocommerce_form_field_args', 'remove_country_select2', 10, 3 );

function remove_country_select2( $args, $key, $value ) {

    if ( $key === 'billing_country' || $key === 'shipping_country' ) {
        $args['input_class'][] = 'no-select2';
    }

    return $args;
}

add_action( 'wp_footer', function () {
    ?>
    <script>
    jQuery(function($){

        $('select.no-select2').each(function(){
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).selectWoo('destroy');
            }
        });

    });
    </script>
    <?php
});



function custom_sar_currency_symbol( $currency_symbol, $currency ) {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

    if ( 'SAR' === $currency ) {
    	if($lang == "en"){
    		$currency_symbol = 'SAR';
    	}
        
    }
    return $currency_symbol;
}
add_filter( 'woocommerce_currency_symbol', 'custom_sar_currency_symbol', 10, 2 );


add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        wp_dequeue_script('selectWoo');
        wp_dequeue_script('select2');
        wp_dequeue_style('select2');
    }
}, 100);

/*ajax*/
function product_filter() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$categories = json_decode($_POST['categories'], true);

	$output = "";

	if(!empty($categories)){

		$args = array(
		    'post_type' => 'product',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		    'tax_query' => array(
		        array(
		            'taxonomy' => 'product_cat',
		            'field'    => 'term_id',
		            'terms'    => $categories,
		        ),
		    ),
		);		

	}else{

		$args = array(
		    'post_type' => 'product',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		);		

	}


	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){
		
		$details = wc_get_product($row->ID);
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');
		$currency = get_woocommerce_currency();

			if($lang == "ar"){
				$currency = "ر.س";
			}

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'subtitle' => get_field("subtitle", $row->ID),
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'categories' => implode(",",$term_ids),
			'details' => array(
				'currency' => $currency,
				'price' => $details->get_price(),
				'sale_price' =>$details->get_sale_price(),
			),
		);

	}

	$outputHtml = array();

	foreach($arrayList as $outputRow){ 

		$outputPrice = '';

		if(empty($outputRow['details']['sale_price']) || $outputRow['details']['sale_price'] == ""){

			$outputPrice = '
              <p class="product-price">
              	  '.$outputRow['details']['price'].'
                  '.$outputRow['details']['currency'].'
                  
               </p>
			';

		}else{

			$outputPrice = '

              <p class="product-price">
              	'.$outputRow['details']['sale_price'].'
                  '.$outputRow['details']['currency'].'
                  

                  <font class="product-price-sale">
                  	'.$outputRow['details']['price'].'
                     '.$outputRow['details']['currency'].'
                     
                  </font>

               </p>
			';			

		}


	 $outputHtml[] = '


	    <div class="col-6 col-md-4 item" data-catids="'.$outputRow['categories'].'">

	          <div class="card h-100 w-100 product-box">

	             <a href="'.$outputRow['link'].'" class="h-100 posRel">

	                <div class="product-rating product-padding">

	                   <div class="row align-items-center">

	                     <div class="col-6">
	                        <p class="number-rating d-flex justify-content-start fnt-bold fnt-20 remMar">
	                           <font class="fa-mar-10">4.4</font> 
	                           <img src="'.get_template_directory_uri().'/img/icons/star.svg" class="" alt="icons">
	                        </p>
	                     </div>

	                     <div class="col-6">
	                        <p class="whislist-rating d-flex justify-content-end remMar">
	                           <img src="'.get_template_directory_uri().'/img/icons/love.svg" class="fa-mar-5" alt="icons">
	                        </p>
	                     </div>
	                     
	                   </div> 

	                </div>

	                <div class="spacer-20"></div>

	                <div class="product-img product-padding">

	                  <div class="row">
	                     
	                     <div class="col-md-12">
	                        <center>
	                           <img src="'.$outputRow['image'].'" class="img-fluid" alt="product">                      
	                        </center>
	                     </div>
	                    
	                  </div>

	                </div>

	                <div class="spacer-20"></div>    

	                  <div class="product-details">

	                     <div class="product-padding">

	                        <h4 class="product-title">'.$outputRow['title'].'</h4>
	                        <div class="spacer-20"></div>
	                        
	                        <div class="row align-items-end">

	                           <div class="col-12">

	                           	'.$outputPrice.'
	                           	
	                           </div>

	                        </div>
	                        
	                     </div>
	                     
	                  </div>

	             </a>

	          </div>

	    </div>


	 ';

	}


	//print_r($outputHtml);

	
	echo implode("", $outputHtml);

	
    wp_die(); 

}
add_action('wp_ajax_product_filter', 'product_filter');
add_action('wp_ajax_nopriv_product_filter', 'product_filter');

function product_filter_home() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$categories = json_decode($_POST['categories'], true);

	$output = "";

	if(!empty($categories)){

		$args = array(
		    'post_type' => 'product',
		    'post_status' => 'publish',
		    'posts_per_page' => 5,
		    'orderby' => 'title',
		    'order' => 'ASC',
		    'tax_query' => array(
		        array(
		            'taxonomy' => 'product_cat',
		            'field'    => 'term_id',
		            'terms'    => $categories,
		        ),
		    ),
		);	

	}else{

		$args = array(
		    'post_type' => 'product',
		    'post_status' => 'publish',
		    'posts_per_page' => 5,
		    'orderby' => 'title',
		    'order' => 'ASC',
		);		

	}


	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){
		
		$details = wc_get_product($row->ID);
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');
		$currency = get_woocommerce_currency();

			if($lang == "ar"){
				$currency = "ر.س";
			}

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'subtitle' => get_field("subtitle", $row->ID),
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'categories' => implode(",",$term_ids),
			'details' => array(
				'currency' => $currency,
				'price' => $details->get_price(),
				'sale_price' =>$details->get_sale_price(),
			),
		);

	}

	$outputHtml = array();

	foreach($arrayList as $outputRow){ 

		$outputPrice = '';

		if(empty($outputRow['details']['sale_price']) || $outputRow['details']['sale_price'] == ""){

			$outputPrice = '
              <p class="product-price">
              	'.$outputRow['details']['price'].'
                  '.$outputRow['details']['currency'].'
                  
               </p>
			';

		}else{

			$outputPrice = '

              <p class="product-price">
              	'.$outputRow['details']['sale_price'].'
                  '.$outputRow['details']['currency'].'
                  

                  <font class="product-price-sale">
                  	'.$outputRow['details']['price'].'
                     '.$outputRow['details']['currency'].'
                     
                  </font>

               </p>
			';			

		}


	 $outputHtml[] = '


        <div class="item" data-catid="'.$outputRow['categories'].'">

           <div class="product-box">

              <a href="'.$outputRow['link'].'">
                 <div class="product-rating product-padding">
                    <div class="row align-items-center">

                       <div class="col-6">
                          <p class="number-rating d-flex justify-content-start fnt-bold fnt-20 remMar">
                             <font class="fa-mar-10">4.4</font> 
                             <img src="'.get_template_directory_uri().'/img/icons/star.svg" class="" alt="icons">
                          </p>
                       </div>

                       <div class="col-6">
                          <p class="whislist-rating d-flex justify-content-end remMar">
                             <img src="'.get_template_directory_uri().'/img/icons/love.svg" class="fa-mar-5" alt="icons">
                          </p>
                       </div>
                       
                    </div>                              
                 </div>

                 <div class="spacer-20"></div>

                 <div class="product-img product-padding">
                    <div class="row">
                       <!--<div class="col-md-3"></div>-->
                       <div class="col-md-12">
                          <center>
                              <img src="'.$outputRow['image'].'" class="img-fluid" alt="product">                       
                          </center>
                       </div>
                        <!--<div class="col-md-3"></div>-->
                    </div>
                 </div>

                 <div class="spacer-20"></div>

	                  <div class="product-details">

	                 <div class="product-padding">

	                    <h4 class="product-title">'.$outputRow['title'].'</h4>
	                    <div class="spacer-20"></div>
	                    
	                    <div class="row align-items-end">

	                       <div class="col-12">

	                       	'.$outputPrice.'
	                       	
	                       </div>

	                    </div>
	                    
	                 </div>
	                 
	              </div>
              </a>

           </div>

        </div>


	 ';

	}


	//print_r($outputHtml);

	
	echo implode("", $outputHtml);

	
    wp_die(); 

}
add_action('wp_ajax_product_filter_home', 'product_filter_home');
add_action('wp_ajax_nopriv_product_filter_home', 'product_filter_home');

function product_filter_b2b() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$categories = json_decode($_POST['categories'], true);

	$output = "";

	if(!empty($categories)){

		$args = array(
		    'post_type' => 'products-b2b',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		    'tax_query' => array(
		        array(
		            'taxonomy' => 'product_cat',
		            'field'    => 'term_id',
		            'terms'    => $categories,
		        ),
		    ),
		);		

	}else{

		$args = array(
		    'post_type' => 'products-b2b',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		);		

	}


	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){
		
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'gallery' => get_field("gallery", $row->ID),
			'categories' => $term_ids,
			'size' =>  get_field("package_size", $row->ID),
		);

	}

	$outputHtml = array();

	foreach($arrayList as $outputRow){ 


	 $outputHtml[] = '

        <div class="col-md-4">

          <div class="item" data-catid="['.$outputRow['categories'].']">

             <div class="product-box">

                <a href="'.$outputRow['link'].'">
                   <div class="product-rating product-padding">
                      <div class="row align-items-center">

                         <div class="col-6">
                            <p class="number-rating d-flex justify-content-start fnt-bold fnt-20 remMar">
                               <font class="fa-mar-10">4.4</font> 
                               <img src="'.get_template_directory_uri().'/img/icons/star.svg" class="" alt="icons">
                            </p>
                         </div>

                         <div class="col-6">
                            <p class="whislist-rating d-flex justify-content-end remMar">
                               <img src="'.get_template_directory_uri().'/img/icons/love.svg" class="fa-mar-5" alt="icons">
                            </p>
                         </div>
                         
                      </div>                              
                   </div>

                   <div class="spacer-20"></div>

                   <div class="product-img product-padding">
                      <div class="row">
                         <div class="col-md-3"></div>
                         <div class="col-md-6">
                            <center>
                               <img src="'.$outputRow['image'].'" class="img-fluid" alt="product">                      
                            </center>
                         </div>
                         <div class="col-md-3"></div>
                      </div>
                   </div>

                   <div class="spacer-20"></div>

                   <div class="product-details">

                      <div class="product-padding">

                         <h4 class="product-title">'.$outputRow['title'].'</h4>
                         
                         <div class="row align-items-start">

                            <div class="col-6">
                               <!-- <h4 class="product-title">'.$outputRow['title'].'</h4> -->
                            </div>

                            <div class="col-6 text-end">
                               <!-- <p class="product-weigth">'.$outputRow['size'].'</p> -->
                            </div>

                         </div>

                      </div>
                      
                   </div>
                </a>

     
                 <a href="#" 
                    class="addToQuote" 
                    data-product-id="'.$outputRow['id'].'"
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    data-bs-title="'.pll__('Added to Quotation').'"
                 >

                 	'.pll__('Add To Quote').'

                 </a>


             </div>

          </div> 

          <div class="spacer-20"></div> 

        </div>

	 ';

	}


	//print_r($outputHtml);

	
	echo implode("", $outputHtml);

	
    wp_die(); 

}
add_action('wp_ajax_product_filter_b2b', 'product_filter_b2b');
add_action('wp_ajax_nopriv_product_filter_b2b', 'product_filter_b2b');

function product_filter_home_b2b() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$categories = json_decode($_POST['categories'], true);

	$output = "";

	if(!empty($categories)){

		$args = array(
		    'post_type' => 'products-b2b',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		    'tax_query' => array(
		        array(
		            'taxonomy' => 'product_cat',
		            'field'    => 'term_id',
		            'terms'    => $categories,
		        ),
		    ),
		);		

	}else{

		$args = array(
		    'post_type' => 'products-b2b',
		    'post_status' => 'publish',
		    'posts_per_page' => -1,
		    'orderby' => 'title',
		    'order' => 'ASC',
		);		

	}


	$query = get_posts($args);

	$arrayList = array();

	foreach($query as $row){
		
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'gallery' => get_field("gallery", $row->ID),
			'categories' => $term_ids,
			'size' =>  get_field("package_size", $row->ID),
		);

	}

	$outputHtml = array();

	foreach($arrayList as $outputRow){ 


	 $outputHtml[] = '

        <div class="item" data-catid="['.$outputRow['categories'].']">

             <div class="product-box">

                <a href="'.$outputRow['link'].'">
                   <div class="product-rating product-padding">
                      <div class="row align-items-center">

                         <div class="col-6">
                            <p class="number-rating d-flex justify-content-start fnt-bold fnt-20 remMar">
                               <font class="fa-mar-10">4.4</font> 
                               <img src="'.get_template_directory_uri().'/img/icons/star.svg" class="" alt="icons">
                            </p>
                         </div>

                         <div class="col-6">
                            <p class="whislist-rating d-flex justify-content-end remMar">
                               <img src="'.get_template_directory_uri().'/img/icons/love.svg" class="fa-mar-5" alt="icons">
                            </p>
                         </div>
                         
                      </div>                              
                   </div>

                   <div class="spacer-20"></div>

                   <div class="product-img product-padding">
                      <div class="row">
                         <div class="col-md-3"></div>
                         <div class="col-md-6">
                            <center>
                               <img src="'.$outputRow['image'].'" class="img-fluid" alt="product">                      
                            </center>
                         </div>
                         <div class="col-md-3"></div>
                      </div>
                   </div>

                   <div class="spacer-20"></div>

                   <div class="product-details">

                      <div class="product-padding">

                         <h4 class="product-title">'.$outputRow['title'].'</h4>
                         
                         <div class="row align-items-start">

                            <div class="col-6">
                               <!-- <h4 class="product-title">'.$outputRow['title'].'</h4> -->
                            </div>

                            <div class="col-6 text-end">
                               <!-- <p class="product-weigth">'.$outputRow['size'].'</p> -->
                            </div>

                         </div>

                      </div>
                      
                   </div>
                </a>

                 <a href="#" 
                    class="addToQuote" 
                    data-product-id="'.$outputRow['id'].'"
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    data-bs-title="'.pll__('Added to Quotation').'"                                   
                    >

                    '.pll__('Add To Quote').'
                 </a>

             </div>

        </div>


	 ';

	}


	//print_r($outputHtml);

	
	echo implode("", $outputHtml);

	
    wp_die(); 

}
add_action('wp_ajax_product_filter_home_b2b', 'product_filter_home_b2b');
add_action('wp_ajax_nopriv_product_filter_home_b2b', 'product_filter_home_b2b');

function product_addto_quote() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$product_id = intval($_POST['id']);
	//$product_id = pll_get_post($postId, $lang);

	$success = 0;
	$message = "";

	if(!session_id()){ 
        session_start();
	}

	if(!isset($_SESSION['product_ids'])){ $_SESSION['product_ids'] = array(); }

	if(isset($_SESSION['product_ids']) && in_array($product_id, $_SESSION['product_ids'])){

		$success = 0;
		$message = pll__('Already added to the quotation');

	}else{

		$_SESSION['product_ids'][] = $product_id;
		$success = 1;
		$message = pll__('Added to Quotation');
	}

	//print_r($_SESSION['product_ids']);

    wp_send_json([
        'success' => $success,
        'message' => $message,
        'session' => $_SESSION['product_ids'],
    ]);

    // print $success;
    

    wp_die(); 

}
add_action('wp_ajax_product_addto_quote', 'product_addto_quote');
add_action('wp_ajax_nopriv_product_addto_quote', 'product_addto_quote');

function product_delfrom_quote() {

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$product_id = intval($_POST['id']);
	//$product_id = pll_get_post($postId, $lang);

	$success = 0;
	$message = "";

	if(isset($_SESSION['product_ids']) && in_array($product_id, $_SESSION['product_ids'])){

		$success = 1;
		foreach($_SESSION['product_ids'] as $key => $rowId){
			if($rowId == $product_id){
				unset($_SESSION['product_ids'][$key]);
			}
		}
		

	}else{

		$success = 0;
	}

	//print_r($_SESSION['product_ids']);

    wp_send_json([
        'success' => $success,
        'session' => $_SESSION['product_ids'],
    ]);

    // print $success;
    

    wp_die(); 

}
add_action('wp_ajax_product_delfrom_quote', 'product_delfrom_quote');
add_action('wp_ajax_nopriv_product_delfrom_quote', 'product_delfrom_quote');


function getCategoriesById($id){

	$categories = get_the_terms($id, 'product_cat');

	$arrayList = array();
	foreach($categories as $rowCat){

		$arrayList[] = array(
			"id" => $rowCat->term_id,
			"name" => $rowCat->name
		);
	}

	return $arrayList;

}

function getOrderItems($order){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$arrayList = array();
	$product_ids = array();
	$product_qty = array();

	foreach ($order as $item){ 
		$product_ids[] = $item->get_product_id();
		$product_qty[] = $item->get_quantity();
	}

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'post__in' => $product_ids,	
	);

	$query = get_posts($args);	

	foreach ($query as $row){

		$details = wc_get_product($row->ID);
		$terms = wp_get_post_terms($row->ID, 'product_cat');
		$term_ids = wp_list_pluck($terms, 'term_id');
		$currency = get_woocommerce_currency();	

			if($lang == "ar"){
				$currency = "ر.س";
			}	

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'subtitle' => get_field("subtitle", $row->ID),
			'excerpt' => customExcerpt($row->post_excerpt, 100),
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'categories' => implode(",",$term_ids),
			'details' => array(
				'currency' => $currency,
				'price' => $details->get_price(),
				'sale_price' =>$details->get_sale_price(),
			),

		);


	}
	//print_r($product_ids);
	return $arrayList;

}

function getQuotes($arrayQuot){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}
	$translated_prod = array();
	foreach($arrayQuot as $quotItem){
		$translated_prod[] = pll_get_post($quotItem, $lang);
	}	

	$args = array(
	    'post_type' => 'products-b2b',
	    'posts_per_page' => -1,		
		"post__in" => $translated_prod,
	);

	$query = get_posts($args);

	//print_r($query);

	$arrayList = array(); 

	foreach($query as $row){

		$arrayList[] = array(
			'id' => $row->ID,
			'title' => $row->post_title,
			'excerpt' => $row->post_excerpt,
			'content' => $row->post_content,
			'link' =>  get_permalink($row->ID),
			'image' => get_featured_image_url($row->ID),
			'gallery' => get_field("gallery", $row->ID),
			'size' =>  get_field("package_size", $row->ID),
		);

	}

	//print_r($arrayList);
	return $arrayList;

}

/*shortcodes*/
function get_b2b_items() {
    return "Hello, this is my shortcode output!";
}
add_shortcode('b2b_items', 'get_b2b_items');

function get_templateDir(){
    return get_template_directory_uri();
}
add_shortcode('templateDir', 'get_templateDir');

add_filter( 'woocommerce_account_menu_items', function( $items ) {

    return array(
        'dashboard'       => __( 'Dashboard', 'woocommerce' ),
        'edit-account'    => __( 'Profile', 'woocommerce' ),        
        'orders'          => __( 'Orders', 'woocommerce' ),
        'wishlist'        => __( 'Wishlist', 'woocommerce' ), 
        'returns'        => __( 'Returns', 'woocommerce' ), 
        'coupons'       => __( 'My Coupons', 'woocommerce' ),
        'edit-address'    => __( 'Addresses', 'woocommerce' ),        
        // 'downloads'       => __( 'Downloads', 'woocommerce' ),
        'payment-methods' => __( 'Payment methods', 'woocommerce' ),
        'reviews' => __( 'Reviews', 'woocommerce' ),
        'notifications' => __( 'Notifications', 'woocommerce' ),
        'customer-logout' => __( 'Logout', 'woocommerce' ),

        
    );

});


/* text editor filter */
remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');
remove_filter('acf_the_content', 'wpautop');
add_filter('wpcf7_autop_or_not', '__return_false');
add_filter('wpcf7_form_elements', function($content) {
    $content = preg_replace('/<span class="wpcf7-form-control-wrap"[^>]*>/', '', $content);
    $content = str_replace('</span>', '', $content);
    return $content;
});

function clean_acf_content($field_name,$postId) {
    $content = get_field($field_name,$postId);

    if (!$content) return '';

    // Remove empty <p>
    $content = preg_replace('/<p>\s*<\/p>/', '', $content);

    // Remove <br> tags
    $content = preg_replace('/<br\s*\/?>/', '', $content);

    return $content;
}

function wc_login_only_shortcode() {
    // if (!is_user_logged_in()) {
    //     return '<p>You are already logged in.</p>';
    // }

    ob_start();
    wc_get_template('myaccount/form-login.php');
    return ob_get_clean();
}
add_shortcode('wc_login_only', 'wc_login_only_shortcode');

function woocommerce_register_only() {
    // if (is_user_logged_in()) return '<p>You are already registered.</p>';

    ob_start();
    do_action('woocommerce_before_customer_login_form');

    wc_get_template('myaccount/form-login.php', array('form' => 'register'));

    return ob_get_clean();
}
add_shortcode('wc_register_only', 'woocommerce_register_only');

add_action('wp_logout', function () {
    wp_redirect(home_url());
    exit;
});

add_action('init', function () {
    add_post_type_support('product', 'revisions');
});

add_filter('searchwp_live_search_results_button_text',function(){

	    $lang = "en";
	if (function_exists('pll_current_language')){
	    $lang = pll_current_language();
	}

	$searchText = "Search";

	if($lang == "ar"){
		$searchText = "يبحث";
	}

	if($lang == "fr"){
		$searchText = "Recherche";
	}	

    return $searchText;
});


add_action('init', function () {
    if (!session_id()) {

        // 30 days
        $lifetime = 60 * 60 * 24 * 30;

        session_set_cookie_params($lifetime);
        ini_set('session.gc_maxlifetime', $lifetime);

        session_start();
    }
}, 1);

//add_filter('wpcf7_form_elements', 'do_shortcode');


/* text translation */
add_action('init', function() {

	/*-- print this in the template <?php pll_e('Copyright'); ?> --*/

	/*-- register strings here --*/

	// pll_register_string('City', 'Riyadh', 'Above Limits');
	// pll_register_string('City', 'Jeddah', 'Above Limits');

	pll_register_string('Coming Soon', 'Coming Soon - Title', 'Noreve');
	pll_register_string('Coming Soon', 'Coming Soon - Subtitle', 'Noreve');
	pll_register_string('Coming Soon', 'Coming Soon - Button', 'Noreve');


	pll_register_string('Footer', 'About Noreve', 'Noreve');
	pll_register_string('Footer', 'Shop', 'Noreve');
	pll_register_string('Footer', 'Support and Help', 'Noreve');
	pll_register_string('Footer', 'Consultations', 'Noreve');
	pll_register_string('Footer', 'Copyright', 'Noreve');

	pll_register_string('Others', 'Other Articles', 'Noreve');
	pll_register_string('Others', 'Other Orders', 'Noreve');
	pll_register_string('Others', 'Other Orders Subtitle', 'Noreve');

	pll_register_string('Login', 'Login', 'Noreve');
	pll_register_string('Login', 'Register', 'Noreve');
	pll_register_string('Login', 'Join', 'Noreve');
	pll_register_string('Login', 'Sign up', 'Noreve');

	pll_register_string('My Account', 'Order Number', 'Noreve');
	pll_register_string('My Account', 'Order Date', 'Noreve');
	
	pll_register_string('B2B', 'Add To Quote', 'Noreve');
	pll_register_string('B2B', 'Added to Quotation', 'Noreve');
	pll_register_string('B2B', 'Already added to the quotation', 'Noreve');
	pll_register_string('B2B', 'Fillout', 'Noreve');

	pll_register_string('Quotation', 'Required Products', 'Noreve');
	pll_register_string('Quotation', 'Product Name', 'Noreve');
	pll_register_string('Quotation', 'Package Size', 'Noreve');
	pll_register_string('Quotation', 'Product Number', 'Noreve');
	pll_register_string('Quotation', 'Quantity', 'Noreve');
	pll_register_string('Quotation', 'Contact Information', 'Noreve');
	pll_register_string('Quotation', 'Additional Notes', 'Noreve');

	pll_register_string('Global', 'All', 'Noreve');
	pll_register_string('Global', 'Filter', 'Noreve');
	pll_register_string('Global', 'Products', 'Noreve');
	pll_register_string('Global', 'All Products', 'Noreve');

	pll_register_string('Contact', 'Via E-mail', 'Noreve');
	pll_register_string('Contact', 'Via Whatsapp', 'Noreve');
	pll_register_string('Contact', 'Address', 'Noreve');
	pll_register_string('Contact', 'Address Text', 'Noreve');

	pll_register_string('Buttons', 'Discover Products', 'Noreve');
	
	pll_register_string('Products', 'Add to Wishlist', 'Noreve');
	pll_register_string('Products', 'Product Reviews', 'Noreve');
	pll_register_string('Products', 'Suggested Products', 'Noreve');
	pll_register_string('Products', 'Vat', 'Noreve');	

	pll_register_string('Navigation', 'Free', 'Noreve');

	pll_register_string('WooCommerce', 'Billing Details', 'Noreve');
	pll_register_string('WooCommerce', 'Order Summary', 'Noreve');
	pll_register_string('WooCommerce', 'Payment Options', 'Noreve');

	

});
