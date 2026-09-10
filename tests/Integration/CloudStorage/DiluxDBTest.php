<?php
namespace Tests\Integration\CloudStorage;

use Tests\Integration\IntegrationTestCase;
use OffloadDlxPlus\OffloadDlxPlusDB;

/**
 * Integration tests for OffloadDlxPlusDB.
 *
 * Exercises CRUD against the wp_offload_dlx_plus_files custom table on a real
 * MySQL database (provided by wp-env's tests environment).
 */
class OffloadDlxPlusDBTest extends IntegrationTestCase {

    public function test_create_files_table_creates_table_with_expected_structure(): void {
        $this->assertTrue(OffloadDlxPlusDB::table_exists());

        global $wpdb;
        $table = OffloadDlxPlusDB::get_table_name();
        $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`");

        $expected_columns = [
            'file', 'size', 'transferred', 'synced', 'deleted',
            'errors', 'error_message', 'upload_id', 'created_at', 'updated_at',
        ];
        foreach ($expected_columns as $col) {
            $this->assertContains($col, $columns, "Column '{$col}' missing from table");
        }
    }

    public function test_add_file_inserts_with_pending_status(): void {
        $result = OffloadDlxPlusDB::add_file('/2024/01/photo.jpg', 2048576);
        $this->assertTrue($result);

        $stats = OffloadDlxPlusDB::get_stats();
        $this->assertSame(1, (int) $stats['total_files']);
        $this->assertSame(0, (int) $stats['synced_files']);

        $pending = OffloadDlxPlusDB::get_pending_files();
        $this->assertCount(1, $pending);
        $this->assertSame('/2024/01/photo.jpg', $pending[0]['file']);
    }

    public function test_add_files_batch_inserts_multiple_files(): void {
        $files = [
            ['path' => '/2024/01/img1.jpg', 'size' => 1000],
            ['path' => '/2024/01/img2.jpg', 'size' => 2000],
            ['path' => '/2024/01/img3.jpg', 'size' => 3000],
        ];

        $result = OffloadDlxPlusDB::add_files_batch($files);
        $this->assertTrue($result);

        $stats = OffloadDlxPlusDB::get_stats();
        $this->assertSame(3, (int) $stats['total_files']);
        $this->assertSame(6000, (int) $stats['total_size']);
    }

    public function test_mark_synced_updates_status(): void {
        OffloadDlxPlusDB::add_file('/2024/01/photo.jpg', 1024);

        $result = OffloadDlxPlusDB::mark_synced('/2024/01/photo.jpg');
        $this->assertNotFalse($result);

        $stats = OffloadDlxPlusDB::get_stats();
        $this->assertSame(1, (int) $stats['synced_files']);
        $this->assertSame(0, (int) $stats['pending_files']);
    }

    public function test_get_stats_counts_by_status(): void {
        $this->addTestFiles(5, 1000);

        OffloadDlxPlusDB::mark_synced('/2024/01/test-file-1.jpg');
        OffloadDlxPlusDB::mark_synced('/2024/01/test-file-2.jpg');

        OffloadDlxPlusDB::increment_error('/2024/01/test-file-3.jpg', 'Timeout');
        OffloadDlxPlusDB::increment_error('/2024/01/test-file-3.jpg', 'Timeout');
        OffloadDlxPlusDB::increment_error('/2024/01/test-file-3.jpg', 'Timeout');

        $stats = OffloadDlxPlusDB::get_stats();
        $this->assertSame(5, (int) $stats['total_files']);
        $this->assertSame(2, (int) $stats['synced_files']);
        $this->assertSame(1, (int) $stats['failed_files']);
        $this->assertSame(3, (int) $stats['pending_files']);
    }

    public function test_get_pending_files_returns_only_pending(): void {
        $this->addTestFiles(3, 1024);
        OffloadDlxPlusDB::mark_synced('/2024/01/test-file-1.jpg');

        $pending = OffloadDlxPlusDB::get_pending_files();
        $this->assertCount(2, $pending);

        $paths = array_map(fn($f) => $f['file'], $pending);
        $this->assertNotContains('/2024/01/test-file-1.jpg', $paths);
    }

    public function test_get_pending_files_respects_limit(): void {
        $this->addTestFiles(10, 1024);

        $pending = OffloadDlxPlusDB::get_pending_files(3);
        $this->assertCount(3, $pending);
    }

    public function test_get_failed_files_returns_only_failed(): void {
        $this->addTestFiles(3, 1024);

        OffloadDlxPlusDB::mark_synced('/2024/01/test-file-1.jpg');
        OffloadDlxPlusDB::increment_error('/2024/01/test-file-2.jpg', 'Error 1');

        $failed = OffloadDlxPlusDB::get_failed_files();
        $this->assertCount(2, $failed);
    }

    public function test_reset_failed_files_to_pending(): void {
        $this->addTestFiles(2, 1024);

        OffloadDlxPlusDB::increment_error('/2024/01/test-file-1.jpg', 'Timeout');
        OffloadDlxPlusDB::increment_error('/2024/01/test-file-1.jpg', 'Timeout');
        OffloadDlxPlusDB::increment_error('/2024/01/test-file-1.jpg', 'Timeout');

        $result = OffloadDlxPlusDB::reset_failed_files_to_pending();
        $this->assertTrue($result);

        $pending = OffloadDlxPlusDB::get_pending_files();
        $this->assertCount(2, $pending);

        global $wpdb;
        $table = OffloadDlxPlusDB::get_table_name();
        $max_errors = (int) $wpdb->get_var("SELECT MAX(errors) FROM `{$table}`");
        $this->assertSame(0, $max_errors);
    }

    public function test_clear_table_removes_all_records(): void {
        $this->addTestFiles(5, 1024);
        $this->assertSame(5, $this->getTableRowCount());

        $result = OffloadDlxPlusDB::clear_table();
        $this->assertTrue($result);

        $this->assertSame(0, $this->getTableRowCount());
    }

    public function test_increment_error_tracks_error_count_and_message(): void {
        OffloadDlxPlusDB::add_file('/2024/01/photo.jpg', 1024);

        OffloadDlxPlusDB::increment_error('/2024/01/photo.jpg', 'Connection timeout');
        OffloadDlxPlusDB::increment_error('/2024/01/photo.jpg', 'Server error 500');

        global $wpdb;
        $table = OffloadDlxPlusDB::get_table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT errors, error_message FROM `{$table}` WHERE file = %s", '/2024/01/photo.jpg')
        );

        $this->assertSame(2, (int) $row->errors);
        $this->assertSame('Server error 500', $row->error_message);
    }

    public function test_add_cloud_only_file_inserts_with_synced_and_deleted(): void {
        $result = OffloadDlxPlusDB::add_cloud_only_file('/2024/01/cloud-only.jpg', 5000);
        $this->assertTrue($result);

        $deleted_stats = OffloadDlxPlusDB::get_deleted_stats();
        $this->assertSame(1, (int) $deleted_stats['files']);
        $this->assertSame(5000, (int) $deleted_stats['size']);

        $pending = OffloadDlxPlusDB::get_pending_files();
        $this->assertCount(0, $pending);
    }
}
