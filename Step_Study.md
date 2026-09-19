# WordPress + PHP Study Roadmap
## Project: WP Mini Notes

> Mục tiêu: đi hết roadmap này để lấy lại nền tảng WordPress, hiểu rõ cách WordPress dùng PHP, và đủ tự tin xử lý các job WordPress nhỏ như sửa giao diện, sửa bug, thêm chức năng, chỉnh template, form, CRUD và custom post type.

---

# 0. Luật học

Project duy nhất xuyên suốt:

**WP Mini Notes** — một ứng dụng ghi chú nhỏ, giao diện kiểu workspace có sidebar tương tự Notion nhưng không clone Notion.

Stack:

- XAMPP
- Apache: `8080`
- MySQL: `3306`
- WordPress
- PHP
- HTML
- CSS
- JavaScript thuần
- WordPress API

Không dùng:

- Elementor cho phần học chính
- Plugin CRUD
- Page builder
- React/Vue trong project này
- Framework PHP khác

## Cách học

Mỗi Step phải làm theo thứ tự:

1. Đọc mục tiêu.
2. Tự thao tác/code.
3. Hiểu dữ liệu đi từ đâu → qua đâu → tới đâu.
4. Test.
5. Trả lời câu hỏi checkpoint.
6. Chỉ chuyển Step khi đã hiểu.

Khi AI đưa code, không chỉ copy. Với mỗi đoạn PHP quan trọng, phải trả lời được:

- Input từ đâu?
- Hàm này của PHP hay WordPress?
- Nó trả về gì?
- Dữ liệu nằm ở đâu?
- Nếu bỏ dòng này thì chuyện gì xảy ra?

---

# STEP 1 — Dựng WordPress local từ đầu

## Mục tiêu

Nhớ lại toàn bộ quy trình WordPress chạy trên máy local.

## Làm

Tạo:

```text
C:\xampp\htdocs\wp-mini-notes
```

Truy cập:

```text
http://localhost:8080/wp-mini-notes
```

Tạo database:

```text
wp_mini_notes
```

Cài WordPress và tạo admin account.

## Phải hiểu

```text
Browser
   ↓
Apache
   ↓
PHP
   ↓
WordPress
   ↓
MySQL
   ↓
HTML trả về Browser
```

## PHP cần ôn

- biến
- string
- array
- `if`
- function
- `include`
- `require`

## Checkpoint

Tự giải thích được:

- Apache làm gì? Dùng để giao tiếp giữa browser với Wordpress qua localhost:8080 cái 8080 này là chân apache đó
- PHP chạy ở browser hay server? serve
- MySQL dùng để làm gì? Quản lý database 
- `wp-config.php` chứa gì? mã kết nối giữa database và wordpress
- WordPress biết database nào để kết nối bằng cách nào? Chia ra nhiều folder folder đó như là teample mỗi foder có chức năng riêng

---

# STEP 2 — Mổ cấu trúc WordPress

## Mục tiêu

Không còn nhìn source WordPress như một đống folder khó hiểu.

## Tập trung vào

```text
wp-admin/
wp-includes/
wp-content/
    plugins/
    themes/
    uploads/
wp-config.php
index.php
.htaccess
```

## Quy tắc

Không sửa:

```text
wp-admin
wp-includes
```

Code của mình chủ yếu nằm trong:

```text
wp-content
```

## Bài tập

Tìm:

- theme đang active -> Dễ
- plugin đang active - Đã hiểu
- ảnh upload nằm ở đâu -> Source này không có ảnh
- database config nằm đâu - wp.config.php

## Checkpoint

Giải thích được sự khác nhau giữa:

- WordPress Core
- Theme
- Plugin
- Upload
- Database

---

# STEP 3 — Tạo theme đầu tiên bằng tay

## Mục tiêu

Hiểu một WordPress theme tối thiểu hoạt động thế nào.

Tạo:

```text
wp-content/themes/mini-notes/
```

File đầu tiên:

