# API map — input → output → dữ liệu

Các hàm bắt đầu mn_ / mini_notes_ là hàm tự viết của project. Các API dưới đây là WordPress, trừ bảng PHP cuối trang.

| API | Làm gì / input | Output | Dữ liệu ở đâu |
|---|---|---|---|
| register_post_type('note', args) | Đăng ký loại dữ liệu và capabilities | WP_Post_Type hoặc WP_Error | Đăng ký trong runtime; record Note ở wp_posts |
| add_action(hook, callback) | Gắn hàm vào một thời điểm xử lý | true | Callback trong runtime |
| add_filter(hook, callback) | Đăng ký hàm nhận, sửa và trả lại giá trị | true | Runtime; callback phải return giá trị |
| wp_enqueue_style(handle, url, deps, version) | Đăng ký CSS cần output | Không có giá trị hữu ích | File theme/assets/css; hàng đợi runtime |
| wp_enqueue_script(...) | Đăng ký JS và dependency | Không có giá trị hữu ích | File theme/assets/js |
| get_template_directory_uri() | URL thư mục theme cha | string URL | Thư mục theme hiện tại |
| get_header(), get_footer() | Nạp template header/footer | Thường dùng vì side effect render; có thể false khi không tìm được | File theme |
| get_template_part(slug) | Nạp template con | Dùng để render; false nếu không tìm được | File theme |
| new WP_Query(args) | Truy vấn posts bằng mảng điều kiện | Object WP_Query | wp_posts, có thể JOIN meta/taxonomy |
| have_posts() / query->have_posts() | Còn record trong vòng lặp không? | bool | Kết quả query trong RAM |
| the_post() / query->the_post() | Tiến con trỏ, đặt global post | Không có return dùng để render | Runtime |
| the_title(), the_content(), the_permalink() | Echo nội dung của global post | Output HTML/text/URL | WP_Post hiện tại |
| get_the_title(), get_the_content() | Lấy giá trị để dùng tiếp | string | WP_Post hiện tại |
| wp_reset_postdata() | Khôi phục global post sau query phụ | void | Runtime/main query |
| is_user_logged_in() | Đã xác thực? | bool | User hiện tại từ cookie |
| wp_get_current_user() | Lấy user hiện tại | WP_User; ID=0 nếu khách | wp_users/wp_usermeta + runtime |
| get_current_user_id() | Lấy ID user hiện tại | int; 0 nếu khách | Runtime |
| wp_signon(credentials, secure_cookie) | Kiểm tra login và set cookie | WP_User hoặc WP_Error | wp_users, wp_usermeta, HTTP cookie |
| wp_logout_url(redirect) | Tạo URL logout có nonce | string URL đã escape | Core logout endpoint |
| wp_safe_redirect(url) | Gửi header redirect tới host được cho phép | bool; không tự exit | HTTP response; luôn exit sau lời gọi |
| get_post(id) | Lấy một post | WP_Post hoặc null | wp_posts |
| wp_insert_post(data, true) | Tạo record | ID hoặc WP_Error | wp_posts |
| wp_update_post(data gồm ID, true) | Cập nhật record | ID hoặc WP_Error | wp_posts |
| wp_trash_post(id) | Chuyển Note vào Trash | WP_Post hoặc false/null | wp_posts + meta khôi phục |
| wp_delete_post(id, true) | Xóa vĩnh viễn fixture trong test | WP_Post hoặc false/null | Xóa record và dữ liệu liên quan |
| current_user_can(capability, id) | Kiểm tra quyền theo loại hành động | bool | Roles/caps + map_meta_cap filter |
| wp_nonce_field(action, name) | Echo hidden field nonce | string HTML, mặc định cũng echo | Form; không phải mật khẩu |
| wp_verify_nonce(nonce, action) | Xác minh nonce theo user/token/action/time | 1, 2 hoặc false | Runtime authentication |
| sanitize_text_field(value) | Làm sạch text một dòng | string | Trước khi lưu hoặc dùng query |
| sanitize_textarea_field(value) | Làm sạch plain text, giữ newline | string | Trước khi lưu content |
| esc_html(value) | Escape vào text trong HTML | string | Lúc render |
| esc_attr(value) | Escape vào HTML attribute | string | Lúc render |
| esc_textarea(value) | Escape vào textarea | string | Lúc render |
| esc_url(value) | Làm sạch/escape URL để output | string | href/action/src |
| get_post_meta(id,key,true) | Đọc một giá trị meta | Giá trị đã unserialize; thường string ở project này | wp_postmeta |
| update_post_meta(id,key,value) | Upsert meta | meta ID / true / false; false cũng có thể vì giá trị không đổi | wp_postmeta |
| delete_post_meta(id,key) | Xóa meta theo key | bool | wp_postmeta; bài thực hành STEP 13 |
| register_post_meta(type,key,args) | Khai báo schema/meta access | bool | Registry runtime, giá trị ở wp_postmeta |
| add_meta_box(...) | Đăng ký form phụ trong Admin | void | Runtime Admin |
| wp_insert_user(data) | Tạo user trong installer | ID hoặc WP_Error | wp_users + wp_usermeta |
| update_option(key,value) | Cập nhật cấu hình site | bool | wp_options |
| wp_slash(value) / wp_unslash(value) | Thêm/bỏ slashes theo convention WordPress | Cùng kiểu dữ liệu phù hợp | Dữ liệu tại boundary WordPress |
| is_wp_error(value) | Có phải lỗi WordPress không? | bool | Runtime |
| wp_die(message,title,args) | Dừng request với trang lỗi/status | Không quay lại luồng gọi | HTTP response |

## PHP cần đọc được

| PHP | Vai trò trong project |
|---|---|
| $variable, string, number, array | Nhận input, cấu hình query, dữ liệu post |
| if / elseif / else | Rẽ nhánh create/update, login, permission |
| foreach | Render lựa chọn status/priority, update meta |
| while | The Loop duyệt kết quả query |
| function, return | Tách từng trách nhiệm; trả kết quả về hàm gọi |
| include / require_once | Nạp file; require thất bại dừng thực thi |
| $_GET / $_POST / $_SERVER | Query URL / form / request method |
| isset, empty, is_string, ctype_digit | Kiểm tra kiểu và sự tồn tại |
| mb_strlen / mb_substr | Độ dài/cắt chuỗi UTF-8 như tiếng Việt |
| -> | Đọc thuộc tính hoặc gọi method của object |
| error_log / var_dump / print_r | Debug; không dump mật khẩu hoặc cookie |
| try / finally | Test luôn dọn fixture, dù có lỗi |
