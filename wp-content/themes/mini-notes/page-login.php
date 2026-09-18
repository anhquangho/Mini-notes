<?php get_header(); ?>
<div class="login-wrap">
<?php if (mini_notes_core_required()) : ?>
    <section class="login-card">
        <span class="eyebrow">WELCOME BACK</span><h1>Your thoughts,<br>right where you left them.</h1>
        <p class="muted">Log in to your personal workspace.</p>
        <?php if (mn_input($_GET, 'login') === 'failed') : ?><p class="notice error" role="alert">Could not log in. Check your username and password.</p><?php endif; ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="mn_login">
            <?php wp_nonce_field('mn_login', 'mn_nonce'); ?>
            <label for="username">Username or email</label><input id="username" name="username" autocomplete="username" required autofocus>
            <label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required>
            <label class="check-label"><input type="checkbox" name="remember" value="1"> Keep me logged in</label>
            <button class="button full-width" type="submit">Log in <?php mini_notes_icon('arrow'); ?></button>
        </form>
        <p class="login-foot">Your notes are visible only to your account.</p>
    </section>
<?php endif; ?>
</div>
<?php get_footer(); ?>