```text
style.css
index.php
functions.php
```

## Học

### `style.css`

WordPress dùng phần header của file này để nhận diện theme.

### `index.php`

Template fallback cơ bản.

### `functions.php`

Nơi đăng ký logic cho theme.

## Bài tập

Tạo theme có dòng:

```text
Hello WP Mini Notes
```

và activate nó trong Admin.

## Checkpoint

Trả lời:

- Tại sao chỉ tạo folder chưa đủ để WordPress nhận theme?
- `functions.php` khác `index.php` thế nào?
- Theme được activate ở đâu?

---

# STEP 4 — Template Hierarchy

## Mục tiêu

Hiểu WordPress chọn file PHP nào để render một URL.

Tạo và thử:

```text
header.php
footer.php
front-page.php
page.php
single.php
404.php
```

## Học

Các hàm:

```php
get_header();
get_footer();
get_template_part();
```

## Bài tập

Tạo:

- Trang Home
- Trang About
- Một Post
- Một URL 404

Sau đó xác định file PHP nào đang render từng trang.

## Checkpoint

Nếu mở một Page thì WordPress tìm template theo thứ tự nào?

Không cần thuộc lòng toàn bộ hierarchy, nhưng phải hiểu cơ chế fallback.

---

# STEP 5 — The Loop và WP_Query

## Mục tiêu

Hiểu cách WordPress lấy dữ liệu bài viết từ database và render ra HTML.

## Học

```php
have_posts();
the_post();
the_title();
the_content();
the_permalink();
```

Sau đó:

```php
WP_Query
```

## Bài tập

Hiển thị 5 bài viết mới nhất.

Sau đó chỉ hiển thị bài của một category.

## PHP cần hiểu

- object
- method
- `while`
- array cấu hình
- biến `$query`

## Checkpoint

Giải thích:

```php
while ($query->have_posts()) {
    $query->the_post();
}
```

đang làm gì.

---

# STEP 6 — Hooks: Actions và Filters

## Mục tiêu

Hiểu phần quan trọng nhất trong cách WordPress được mở rộng.

## Học

```php
add_action();
add_filter();
```

Ví dụ:

```php
add_action('wp_enqueue_scripts', 'mini_notes_assets');
```

## Bài tập

Load:

```text
assets/css/app.css
assets/js/app.js
```

đúng chuẩn WordPress, không hard-code `<link>` và `<script>`.

## Học thêm

```php
wp_enqueue_style();
wp_enqueue_script();
get_template_directory_uri();
```

## Checkpoint

Phân biệt:

**Action** = chạy thêm hành động.

**Filter** = nhận dữ liệu → sửa → trả dữ liệu lại.

---

# STEP 7 — Login / Logout và User

## Mục tiêu

Làm hệ thống login bằng chính WordPress.

## Chức năng

- Login page
- Login
- Logout
- Redirect
- Chặn dashboard nếu chưa login
- Hiển thị user hiện tại

## Học

```php
is_user_logged_in();
wp_signon();
wp_logout_url();
wp_get_current_user();
get_current_user_id();
wp_redirect();
```

## PHP cần hiểu

```php
$_POST
$_SERVER
```

## Security

```php
sanitize_text_field();
esc_html();
esc_url();
```

## Checkpoint

Mô tả flow:

```text
HTML form
   ↓
POST
   ↓
PHP
   ↓
wp_signon()
   ↓
WordPress kiểm tra user
   ↓
Cookie/session auth
   ↓
Redirect dashboard
```

---

# STEP 8 — Custom Post Type: Note

## Mục tiêu

Hiểu cách WordPress dùng `wp_posts` cho nhiều loại dữ liệu khác nhau.

Tạo:

```text
post_type = note
```

## Học

```php
register_post_type();
```

Note có:

- title
- content
- author
- date
- status

## Bài tập

Đăng ký CPT `note`.

Kiểm tra nó xuất hiện trong WordPress Admin.

Tạo vài note thủ công.

