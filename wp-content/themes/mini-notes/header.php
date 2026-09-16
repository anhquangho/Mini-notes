<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark">m<span>n</span></span><span>mini notes<span class="brand-sub">A little space for your thoughts</span></span></a>
    <p class="nav-label">WORKSPACE</p>
    <nav aria-label="Main navigation">
        <a class="nav-item <?php echo is_front_page() ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"><?php mini_notes_icon('home'); ?> Home</a>
        <a class="nav-item <?php echo is_page('dashboard') ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><?php mini_notes_icon('note'); ?> My notes</a>
        <a class="nav-item" href="<?php echo esc_url(home_url('/dashboard/#note-search')); ?>"><?php mini_notes_icon('search'); ?> Search</a>
    </nav>
    <a class="new-note-link" href="<?php echo esc_url(add_query_arg('new', '1', home_url('/dashboard/'))); ?>"><?php mini_notes_icon('plus'); ?> New note</a>
    <div class="sidebar-tip"><span class="tip-dot"></span><strong>Room to think.</strong><p>Capture an idea.<br>Make a little progress.<br>Come back whenever.</p></div>
    <div class="sidebar-bottom">
        <a class="about-link" href="<?php echo esc_url(home_url('/about/')); ?>">About this workspace ↗</a>
        <?php if (is_user_logged_in()) : $current = wp_get_current_user(); ?>
            <div class="user-row"><span class="avatar"><?php echo esc_html(mb_strtoupper(mb_substr($current->display_name, 0, 1))); ?></span><div><strong><?php echo esc_html($current->display_name); ?></strong><small>Personal workspace</small></div></div>
            <div class="account-links"><?php if (current_user_can('manage_options')) : ?><a href="<?php echo esc_url(admin_url()); ?>">WordPress Admin</a><?php endif; ?><a href="<?php echo esc_url(wp_logout_url(home_url('/login/'))); ?>">Log out</a></div>
        <?php else : ?>
            <a class="nav-item" href="<?php echo esc_url(home_url('/login/')); ?>">Log in <?php mini_notes_icon('arrow'); ?></a>
        <?php endif; ?>
    </div>
</aside>
<div class="main-shell">
<header class="topbar">
    <button class="menu-toggle" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">☰</button>
    <div class="breadcrumb">Workspace <span>/</span> <strong><?php echo is_front_page() ? 'Home' : esc_html(wp_strip_all_tags(get_the_title())); ?></strong></div>
    <span class="private-label"><span></span> Personal & private</span>
</header>
<main id="main-content">
