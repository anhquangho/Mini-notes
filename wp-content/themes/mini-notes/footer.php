</main>
<footer class="site-footer">
<span>Small notes. Clearer days.</span>
<form class="footer-signup" method="post">
<label for="mn-signup-email">Email</label>
<input id="mn-signup-email" name="mn_signup_email" type="email" autocomplete="section-newsletter email" placeholder="you@example.com" required>
<button type="submit">Subscribe</button>
<p class="footer-signup-status" role="status"><?php echo (isset($_POST['mn_signup_email']) ? 'Turn on JavaScript to join the list.' : ''); ?></p>
</form>
<span>WP Mini Notes</span>
</footer>
</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