## Database

Mở phpMyAdmin và xem bảng:

```text
wp_posts
```

Quan sát:

```text
post_type
post_title
post_content
post_author
post_status
```

## Checkpoint

Giải thích tại sao WordPress không cần tạo bảng `notes` riêng cho project này.

---

# STEP 9 — CRUD Note bằng PHP

## Mục tiêu

Đây là Step quan trọng nhất của project.

Làm đủ:

```text
Create
Read
Update
Delete
```

## WordPress API

```php
wp_insert_post();
get_post();
WP_Query;
wp_update_post();
wp_delete_post();
```

## Quyền user

Note phải gắn với:

```php
get_current_user_id();
```

User A không được sửa note của User B.

## Checkpoint

Tự giải thích được toàn bộ flow:

```text
Form
↓
POST
↓
Validate
↓
Sanitize
↓
Permission
↓
Database
↓
Redirect
↓
Render
```

---

# STEP 10 — Nonce và Security

## Mục tiêu

Không viết form WordPress theo kiểu nguy hiểm.

## Học

```php
wp_nonce_field();
wp_verify_nonce();
current_user_can();
sanitize_text_field();
sanitize_textarea_field();
esc_html();
esc_attr();
esc_url();
```

## Phải hiểu 3 khái niệm khác nhau

### Validate

Dữ liệu có hợp lệ không?

### Sanitize

Làm sạch dữ liệu trước khi lưu.

### Escape

Làm dữ liệu an toàn trước khi output HTML.

## Bài tập

Bảo vệ:

- Create Note
- Update Note
- Delete Note

bằng nonce.

## Checkpoint

Trả lời được:

> Sanitize và Escape khác nhau ở đâu?

---

# STEP 11 — UI Workspace kiểu Notion

## Mục tiêu

Ghép phần WordPress/PHP thành sản phẩm dễ nhìn.

Layout:

```text
┌──────────────────┬──────────────────────────────┐
│ Mini Notes       │ Note title                   │
│                  │                              │
│ Home             │ Metadata                     │
│ Search           │                              │
│                  │ Content                      │
│ Notes            │                              │
│  - Note A        │                              │
│  - Note B        │                              │
│                  │                              │
│ + New Note       │                              │
│                  │                              │
│ User / Logout    │                              │
└──────────────────┴──────────────────────────────┘
```

## Làm

- Dark sidebar
- Note list
- Active note
- Editor form
- Responsive cơ bản

## Quy tắc

Không dành quá nhiều thời gian pixel-perfect.

Mục tiêu vẫn là WordPress/PHP.

---

# STEP 12 — Search

## Mục tiêu

Hiểu query parameters và GET request.

## Học PHP

```php
$_GET
```

## WordPress

Dùng:

```php
WP_Query
```

để search Note theo keyword.

URL ví dụ:

```text
/dashboard/?q=wordpress
```

## Checkpoint

Phân biệt:

```text
GET
POST
```

và biết khi nào nên dùng mỗi loại.

---

# STEP 13 — Custom Fields / Metadata

## Mục tiêu

Hiểu cách thêm dữ liệu phụ cho Note.

Mỗi Note thêm:

```text
Priority
Status
Category
```

Ví dụ:

```text
Priority: High
Status: In Progress
```

## Học

```php
get_post_meta();
update_post_meta();
delete_post_meta();
```

## Database

Quan sát:

```text
wp_postmeta
```

## Checkpoint

Phân biệt:

```text
wp_posts
wp_postmeta
```

---

# STEP 14 — WordPress Admin

## Mục tiêu

Hiểu frontend và backend WordPress không phải hai hệ thống tách biệt.

## Làm

Customize admin list của Note:

- thêm column Priority
- thêm column Status
- hiển thị Author

## Học

Thêm một vài hook Admin.

Không cần đi quá sâu.

---

# STEP 15 — Plugin đầu tiên

## Mục tiêu

Hiểu khi nào code thuộc Theme, khi nào thuộc Plugin.

