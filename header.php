<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package My Transit Lines
 */
 
/* redirect all possible domains to the site url (see functions.php) */
mtl_main_domain_redirect();

// redirect to start page if current proposal not within selected categories
if(is_single() && get_post_type()=='mtlproposal') {
	$category = get_the_category($post->ID);
	$catid = $category[0]->cat_ID;
	$mtl_options = get_option('mtl-option-name');
	if(!current_user_can('administrator') && in_array($mtl_options['mtl-cat-use'.$catid], ['no','only-in-search'])) header('Location: '.get_bloginfo('url').'');
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<?php
// including HTML5 enabling script for IE versions < 9 ?>
<!--[if lt IE 9]>
<script src="<?php echo get_bloginfo('template_url'); ?>/js/html5shiv.min.js"></script>
<![endif]-->
<?php
// use our logo file as Open Graph image, e.g. for Facebook ?>
<meta property="og:image" content="<?php echo get_bloginfo('stylesheet_directory').'/images/logo.png'; ?>" />
<?php
// include the favicon ?>
<link rel="shortcut icon" href="<?php echo get_bloginfo('stylesheet_directory').'/images/favicon.ico'; ?>" />
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php wp_title( '|', true, 'right' ); ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">

	<header id="masthead" class="site-header" role="banner">
		<nav id="top-navigation" class="top-navigation<?php if (!is_user_logged_in()) echo ' not-logged-in'; ?>" role="navigation">
			<h1 class="menu-toggle"><?php _e( 'Topmenu', 'my-transit-lines' ); ?></h1>
			<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'my-transit-lines' ); ?></a>
			<?php if ( ! dynamic_sidebar( 'sidebar-2' ) ) : ?><?php endif; ?>
			<?php wp_nav_menu( array( 'theme_location' => 'secondary' ) ); ?>
		</nav><!-- #site-navigation -->
		<div class="site-branding">
			<?php $mtl_options3 = get_option('mtl-option-name3');
			if($mtl_options3['mtl-main-logo']) { ?><span class="header-logo"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="<?php echo $mtl_options3['mtl-main-logo']; ?>" alt="<?php _e('site logo', 'my-transit-lines') ?>" /></a></span><?php } ?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<h2 class="site-description"><span><?php bloginfo( 'description' ); ?></span></h2>
		</div>
		<?php
		// hide the menu if custom meta 'hidemenu' is set to true
		$hidemenu = false;
		while ( have_posts() ) { the_post(); $hidemenu = get_post_meta($post->ID,'hidemenu',true); } ?>
		<?php if(!$hidemenu) { ?><nav id="site-navigation" class="main-navigation" role="navigation">
			<h1 class="menu-toggle"><?php _e( 'Menu', 'my-transit-lines' ); ?></h1>
			<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'my-transit-lines' ); ?></a>

			<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
		</nav><!-- #site-navigation --><?php } ?>
	</header><!-- #masthead -->

	<div id="content" class="site-content">

