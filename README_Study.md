# WP Mini Notes

Ứng dụng ghi chú cá nhân để ôn WordPress + PHP dựa trên roadmap chính thức **Step_Study.md**.

**Cách dùng để học:** chạy ứng dụng hoàn chỉnh, chọn một STEP trong [STUDY-GUIDE.md](docs/STUDY-GUIDE.md), đọc đúng file/hàm được chỉ ra, thử thay đổi nhỏ rồi trả lời checkpoint. Source đã triển khai các chức năng xuyên suốt; không cần đợi hoàn thành bài học mới có ứng dụng.

## Có gì trong source?

- Theme viết tay: Home, About, bài Post, 404, Login, Dashboard.
- Plugin riêng đăng ký CPT Note, xử lý login, CRUD, quyền sở hữu, metadata, Admin, REST.
- Sidebar tối, danh sách Note, editor plain text, responsive; không cần công cụ build.
- Tìm kiếm title/content bằng GET; 20 ghi chú mỗi trang.
- Priority (Low/Normal/High), Status (To do/In progress/Done), Category dạng text.
- WordPress Trash để khôi phục Note; không xóa vĩnh viễn từ workspace.
- Nonce, validation, sanitization, escaping, capability và kiểm tra chủ sở hữu.
- Ba tài khoản được tạo khi cài: study_admin, alice, bob. Mật khẩu ngẫu nhiên.
- Bài PHP, câu SQL đọc dữ liệu, bài tập 21 STEP và bộ kiểm thử.

Stack: PHP 8.1+, WordPress, XAMPP Apache 8080, MySQL/MariaDB 3306, HTML/CSS/JavaScript thuần. XAMPP đang có dùng MariaDB 10.4.32, tương thích giao diện MySQL trong bài học. Không có page builder, plugin CRUD, React/Vue hay framework PHP.

## Trạng thái bàn giao

- Source đã chạy trên WordPress **7.1** lấy từ bộ cài có sẵn trong Downloads.
- Đã kiểm thử bằng PHP **8.1.25** và MariaDB **10.4.32**.
- Preview của phiên làm việc này: **http://127.0.0.1:8091**.
- Database preview: **wp_mini_notes_study_test**.
- Chưa cài vào **C:\xampp\htdocs\wp-mini-notes**: môi trường công cụ báo đã cấp quyền nhưng thực tế vẫn từ chối ghi (EPERM); trình chạy shell cũng gặp lỗi khởi tạo.
- Preview dùng PHP development server; chưa xác nhận chạy qua Apache 8080. Script dưới đây chuẩn bị đúng đường dẫn và database trong roadmap.
- Xem [TEST-REPORT.md](docs/TEST-REPORT.md) để biết phần đã test và giới hạn.

## Cài bản chính vào XAMPP

1. Bật Apache **8080** và MySQL **3306** trong XAMPP.
2. Để bộ cài WordPress chính thức ở Downloads hoặc truyền đường dẫn ZIP.
3. Mở PowerShell tại thư mục chứa README này:

~~~powershell
.\Install-Local.ps1 -WordPressZip "$env:USERPROFILE\Downloads\wordpress-7.1.zip"
~~~

Nếu PowerShell chặn script tải về, đọc script rồi bỏ chặn riêng file bằng Properties → Unblock. Không cần tắt chính sách bảo mật toàn máy.

Script sẽ:

- Dừng nếu thư mục đích đã tồn tại hoặc database đã có bảng, tránh ghi đè dữ liệu.
- Giải nén core và chép theme/plugin vào thư mục wp-content.
- Tạo database wp_mini_notes, wp-config.php, salt ngẫu nhiên và rewrite Apache.
- Cài WordPress, activate plugin/theme, tạo Home/About/Login/Dashboard, post và note mẫu.
- Lưu thông tin đăng nhập trong **LOCAL-ACCESS.json cạnh README**, ngoài web root.

Mở **http://localhost:8080/wp-mini-notes**. Admin ở **/wp-admin/**, workspace ở **/dashboard/**.

Nếu MySQL đã có mật khẩu, thiết lập biến môi trường MN_DB_PASS trong terminal trước khi chạy. Không điền mật khẩu vào source hoặc commit. Có thể truyền -DbUser nếu không dùng root. Database user cần quyền tạo database và các bảng WordPress.

Nếu gặp 404 ở /dashboard/ khi Home vẫn chạy: WordPress Admin → Settings → Permalinks → Save Changes; kiểm tra Apache cho phép .htaccess và mod_rewrite. Không sửa core.

