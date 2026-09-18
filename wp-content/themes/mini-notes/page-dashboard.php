<?php
get_header();
if (!mini_notes_core_required()) {
    get_footer();
    return;
}
$search = sanitize_text_field(mn_input($_GET, 'q'));
$page = max(1, absint(mn_input($_GET, 'notes_page', '1')));
$notes = mn_query_notes($search, $page);
$is_new = mn_input($_GET, 'new') === '1';
$selected_id = absint(mn_input($_GET, 'note', '0'));
if (!$selected_id && !$is_new && $notes->have_posts()) {
    $selected_id = $notes->posts[0]->ID;
}
$selected_note = $selected_id ? get_post($selected_id) : null;
$is_new = $is_new || !$selected_note;
$priority = $selected_note ? (get_post_meta($selected_id, 'mn_priority', true) ?: 'normal') : 'normal';
$status = $selected_note ? (get_post_meta($selected_id, 'mn_status', true) ?: 'todo') : 'todo';
$category = $selected_note ? get_post_meta($selected_id, 'mn_category', true) : '';
$messages = ['created' => 'Note created.', 'updated' => 'Changes saved.', 'deleted' => 'Note moved to Trash.'];
$message_key = mn_input($_GET, 'message');
?>
<div class="workspace-wrap">
    <div class="workspace-heading"><div><span class="eyebrow">A LITTLE SPACE TO THINK</span><h1>My notes<span class="count-pill"><?php echo esc_html($notes->found_posts); ?></span></h1><p class="muted">Keep your ideas close. Give them room to grow.</p></div><a class="button" href="<?php echo esc_url(mn_workspace_url(['new' => '1'])); ?>"><?php mini_notes_icon('plus'); ?> New note</a></div>
    <?php if (isset($messages[$message_key])) : ?><p class="notice success" role="status"><?php echo esc_html($messages[$message_key]); ?></p><?php endif; ?>
    <div class="notes-workspace">
        <section class="note-list-panel" aria-label="Your notes">
            <form action="<?php echo esc_url(mn_workspace_url()); ?>" method="get" class="search-form" role="search">
                <label class="sr-only" for="note-search">Search notes</label>
                <?php mini_notes_icon('search'); ?><input type="search" id="note-search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="Search your notes…"><button type="submit" aria-label="Search">↵</button>
            </form>
            <div class="list-caption"><span><?php echo $search !== '' ? 'SEARCH RESULTS' : 'ALL NOTES'; ?></span><span>Last edited ↓</span></div>
            <div class="note-list">
            <?php if ($notes->have_posts()) : while ($notes->have_posts()) : $notes->the_post();
                $note_priority = get_post_meta(get_the_ID(), 'mn_priority', true) ?: 'normal';
                $note_status = get_post_meta(get_the_ID(), 'mn_status', true) ?: 'todo';
            ?>
                <a class="note-card <?php echo !$is_new && get_the_ID() === $selected_id ? 'selected' : ''; ?>" <?php if (!$is_new && get_the_ID() === $selected_id) echo 'aria-current="true"'; ?> href="<?php echo esc_url(mn_workspace_url(['note' => get_the_ID(), 'q' => $search, 'notes_page' => $page])); ?>">
                    <span class="note-card-heading"><?php mini_notes_icon('note'); ?><strong><?php echo esc_html(get_the_title()); ?></strong></span>
                    <p><?php echo esc_html(wp_trim_words(get_the_content(), 18)); ?></p>
                    <span class="note-card-meta"><span class="status-dot status-<?php echo esc_attr($note_status); ?>"></span><?php echo esc_html(mn_choices('status')[$note_status] ?? 'To do'); ?><time><?php echo esc_html(get_the_modified_date('M j')); ?></time></span>
                </a>
            <?php endwhile; wp_reset_postdata(); else : ?>
                <div class="list-empty"><?php mini_notes_icon('note'); ?><h3><?php echo $search !== '' ? 'No matching notes' : 'A fresh page'; ?></h3><p><?php echo $search !== '' ? 'Try another word or clear your search.' : 'Create your first note to get started.'; ?></p><?php if ($search !== '') : ?><a href="<?php echo esc_url(mn_workspace_url()); ?>">Clear search</a><?php endif; ?></div>
            <?php endif; ?>
            </div>
            <?php if ($notes->max_num_pages > 1) : ?><nav class="pagination" aria-label="Notes pages">
                <?php for ($i = 1; $i <= $notes->max_num_pages; $i++) : ?><a <?php if ($i === $page) echo 'aria-current="page"'; ?> href="<?php echo esc_url(mn_workspace_url(['q' => $search, 'notes_page' => $i])); ?>"><?php echo esc_html($i); ?></a><?php endfor; ?>
            </nav><?php endif; ?>
        </section>
        <section class="editor-panel" aria-label="<?php echo $is_new ? 'New note' : 'Edit note'; ?>">
            <div class="editor-top"><span><?php mini_notes_icon('note'); ?> <?php echo $is_new ? 'A fresh page' : 'Personal note'; ?></span><span class="save-state" aria-live="polite"><?php echo $is_new ? 'Not saved yet' : 'Saved'; ?></span></div>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="note-editor">
                <input type="hidden" name="action" value="mn_save_note">
                <input type="hidden" name="note_id" value="<?php echo esc_attr($is_new ? 0 : $selected_id); ?>">
                <?php wp_nonce_field('mn_save_note_' . ($is_new ? 0 : $selected_id), 'mn_nonce'); ?>
                <label class="sr-only" for="note-title">Note title</label>
                <textarea id="note-title" class="title-input" name="title" rows="2" maxlength="180" placeholder="Untitled note" required><?php echo esc_textarea($is_new ? '' : $selected_note->post_title); ?></textarea>
                <div class="note-metadata">
                    <?php foreach (['priority' => $priority, 'status' => $status] as $field => $current_value) : ?>
                        <div><label for="note-<?php echo esc_attr($field); ?>"><?php echo esc_html(ucfirst($field)); ?></label><select id="note-<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>"><?php foreach (mn_choices($field) as $value => $label) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($current_value, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
                    <?php endforeach; ?>
                    <div><label for="note-category">Category</label><input id="note-category" name="category" maxlength="60" placeholder="e.g. Work" value="<?php echo esc_attr($category); ?>"></div>
                </div>
                <?php if (!$is_new) : ?><div class="note-detail"><span class="badge priority-<?php echo esc_attr($priority); ?>"><?php echo esc_html(mn_choices('priority')[$priority] ?? 'Normal'); ?> priority</span><span>Edited <?php echo esc_html(get_the_modified_date('M j, Y · H:i', $selected_note)); ?> · <?php echo esc_html(wp_get_current_user()->display_name); ?></span></div><?php endif; ?>
                <label class="sr-only" for="note-content">Note content</label>
                <textarea id="note-content" name="content" maxlength="100000" placeholder="Start writing. This space is yours…" spellcheck="true"><?php echo esc_textarea($is_new ? '' : $selected_note->post_content); ?></textarea>
                <div class="editor-actions"><span class="editor-hint">Plain text, a clear mind. <span class="shortcut">Ctrl / ⌘ + S to save</span></span><button class="button" type="submit"><?php echo $is_new ? 'Create note' : 'Save changes'; ?></button></div>
            </form>
            <?php if (!$is_new) : ?>
                <form class="delete-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="mn_delete_note"><input type="hidden" name="note_id" value="<?php echo esc_attr($selected_id); ?>"><?php wp_nonce_field('mn_delete_note_' . $selected_id, 'mn_nonce'); ?><span class="delete-confirmation" hidden>Move this note to Trash? You can restore it in WordPress Admin. <button class="cancel-delete" type="button">Cancel</button></span><button class="delete-button" type="submit">Move to Trash</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php get_footer(); ?>