Tách CPT `note` khỏi theme thành plugin:

```text
wp-content/plugins/mini-notes-core/
    mini-notes-core.php
```

Plugin chịu trách nhiệm:

```text
Note data / business logic
```

Theme chịu trách nhiệm:

```text
Presentation / UI
```

## Checkpoint

Trả lời:

> Nếu đổi theme thì dữ liệu/function Note có nên biến mất không?

Nếu câu trả lời là **không**, logic đó thường phù hợp để nằm trong Plugin.

---

# STEP 16 — Debug WordPress/PHP

## Mục tiêu

Biết tự tìm lỗi thay vì nhìn màn hình trắng.

Trong development:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
```

Kiểm tra:

```text
wp-content/debug.log
```

## PHP

Ôn:

```php
var_dump();
print_r();
error_log();
```

## Bài tập

Cố tình tạo:

- syntax error
- undefined variable
- query sai
- permission sai

Sau đó tìm nguyên nhân.

---

# STEP 17 — Database và phpMyAdmin

## Mục tiêu

Hiểu WordPress đang lưu những gì.

Tập trung các bảng:

```text
wp_posts
wp_postmeta
wp_users
wp_usermeta
wp_options
wp_terms
wp_term_taxonomy
wp_term_relationships
```

Không cần thuộc tất cả bảng.

## Bài tập

Tạo một Note → tìm record tương ứng trong DB.

Update Note → xem record thay đổi.

Delete Note → xem chuyện gì xảy ra.

---

# STEP 18 — REST API nhập môn

## Mục tiêu

Nối kiến thức WordPress với background frontend/Vue.

Mở:

```text
/wp-json/
```

Hiểu:

```text
WordPress cũng có REST API.
```

Thử đọc Posts qua API.

Sau đó hiểu cách CPT có thể expose REST bằng:

```php
'show_in_rest' => true
```

Không cần build SPA trong project này.

## Checkpoint

Liên hệ:

```text
Vue
↓ fetch/axios
WordPress REST API
↓
PHP
↓
MySQL
```

---

# STEP 19 — Job Simulation 1: WordPress Update

Giả lập job khách hàng:

> Tôi có website WordPress hiện tại. Hãy thêm một field "Priority" vào Notes và hiển thị badge trên trang chi tiết.

Tự thực hiện từ đầu.

Không xem đáp án trước.

---

# STEP 20 — Job Simulation 2: Bug Fix

Giả lập:

> User có thể sửa Note của user khác bằng cách thay ID trên URL.

Nhiệm vụ:

- tìm bug
- giải thích lỗ hổng
- sửa permission
- test User A / User B

---

# STEP 21 — Job Simulation 3: Frontend Fix

Giả lập:

> Sidebar bị vỡ trên màn hình mobile và title quá dài tràn layout.

Sửa:

- responsive
- overflow
- text wrapping

Mục tiêu là mô phỏng job Upwork nhỏ thực tế.

---

# FINAL PROJECT CHECK

Project hoàn thành phải có:

- [ ] WordPress cài local
- [ ] Custom theme
- [ ] Login
- [ ] Logout
- [ ] Protected dashboard
- [ ] Current user
- [ ] Custom Post Type Note
- [ ] Create Note
- [ ] Read Note
- [ ] Update Note
- [ ] Delete Note
- [ ] User ownership
- [ ] Nonce
- [ ] Sanitize
- [ ] Escape
- [ ] Search
- [ ] Post Meta
- [ ] Sidebar UI
- [ ] Responsive UI
- [ ] Plugin cơ bản
- [ ] Debug
- [ ] Hiểu database
- [ ] Hiểu REST API cơ bản

---

# PHP KNOWLEDGE CHECK

Sau project phải sử dụng được:

```text
Variables
Strings
Numbers
Arrays
Associative arrays
if / elseif / else
switch
for
foreach
while
Functions
Return values
include / require
$_GET
$_POST
$_SERVER
Objects
Methods
Basic OOP reading
Form handling
Redirect
Validation
Sanitization
Escaping
Debugging
```

Không cần trở thành PHP specialist.

Mục tiêu là:

> Đọc hiểu và tự viết PHP đủ tốt để phát triển, sửa chữa và debug WordPress.

---

# WORDPRESS KNOWLEDGE CHECK

Sau project phải giải thích được:

```text
WordPress request lifecycle cơ bản
Theme
Plugin
Template hierarchy
The Loop
WP_Query
Actions
Filters
Users
Authentication
Capabilities
Custom Post Types
Post Meta
Forms
Nonce
Sanitize
Escape
Database structure
Admin
REST API
Debugging
```

---

# CÁCH LÀM VIỆC VỚI CHATGPT

Trong cửa sổ học riêng, sử dụng instruction sau:

```text
Bạn là mentor WordPress + PHP của tôi.

