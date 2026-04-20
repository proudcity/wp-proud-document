<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Proud\Document\ProudDocument;

/**
 * Security regression tests for the stored XSS and related vulnerabilities
 * found in wp-proud-document (issue #2801).
 */
class DocumentSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
        $_POST = [];
        $_GET  = [];
    }

    // -------------------------------------------------------------------------
    // Output escaping — display_document_file_meta_box
    // -------------------------------------------------------------------------

    /**
     * A document URL containing attribute-breaking characters must be escaped
     * so it cannot inject event handlers into the hidden input value.
     */
    public function test_document_url_is_escaped(): void
    {
        $payload = '" onmouseover="alert(1)"';

        Functions\expect('get_post_meta')
            ->with(123, 'document', true)
            ->andReturn($payload);
        Functions\expect('get_post_meta')
            ->with(123, 'document_meta', true)
            ->andReturn('');
        Functions\expect('get_post_meta')
            ->with(123, 'document_filename', true)
            ->andReturn('');
        Functions\when('wp_enqueue_media')->justReturn(null);
        Functions\when('wp_nonce_field')->justReturn(null);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');

        $doc     = new \stdClass();
        $doc->ID = 123;

        $instance = new ProudDocument();

        ob_start();
        $instance->display_document_file_meta_box($doc);
        $html = ob_get_clean();

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString('&quot; onmouseover=&quot;alert(1)&quot;', $html);
    }

    /**
     * A document_meta value containing a single quote must be escaped so it
     * cannot break out of the attribute value (which uses single-quote delimiters).
     */
    public function test_document_meta_is_escaped(): void
    {
        $payload = "' onmouseover='alert(1)'";

        Functions\when('get_post_meta')->alias(function ($id, $key, $single) use ($payload) {
            return $key === 'document_meta' ? $payload : '';
        });
        Functions\when('wp_enqueue_media')->justReturn(null);
        Functions\when('wp_nonce_field')->justReturn(null);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');

        $doc     = new \stdClass();
        $doc->ID = 123;

        $instance = new ProudDocument();

        ob_start();
        $instance->display_document_file_meta_box($doc);
        $html = ob_get_clean();

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString('&#039;', $html);
    }

    /**
     * A document_filename containing a script injection attempt must be escaped
     * so it cannot execute when rendered in the text input value attribute.
     */
    public function test_document_filename_is_escaped(): void
    {
        $payload = '"><img src=x onerror=alert(1)>';

        Functions\when('get_post_meta')->alias(function ($id, $key, $single) use ($payload) {
            return $key === 'document_filename' ? $payload : '';
        });
        Functions\when('wp_enqueue_media')->justReturn(null);
        Functions\when('wp_nonce_field')->justReturn(null);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');

        $doc     = new \stdClass();
        $doc->ID = 123;

        $instance = new ProudDocument();

        ob_start();
        $instance->display_document_file_meta_box($doc);
        $html = ob_get_clean();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;img src=x', $html);
    }

    // -------------------------------------------------------------------------
    // Save sanitization — add_document_fields
    // -------------------------------------------------------------------------

    /**
     * A javascript: URL in upload_src must be sanitized to an empty string
     * before being passed to update_post_meta.
     */
    public function test_save_sanitizes_upload_src(): void
    {
        $_POST['_proud_document_nonce'] = 'valid_nonce';
        $_POST['upload_src']            = 'javascript:alert(1)';
        $_POST['upload_filename']       = 'file.pdf';
        $_POST['upload_meta']           = '{}';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        Functions\expect('update_post_meta')
            ->with(42, 'document', '')
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_filename', \Mockery::any())
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_meta', \Mockery::any())
            ->once();

        $document            = new \stdClass();
        $document->post_type = 'document';

        $instance = new ProudDocument();
        $instance->add_document_fields(42, $document);

        $this->addToAssertionCount(1);
    }

    /**
     * A path traversal attempt in upload_filename must be sanitized before
     * being passed to update_post_meta.
     */
    public function test_save_sanitizes_upload_filename(): void
    {
        $_POST['_proud_document_nonce'] = 'valid_nonce';
        $_POST['upload_src']            = 'https://example.com/file.pdf';
        $_POST['upload_filename']       = '../../etc/passwd';
        $_POST['upload_meta']           = '{}';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        Functions\expect('update_post_meta')
            ->with(42, 'document', \Mockery::any())
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_filename', \Mockery::not('../../etc/passwd'))
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_meta', \Mockery::any())
            ->once();

        $document            = new \stdClass();
        $document->post_type = 'document';

        $instance = new ProudDocument();
        $instance->add_document_fields(42, $document);

        $this->addToAssertionCount(1);
    }

    /**
     * A script tag in upload_meta must have tags stripped before being passed
     * to update_post_meta.
     */
    public function test_save_sanitizes_upload_meta(): void
    {
        $_POST['_proud_document_nonce'] = 'valid_nonce';
        $_POST['upload_src']            = 'https://example.com/file.pdf';
        $_POST['upload_filename']       = 'file.pdf';
        $_POST['upload_meta']           = '<script>alert(1)</script>';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        Functions\expect('update_post_meta')
            ->with(42, 'document', \Mockery::any())
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_filename', \Mockery::any())
            ->once();
        Functions\expect('update_post_meta')
            ->with(42, 'document_meta', \Mockery::not('<script>alert(1)</script>'))
            ->once();

        $document            = new \stdClass();
        $document->post_type = 'document';

        $instance = new ProudDocument();
        $instance->add_document_fields(42, $document);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Nonce check — add_document_fields
    // -------------------------------------------------------------------------

    /**
     * A save request without a nonce must return early without calling
     * update_post_meta.
     */
    public function test_save_returns_early_without_nonce(): void
    {
        // No _proud_document_nonce in $_POST
        $_POST['upload_src']      = 'https://example.com/file.pdf';
        $_POST['upload_filename'] = 'file.pdf';
        $_POST['upload_meta']     = '{}';

        Functions\expect('update_post_meta')->never();

        $document            = new \stdClass();
        $document->post_type = 'document';

        $instance = new ProudDocument();
        $instance->add_document_fields(42, $document);

        $this->addToAssertionCount(1);
    }

    /**
     * A save request where the user lacks edit_post capability must return early
     * without calling update_post_meta.
     */
    public function test_save_returns_early_without_capability(): void
    {
        $_POST['_proud_document_nonce'] = 'valid_nonce';
        $_POST['upload_src']            = 'https://example.com/file.pdf';
        $_POST['upload_filename']       = 'file.pdf';
        $_POST['upload_meta']           = '{}';

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('update_post_meta')->never();

        $document            = new \stdClass();
        $document->post_type = 'document';

        $instance = new ProudDocument();
        $instance->add_document_fields(42, $document);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // AJAX nonce — get_icon
    // -------------------------------------------------------------------------

    /**
     * The get_icon AJAX handler must call check_ajax_referer before doing any
     * work. If the referer check fails (throws), the handler must not proceed.
     */
    public function test_get_icon_checks_nonce(): void
    {
        $_GET['filetype'] = 'pdf';

        Functions\expect('check_ajax_referer')
            ->with('proud_document_icon', '_wpnonce')
            ->once()
            ->andThrow(new \Exception('Bad nonce'));

        $instance = new ProudDocument();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bad nonce');

        $instance->get_icon();
    }
}
