<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Agosto_13
 */

	$lang = pll_current_language();

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="<?php print get_template_directory_uri(); ?>/img/faviconshttps://gmpg.org/xfn/11">

	<link rel="apple-touch-icon" sizes="57x57" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php print get_template_directory_uri(); ?>/img/favicons/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="<?php print get_template_directory_uri(); ?>/img/favicons/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php print get_template_directory_uri(); ?>/img/favicons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="<?php print get_template_directory_uri(); ?>/img/favicons/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php print get_template_directory_uri(); ?>/img/favicons/favicon-16x16.png">
	<link rel="manifest" href="<?php print get_template_directory_uri(); ?>/img/favicons/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="<?php print get_template_directory_uri(); ?>/img/favicons/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">

	<?php wp_head(); ?>
	
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	
	<?php include_once("navigation.php"); ?>

	<div data-scroll-container>



		

