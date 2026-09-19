# Báo cáo kiểm thử — WP Mini Notes

Ngày: 2026-09-15. Bản source 1.0.0.

## Môi trường thực tế

- WordPress 7.1 từ ZIP có sẵn trong Downloads.
- PHP 8.1.25 của XAMPP.
- MariaDB 10.4.32, port 3306.
- HTTP preview: PHP development server, http://127.0.0.1:8091.
- Database: wp_mini_notes_study_test.
- Fresh installer check: database wp_mini_notes_install_check, URL cấu hình http://localhost:8080/wp-mini-notes trong bản core riêng ở workspace.
- Browser: Codex In-app Browser.

## Kết quả

| Hạng mục | Kết quả |
|---|---|
| PHP syntax: tất cả file PHP tự viết | Pass |
| JavaScript syntax: app.js và HTTP test | Pass |
| WordPress / REST integration | 29/29 pass |
| HTTP form / auth / CRUD / CSRF | 33/33 pass |
| PHP basics lab | Pass |
| Query public Post theo category Notebook | Pass, trả post mẫu |
| Fresh setup.php trên database mới | Pass, không warning/error |
| Rewrite .htaccess cho /wp-mini-notes/ | Nội dung được sinh đúng; chưa test qua Apache đích |
| Browser Home, login, dashboard | Pass |
| Browser tạo Note tiếng Việt và title dài | Pass |
| Browser update Status, Save | Pass |
| Browser xác nhận xóa trong trang, Cancel, Trash | Pass |
| Browser Search title/content | Pass; “meeting” trả 2 Note đúng dữ liệu mẫu |
| WordPress Admin list | Đúng 4 Note của Alice; có Author/Priority/Status/Category |
| Admin sửa Priority → frontend đọc giá trị mới | Pass; đã đưa giá trị mẫu về Normal |
| Viewport 390px và 320px | Không tràn ngang; menu toggle/Escape hoạt động |
| JavaScript console ở workspace được kiểm | Không có error/warn |

Chi tiết automated checks nằm trong automated-results.txt. Integration test tạo và xóa fixture riêng; HTTP/browser tests chuyển fixture vào Trash, không xóa ghi chú mẫu.

## Những lỗi đã phát hiện và sửa

1. **Anonymous query:** author=-1 được WordPress hiểu là loại trừ author 1, không phải “không có user”. Đã thêm post__in=[0] cho khách; test xác nhận query rỗng.
2. **Trash của custom post type:** wp_delete_post(id,false) không bảo đảm chuyển CPT vào Trash. Đã dùng wp_trash_post(); test Trash/Restore xác nhận trạng thái private được giữ.
3. **ABSPATH trong installer:** WordPress đã định nghĩa trước khi nạp config. Đã thêm defined guard; fresh install không còn warning.
4. **REST validation:** kiểm tra title khi tạo mới, giới hạn title/content/category và enum priority/status.
5. **Title dài trên mobile:** bổ sung auto-height cho textarea, giữ wrapping.
6. **Xác nhận xóa:** thay window.confirm bằng xác nhận/Cancel trong trang, đã click kiểm tra lại.

## Giới hạn cần hiểu đúng

- Chưa triển khai và chạy qua Apache tại C:\xampp\htdocs\wp-mini-notes. Công cụ cấp quyền báo thành công nhưng write vẫn EPERM; exec shell gặp setup refresh error.
- Install-Local.ps1 đã kiểm cú pháp; phần PHP installer đã được test thật trong workspace. Toàn bộ PowerShell deployment vào C:\xampp chưa chạy thành công vì giới hạn trên.
- Chưa kiểm thử các browser khác, môi trường hosting, mail gửi đi, tải lớn hay bảo mật bên ngoài phạm vi quyền/form/REST đã nêu.
- Đây là project học local. Không tuyên bố production-ready.
- Bản preview và bản cài chính dùng hai database khác nhau. Cài bằng Install-Local.ps1 tạo dữ liệu mẫu mới; không tự migrate Note bạn thêm ở preview.

## Log môi trường

WordPress core ghi warning khi kiểm tra update vì không kết nối HTTPS được tới WordPress.org. Đây là cảnh báo kết nối của môi trường local; không có PHP error từ theme/plugin trong các luồng đã test. Không tắt xác minh TLS để bỏ qua cảnh báo.
