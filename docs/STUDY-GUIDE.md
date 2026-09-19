# Ôn 21 STEP trên source hoàn chỉnh

Bạn đã có source để chạy và đọc. Không cần làm lại toàn bộ ứng dụng mỗi STEP. Dùng đúng thứ tự trong Step_Study.md; mỗi buổi chỉ chọn một STEP để ôn sâu.

**Nhịp ôn:** đọc mục tiêu → mở file → tự dự đoán hành vi → thao tác/test → giải thích luồng dữ liệu → trả lời checkpoint. Nếu bí, dùng API-MAP.md và DATA-FLOW.md. Đừng coi việc source đã chạy là bằng chứng mình đã hiểu.

Đường dẫn ngắn dưới đây:

- **Plugin:** wp-content/plugins/mini-notes-core/
- **Theme:** wp-content/themes/mini-notes/
- **W:** thư mục WordPress đã cài (bản chính là C:\xampp\htdocs\wp-mini-notes).
- File trong docs/labs/tests nằm ngoài web root.

## STEP 1 — WordPress local và PHP nền tảng

**Mục tiêu:** phân biệt web server, PHP interpreter, ứng dụng WordPress và database.

**Đọc:** Install-Local.ps1 → tools/setup.php → W/wp-config.php. Không chia sẻ nội dung mật khẩu/salt của wp-config.php.

**Thử:**

1. Mở Home, /login/, /wp-admin/.
2. Mở phpMyAdmin, đối chiếu DB_NAME với database có bảng.
3. Chạy C:\xampp\php\php.exe labs\php-basics.php.
4. Trong labs/data.php, đổi priority/done rồi dự đoán output trước khi chạy.
5. Đổi require thành include với một tên file không tồn tại trong bản scratch. So sánh lỗi; không sửa file ứng dụng.

**Dòng dữ liệu:** Browser → Apache → PHP thực thi WordPress → MySQL/MariaDB → PHP dựng response → Apache → Browser.

**Checkpoint:**

- Vì sao URL dùng 8080 nhưng DB_HOST có thể dùng 3306?
- PHP chạy ở đâu? Browser có nhận source PHP không?
- WordPress lấy tên database và tài khoản kết nối từ đâu?
- Array/function/return trong bài PHP đóng vai trò gì? include khác require thế nào?

## STEP 2 — Cấu trúc WordPress

**Đọc:** W/wp-admin, W/wp-includes, W/wp-content; wp-config.php, index.php, .htaccess.

**Thử:** tìm theme/plugin active trong Admin rồi đối chiếu wp_options bằng docs/database-readonly.sql. Upload một ảnh nhỏ bằng tài khoản admin và tìm file trong uploads. Không sửa core.

**Checkpoint:**

- Theme khác plugin ở trách nhiệm nào?
- File ảnh và record attachment có cùng nằm trong database không?
- Vì sao cập nhật WordPress có thể làm mất thay đổi nếu bạn sửa wp-includes?

## STEP 3 — Theme tối thiểu

**Đọc:** Theme/style.css, index.php, functions.php.

**Thử:** trong một bản sao local, đổi Theme Name rồi mở Appearance → Themes; khôi phục tên sau khi quan sát. Thêm một câu vào fallback index.php, nhận xét vì sao Home chưa đổi.

**Checkpoint:**

- Header style.css giúp WordPress nhận diện theme như thế nào?
- functions.php chạy để đăng ký gì, còn index.php dùng khi nào?
- Theme đã active ở đâu và option nào lưu lựa chọn này?

## STEP 4 — Template Hierarchy

**Đọc:** front-page.php, page-dashboard.php, page-login.php, page.php, single.php, 404.php, header.php, footer.php, template-parts/post-card.php.

**Thử:** mở Home, About, một Post và URL không tồn tại. Thêm HTML comment chứa tên file vào từng template để xem View Source, rồi xóa comment.

**Checkpoint:**

- Page dashboard chọn page-dashboard.php trước page.php vì sao?
- Page thông thường fallback qua các loại template nào?
- get_header(), get_footer(), get_template_part() giúp tránh lặp phần gì?

