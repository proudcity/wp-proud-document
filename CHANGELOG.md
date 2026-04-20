## 2026-04-20

- Fixed stored XSS in document admin metabox — added `esc_attr()` to three `get_post_meta()` outputs in `display_document_file_meta_box()`
- Added nonce verification and `current_user_can()` check to `add_document_fields()` save handler
- Added `esc_url_raw()`, `sanitize_file_name()`, and `sanitize_text_field()` sanitization to POST data on save
- Added `check_ajax_referer()` and `current_user_can()` to `get_icon()` AJAX handler
- Added PHPUnit test infrastructure (`composer.json`, `phpunit.xml`, `tests/`)
- Added 9 security regression tests

References: https://github.com/proudcity/wp-proudcity/issues/2801
