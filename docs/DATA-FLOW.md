# Lần theo dữ liệu trong WP Mini Notes

Các đường dẫn trong tài liệu này tính từ thư mục source. Plugin: wp-content/plugins/mini-notes-core. Theme: wp-content/themes/mini-notes.

## Đọc trang dashboard

~~~text
GET /dashboard/?q=meeting&note=15
→ Apache chuyển request tới WordPress index.php qua .htaccess
→ WordPress nạp wp-config.php → wp-settings.php → plugin/theme hooks
→ init: mn_register_note_type()
→ WordPress main query xác định đây là Page dashboard
→ template_redirect: mn_protect_pages()
    chưa login → redirect /login/
    note không tồn tại → 404
    note thuộc người khác → 403
→ page-dashboard.php
→ $_GET['q'] → mn_input() → sanitize_text_field()
→ mn_query_notes() → WP_Query(post_type=note, author=current user, s=keyword)
→ wp_posts → WP_Post objects
→ vòng while/have_posts/the_post
→ esc_html / esc_attr / esc_textarea / esc_url
→ HTML response → browser render CSS, chạy JavaScript
~~~

WordPress có main query cho Page; danh sách Note là query riêng. wp_reset_postdata() đưa global post trở về Page sau vòng lặp phụ.

## Tạo và sửa Note

~~~text
HTML form page-dashboard.php
→ POST /wp-admin/admin-post.php
  action=mn_save_note, note_id, title, content, priority, status, category, mn_nonce
→ WordPress dispatch admin_post_mn_save_note
→ mn_save_note()
  kiểm tra POST
  kiểm tra đăng nhập + capability
  kiểm tra ID dạng số
  wp_verify_nonce(action gồm note ID)
  nếu sửa: kiểm tra owner + current_user_can('edit_post', id)
  validate độ dài, enum; sanitize dữ liệu
  wp_insert_post() nếu ID=0 / wp_update_post() nếu ID>0
  wp_insert_post_data filter ép private và giữ owner
  update_post_meta() x 3
→ redirect 302 /dashboard/?note=ID&message=created|updated
→ browser GET trang mới
→ render dữ liệu vừa lưu
~~~

Redirect sau POST tránh submit lại khi refresh. Nonce chống CSRF, không chứng minh quyền sở hữu. Vì vậy phải có cả nonce và permission.

## Xóa và khôi phục

~~~text
POST action=mn_delete_note + note_id + nonce
→ mn_delete_note()
→ kiểm tra POST/login/nonce/owner/delete capability
→ wp_trash_post(id)
→ wp_posts.post_status = trash
→ redirect dashboard
~~~

Query workspace chỉ lấy private nên Note biến mất khỏi danh sách. Chủ sở hữu vào WordPress Admin → Notes → lọc Trash bằng URL edit.php?post_type=note&post_status=trash để Restore. Plugin ẩn các tab đếm tổng toàn hệ thống nhằm tránh lộ số lượng Note của người khác. Sau Restore, filter giữ private. Không nhầm mn_status=done với post_status=trash.

## Đăng nhập và logout

POST form → admin_post_nopriv_mn_login → nonce → wp_signon(credentials) → WP_User hoặc WP_Error.

WordPress lưu hash mật khẩu trong wp_users; phiên đăng nhập dùng auth cookie/token của WordPress, không dùng PHP session_start() trong project này. wp_signon() phải chạy trước khi output HTML để set cookie/redirect được.

Logout link do wp_logout_url() sinh, chứa nonce. Core xóa cookie và chuyển về /login/. Mật khẩu không đi trong query string.

## REST

- Public: GET /wp-json/wp/v2/posts.
- Private: /wp-json/wp/v2/notes.
- rest_pre_dispatch → chặn anonymous.
- rest_note_query → ép author=current user và private cho collection.
- map_meta_cap → chặn đọc/sửa/xóa Note khác owner ở detail.
- rest_pre_insert_note → validate title/content/metadata.
- Core REST controller gọi WordPress post/meta API; không viết SQL CRUD thủ công.

HTTP cookie auth cần cả auth cookie và X-WP-Nonce được tạo cho action wp_rest. Chỉ mở URL REST trong tab khi đã login chưa bảo đảm được xác thực REST, do thiếu nonce. Bài tests/integration.php gọi REST controller nội bộ để quan sát; HTTP login/CSRF được kiểm riêng bằng http-smoke.mjs.

## Database

| Dữ liệu | Nơi lưu |
|---|---|
| Note title/content/author/date/private/trash | wp_posts |
| mn_priority, mn_status, mn_category | wp_postmeta |
| User, email, password hash | wp_users |
| Role/capability của user, session token | wp_usermeta |
| URL site, theme active, plugin active, cấu hình | wp_options |
| Category của public Post | wp_terms, wp_term_taxonomy, wp_term_relationships |
| Upload ảnh/media | file trong wp-content/uploads + attachment record trong wp_posts |

Tên wp_ là prefix của bộ cài này; không giả định mọi site khách hàng đều dùng prefix đó.