Gợi ý thứ tự Page cổ điển: template được gán riêng → page-{slug}.php → page-{id}.php → page.php → singular.php → index.php. front-page.php có ưu tiên riêng với trang đầu; chỉ mô tả thứ tự này không đủ để dự đoán mọi URL.

## STEP 5 — The Loop và WP_Query

**Đọc:** Theme/front-page.php, page-dashboard.php; Plugin/includes/notes.php → mn_query_notes().

**Thử:** Home có query 5 public Posts. Chạy labs/query-by-category.php với MN_ROOT để lấy category Notebook. Tạo thêm public Post có/không có category này, so sánh kết quả.

~~~powershell
$env:MN_ROOT = "C:\xampp\htdocs\wp-mini-notes"
& C:\xampp\php\php.exe .\labs\query-by-category.php
~~~

**Checkpoint:**

- while ($query->have_posts()) kiểm tra điều gì và the_post() thay đổi gì?
- Main query của dashboard là Page hay danh sách Note?
- Bỏ wp_reset_postdata() sau query phụ có thể ảnh hưởng phần render nào?

## STEP 6 — Actions và Filters

**Đọc:** Theme/functions.php → mini_notes_assets(), excerpt_length, private_title_format; Plugin/mini-notes-core.php.

**Thử:** đổi độ dài excerpt từ 24 thành 10 trên bản học và reload Home. Tìm CSS/JS được enqueue trong HTML response.

**Checkpoint:**

- Action cần thực hiện hành động gì, filter phải return điều gì?
- Input/output của callback private_title_format là gì?
- Vì sao không hard-code đường dẫn C:\xampp vào href của CSS?

## STEP 7 — Login, Logout và User

**Đọc:** Theme/page-login.php; Plugin/includes/forms.php → mn_login(), mn_require_member(), mn_protect_pages(); Theme/header.php.

**Thử:** login Alice; mở dashboard; logout; mở lại dashboard từ URL cũ. Thử mật khẩu sai để quan sát thông báo chung.

**Checkpoint:**

- Form gửi POST tới đâu? Trường action chọn callback nào?
- wp_signon() trả về gì nếu đúng/sai?
- Cookie auth của WordPress khác việc tự gọi PHP session_start() như thế nào?
- Tại sao phải redirect trước khi render HTML?

## STEP 8 — CPT Note

**Đọc:** Plugin/includes/notes.php → mn_register_note_type().

**Thử:** mở Admin → Notes. Tạo một Note rồi tìm row có post_type=note trong wp_posts.

**Checkpoint:**

- Note dùng chung bảng nào với Page/Post?
- public=false, publicly_queryable=false và show_in_rest=true có cùng ý nghĩa không?
- Vì sao chưa cần bảng notes riêng?

## STEP 9 — CRUD Note

**Đọc:** Plugin/includes/forms.php → mn_save_note(), mn_delete_note(); Theme/page-dashboard.php.

**Thử:** tạo Note, sửa title/content, reload, tìm kiếm, chuyển Trash. Mở Admin với chính chủ sở hữu để khôi phục.

**Checkpoint:**

- note_id=0 chọn nhánh nào? ID có sẵn chọn nhánh nào?
- Tại sao post_author phải lấy từ user hiện tại, không tin hidden field?
- Vì sao browser GET lại trang sau khi POST thành công?
- wp_trash_post() khác wp_delete_post() ra sao đối với CPT?

## STEP 10 — Nonce và Security

**Đọc:** forms.php; notes.php → mn_owner_capabilities(), mn_private_note_data(); các hàm esc_* trong template.

**Thử:** chạy tests/http-smoke.mjs trên database local dành cho test. Quan sát missing nonce, ID giả, title trống và script injection bị xử lý thế nào.

**Checkpoint:**

- Validate, sanitize, escape áp dụng ở những thời điểm nào?
- Nonce hợp lệ có đủ quyền sửa Note của người khác không?
- Vì sao dữ liệu đã sanitize vẫn phải escape khi render?
- mn_input() xử lý title[]=x thay vì title=x thế nào?

## STEP 11 — UI Workspace

**Đọc:** Theme/header.php, page-dashboard.php, assets/css/app.css, assets/js/app.js.

