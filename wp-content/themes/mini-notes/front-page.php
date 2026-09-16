<?php get_header(); ?>
<div class="page-wrap home-wrap">
    <div class="eyebrow">YOUR PERSONAL WORKSPACE</div>
    <section class="home-hero">
        <div><h1>A place for your<br>next <em>little idea.</em></h1><p>Collect your thoughts, keep a plan, or start something new.<br>Your notes, with a little more breathing room.</p><a class="button" href="<?php echo esc_url(home_url('/dashboard/')); ?>">Open my notes <?php mini_notes_icon('arrow'); ?></a></div>
        <div class="paper-stack" aria-hidden="true"><div class="paper back"></div><div class="paper front"><span class="paper-tag">A FRESH START</span><h2>Good things<br>start small.</h2><div class="paper-rule"></div><p>One thought at a time.</p><span class="paper-doodle">✳</span></div></div>
    </section>
    <div class="section-heading"><h2>Make room for what matters</h2><span>01 — 03</span></div>
    <div class="feature-grid">
        <a class="feature-card" href="<?php echo esc_url(add_query_arg('new', '1', home_url('/dashboard/'))); ?>"><span class="feature-icon mint"><?php mini_notes_icon('plus'); ?></span><h3>Capture a thought</h3><p>A quick idea, a meeting takeaway, a reminder for later.</p><span class="card-link">Write a note ↗</span></a>
        <a class="feature-card" href="<?php echo esc_url(home_url('/dashboard/')); ?>"><span class="feature-icon peach"><?php mini_notes_icon('note'); ?></span><h3>Find your focus</h3><p>Give a note a priority, a status and a place to belong.</p><span class="card-link">See my notes ↗</span></a>
        <a class="feature-card" href="<?php echo esc_url(home_url('/dashboard/#note-search')); ?>"><span class="feature-icon lavender"><?php mini_notes_icon('search'); ?></span><h3>Pick up a thread</h3><p>Find a thought by its title or a word you remember.</p><span class="card-link">Search notes ↗</span></a>
    </div>
    <div class="section-heading journal-heading"><h2>From the notebook</h2><a href="<?php echo esc_url(home_url('/about/')); ?>">About Mini Notes ↗</a></div>
    <div class="journal-grid">
    <?php
    // STEP 5: a separate query does not replace the main page query.
    $journal = new WP_Query(['post_type' => 'post', 'posts_per_page' => 5, 'post_status' => 'publish']);
    if ($journal->have_posts()) :
        while ($journal->have_posts()) : $journal->the_post(); ?>
            <article class="journal-card"><span class="eyebrow"><?php echo esc_html(get_the_date('M j, Y')); ?></span><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><?php the_excerpt(); ?></article>
        <?php endwhile;
        wp_reset_postdata();
    else : ?>
        <p>Nothing here yet. A fresh notebook awaits.</p>
    <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