Project học xuyên suốt là WP Mini Notes.

Tôi đã có nền tảng lập trình web, HTML/CSS/JavaScript và từng thao tác WordPress trước đây nhưng đã quên khá nhiều. Vì vậy không cần dạy quá chậm như người chưa từng code.

Hãy dẫn tôi đi đúng thứ tự trong file Step_Study.md.

QUY TẮC:
1. Chỉ làm MỘT STEP tại một thời điểm.
2. Không nhảy trước sang Step tiếp theo.
3. Trước khi code, giải thích ngắn mục tiêu và cơ chế.
4. Cho tôi tự thao tác trước khi đưa toàn bộ đáp án.
5. Code phải đơn giản, rõ ràng, ưu tiên PHP thuần + WordPress API.
6. Không dùng page builder hoặc plugin để che mất kiến thức cần học.
7. Với mỗi hàm WordPress mới, giải thích:
   - Nó làm gì?
   - Input là gì?
   - Output là gì?
   - Dữ liệu liên quan nằm đâu?
8. Sau mỗi Step, kiểm tra tôi bằng 3–5 câu hỏi ngắn hoặc một bài tập thực hành.
9. Nếu tôi hiểu sai, sửa thẳng vào chỗ sai.
10. Khi tôi nói "NEXT", chỉ khi Step hiện tại đã đạt checkpoint thì mới chuyển Step.
11. Ưu tiên kiến thức thực tế phục vụ sửa website WordPress, bug fix, update existing sites và các job freelance nhỏ.
12. Luôn liên hệ WordPress với PHP bên dưới để tôi hiểu bản chất chứ không học thuộc hàm.

Môi trường local:
- Windows
- XAMPP
- Apache 8080
- MySQL 3306
- Project: C:\xampp\htdocs\wp-mini-notes

Bắt đầu ở STEP 1.
```

---

# NHỊP HỌC GỢI Ý

Với người đã có base web:

```text
Ngày 1
STEP 1 → STEP 5

Ngày 2
STEP 6 → STEP 10

Ngày 3
STEP 11 → STEP 13

Ngày 4
STEP 14 → STEP 17

Ngày 5
STEP 18 → STEP 21
```

Không cần cố ép đúng 5 ngày.

Nếu một phần đã quá quen, làm checkpoint nhanh rồi đi tiếp.

Nếu gặp phần quan trọng như:

```text
Hooks
WP_Query
CRUD
Security
Database
```

thì nên dành nhiều thời gian hơn.

---

# ĐÍCH ĐẾN

Sau khi hoàn thành project, mục tiêu không phải là:

> "Tôi đã xem xong một khóa WordPress."

Mà phải là:

> "Đưa tôi một WordPress site đang chạy, tôi biết tìm code ở đâu, hiểu request đang đi thế nào, biết sửa template, query dữ liệu, xử lý form, thêm chức năng, kiểm tra quyền, debug PHP và lần theo dữ liệu xuống database."

Đó mới là trạng thái sẵn sàng để nhận các job WordPress nhỏ thực tế.