**Thử:** tạo Note dài; chuyển giữa Note; sửa nhưng chưa save rồi bấm link khác để quan sát cảnh báo. Dùng Ctrl/Cmd+S để lưu.

**Checkpoint:**

- PHP render phần nào, CSS quyết định phần nào, JavaScript bổ sung phần nào?
- Nếu JS không chạy, form còn tạo/sửa/xóa được không?
- Source có autosave không? Tín hiệu Unsaved changes xuất hiện khi nào?

## STEP 12 — Search

**Đọc:** form method=get trong page-dashboard.php → mn_input($_GET,'q') → mn_query_notes().

**Thử:** tìm một từ chỉ có trong content. Tìm từ không tồn tại. Đăng nhập Bob và tìm title của Alice.

**Checkpoint:**

- Vì sao search dùng GET, còn save/delete dùng POST?
- Keyword đi vào tham số nào của WP_Query?
- Điều kiện author được áp dụng ở đâu? Pagination giữ lại keyword bằng cách nào?

## STEP 13 — Custom Fields / Metadata

**Đọc:** notes.php → register_post_meta; forms.php → update_post_meta; page-dashboard.php → get_post_meta.

**Thử:** đổi Priority/Status/Category, xem wp_postmeta. Trên một Note test, dùng WordPress API để xóa mn_category rồi reload.

**Checkpoint:**

- post_status=private và mn_status=in-progress khác nhau thế nào?
- Tại sao một post có thể có nhiều row trong wp_postmeta?
- Category của Note hiện là meta hay taxonomy?

## STEP 14 — WordPress Admin

**Đọc:** Plugin/includes/admin.php.

**Thử:** login bằng WordPress Admin với Alice; xem columns Priority/Status/Category/Author; sửa metadata. Sau đó mở frontend để đối chiếu.

**Checkpoint:**

- Admin và frontend có ghi vào cùng bảng không?
- Hook nào thêm column và hook nào render giá trị?
- Vì sao query Admin cần lọc author và save metadata vẫn phải kiểm tra nonce/permission?

Trang Trash của Notes: /wp-admin/edit.php?post_type=note&post_status=trash. Các tab đếm trạng thái toàn hệ thống được ẩn; direct filter URL vẫn hoạt động.

## STEP 15 — Tách Plugin

**Đọc:** Plugin/mini-notes-core.php và Theme/functions.php.

**Thử:** đổi sang một theme mặc định trên bản học, quan sát Admin → Notes vẫn có dữ liệu. Activate lại Mini Notes. Không deactivate plugin nếu đang kiểm tra chức năng CPT.

**Checkpoint:**

- Vì sao CPT và permission không nên phụ thuộc theme?
- Đổi theme khác deactivate plugin ở điểm nào?
- require_once giúp tránh tình huống nào khi nạp file PHP?

## STEP 16 — Debug

**Đọc:** W/wp-config.php (WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY); W/wp-content/debug.log; labs/debug-lab.php.

**Thử:**

~~~powershell
& C:\xampp\php\php.exe .\labs\debug-lab.php inspect
& C:\xampp\php\php.exe .\labs\debug-lab.php undefined
& C:\xampp\php\php.exe .\labs\debug-lab.php syntax
~~~

Query sai: đổi category_name trong bản lab rồi quan sát kết quả rỗng. Permission sai: chạy kiểm thử cross-owner. Syntax error chỉ tạo trong file scratch, không làm hỏng plugin đang chạy.

**Checkpoint:**

- Syntax error khác warning undefined variable như thế nào?
- Vì sao bản local bật log nhưng tắt display trên HTTP response?
- Vì sao không var_dump mật khẩu/cookie và không echo trước redirect?

## STEP 17 — Database / phpMyAdmin

**Đọc:** docs/database-readonly.sql và DATA-FLOW.md.

**Thử:** chạy SELECT trước/sau Create → Update → Trash → Restore, ghi lại ID của cùng một Note.

**Checkpoint:**

- Field nào đổi khi sửa title, khi sửa priority, khi vào Trash?
- wp_users khác wp_usermeta ở vai trò nào?
- Theme active và site URL nằm trong bảng nào?
- Sau Trash, row còn tồn tại hay đã biến mất?