## Sơ đồ source

~~~text
wp-content/
  plugins/mini-notes-core/
    mini-notes-core.php       # entry point, require, activation, role
    includes/
      notes.php              # CPT, query, ownership, private data, REST
      forms.php              # login, protected page, create/update/delete
      admin.php              # columns, metadata form, owner-scoped list
  themes/mini-notes/
    style.css                # theme header
    functions.php            # theme support, enqueue, filters
    header.php / footer.php  # khung workspace
    front-page.php           # Home + 5 public posts
    page-dashboard.php       # danh sách, search, editor
    page-login.php           # form đăng nhập
    page.php / single.php / 404.php / index.php
    template-parts/post-card.php
    assets/css/app.css
    assets/js/app.js
docs/
  STUDY-GUIDE.md              # bản đồ 21 STEP + checkpoint
  API-MAP.md                 # input/output/storage của API
  DATA-FLOW.md               # lần theo request
  TEST-REPORT.md
  database-readonly.sql
labs/
  php-basics.php
  data.php
  query-by-category.php
  debug-lab.php
tests/
  integration.php
  http-smoke.mjs
tools/
  setup.php                  # installer CLI, không đặt trong web root
  router.php                 # chỉ dùng cho preview/test
Install-Local.ps1
Step_Study.md                 # bản sao roadmap gốc
~~~

## Quyết định thiết kế để hiểu đúng source

1. **CPT nằm trong plugin ngay ở bản hoàn chỉnh.** STEP 3–14 mô tả quá trình học; STEP 15 giải thích việc tách logic khỏi theme. Không cần tạo lại CPT trong theme.
2. **Note luôn có post_status = private.** Trạng thái công việc nằm ở meta mn_status, khác trạng thái xuất bản WordPress.
3. **Mỗi người chỉ xem/sửa/xóa Note của mình**, kể cả tài khoản admin trong workspace. Người có quyền truy cập trực tiếp database vẫn có thể đọc database; đây là kiểm soát truy cập ứng dụng, không phải mã hóa dữ liệu.
4. **Category của Note là custom field text** để học STEP 13. Category của public Post là taxonomy WordPress, dùng trong bài STEP 5.
5. **Content là plain text.** HTML bị loại bỏ; textarea được escape khi render. Không có rich-text editor trong workspace.
6. **Save là thao tác chủ động.** JavaScript báo unsaved changes và hỗ trợ Ctrl/Cmd+S; không có autosave.
7. **wp_trash_post()** dùng cho nút Move to Trash. wp_delete_post() có thể xóa vĩnh viễn custom post type ngay cả khi force_delete=false; chỉ dùng cho fixture tự tạo trong test. Đây là điều chỉnh cần thiết so với danh sách API khái quát trong roadmap.
8. **REST Note không public.** /wp-json/wp/v2/posts đọc public; /wp-json/wp/v2/notes cần xác thực và chỉ trả Note của chủ sở hữu. Cookie auth từ JavaScript còn cần X-WP-Nonce.
9. **Đổi theme giữ lại dữ liệu và logic plugin.** Giao diện workspace phụ thuộc Mini Notes theme; WordPress Admin và REST vẫn thuộc plugin/core.
10. **Debug bật cho local**, ghi log và tắt hiển thị lỗi ra trang để không làm hỏng response/redirect. Bài debug riêng hướng dẫn bật display trong môi trường thử.

## Test lại

Bộ test chỉ chạy trên bản local dành riêng cho học. integration.php tạo và xóa vĩnh viễn fixture của chính nó; http-smoke.mjs để Note test trong Trash.

~~~powershell
$env:MN_ROOT = "C:\xampp\htdocs\wp-mini-notes"
$env:MN_RUN_TESTS = "1"
& C:\xampp\php\php.exe .\tests\integration.php
node .\tests\http-smoke.mjs "http://localhost:8080/wp-mini-notes" ".\LOCAL-ACCESS.json"
~~~

Node chỉ dùng cho kiểm thử, không phải dependency chạy ứng dụng.

## Tài liệu chính thức

- [Cài WordPress](https://developer.wordpress.org/advanced-administration/before-install/howto-install/)
- [register_post_type](https://developer.wordpress.org/reference/functions/register_post_type/)
- [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/)
- [wp_trash_post](https://developer.wordpress.org/reference/functions/wp_trash_post/)
- [wp_delete_post](https://developer.wordpress.org/reference/functions/wp_delete_post/)
- [REST authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)