## STEP 18 — REST nhập môn

**Đọc:** notes.php → mn_rest_auth(), mn_rest_query(), mn_rest_validate(); tests/integration.php.

**Thử:** mở /wp-json/ và /wp-json/wp/v2/posts. Gọi /wp-json/wp/v2/notes không có auth để thấy 401. Chạy test để thấy owner đọc được, người khác 403.

**Checkpoint:**

- REST response khác trang HTML ở định dạng nào?
- show_in_rest có đồng nghĩa public access không?
- Với cookie auth, tại sao cần X-WP-Nonce cho REST?
- Nếu sau này gọi từ frontend fetch/axios, server vẫn phải kiểm tra owner ở đâu?

Chưa xây SPA. Không hard-code Application Password hoặc nonce vào source.

## STEP 19 — Job simulation: Priority badge

**Bài khách hàng:** “Thêm Priority vào Notes và hiển thị badge ở trang chi tiết.”

Bản hoàn chỉnh đã có field và badge. Để tự luyện: tạo một bản sao của template, tạm bỏ badge trên bản học rồi tự khôi phục bằng get_post_meta() và allowlist class/label. Không xem đoạn badge hoàn chỉnh trước khi viết.

**Nghiệm thu:** High/Normal/Low đúng nhãn và màu; Note cũ thiếu meta dùng Normal; metadata còn nguyên sau reload; dữ liệu lạ không chèn được HTML.

**Checkpoint:** data được lưu ở đâu, đọc ở đâu, escape ở đâu?

## STEP 20 — Job simulation: sửa ID của người khác

**Bài khách hàng:** “User có thể sửa Note của user khác bằng cách thay ID.”

Bản hoàn chỉnh đã bảo vệ. Không bật lại lỗ hổng trên bản đang chạy. Hãy viết test trước: login Alice/Bob, dùng ID Alice trong URL/form/REST của Bob.

**Nghiệm thu:** read/update/delete đều bị chặn; title/content không đổi; query search không lộ Note khác; own CRUD vẫn hoạt động. Theo dõi tests/integration.php và tests/http-smoke.mjs.

**Checkpoint:** nếu chỉ ẩn nút Edit bằng JS thì còn đường tấn công nào? Nếu chỉ thêm nonce thì còn thiếu điều kiện gì?

## STEP 21 — Job simulation: mobile và title dài

**Đọc:** app.css → media queries, min-width:0, overflow-wrap:anywhere; app.js → fitTitle() và menu.

**Thử:** màn hình 320px, 390px, desktop; title 180 ký tự, một từ dài không có khoảng trắng. Mở/đóng menu, nhấn Escape, edit/save.

**Nghiệm thu:** không tràn ngang; title tự giãn; nội dung nhập đọc được; nút Save dùng được; sidebar không che editor khi đóng; keyboard focus nhìn thấy.

**Checkpoint:** min-width:0 sửa vấn đề gì trong grid/flex? overflow-wrap khác overflow:hidden ra sao? Khi tắt JS, textarea vẫn có thể cuộn để đọc nội dung dài không?

## Chốt project

Đánh dấu hai cột riêng:

| Nhóm | Source có chức năng | Tôi tự giải thích/sửa được |
|---|---|---|
| Local, cấu trúc, theme, hierarchy | Có source + installer; Apache đích chưa được xác minh | Chưa tự đánh giá |
| Loop, hooks | Có | Chưa tự đánh giá |
| Auth, CRUD, owner, nonce | Có và đã test | Chưa tự đánh giá |
| Search, metadata, Admin, plugin | Có và đã test phần lõi | Chưa tự đánh giá |
| UI, responsive | Có và đã kiểm tra browser | Chưa tự đánh giá |
| Debug, database, REST | Có lab/query/test | Chưa tự đánh giá |

Mục tiêu cuối: nhận một WordPress site, xác định được template/hook xử lý request, sửa thay đổi nhỏ có test và giải thích được dữ liệu đi đâu. Source hoàn chỉnh là tài liệu thực hành, không tự thay thế phần hiểu của bạn.
