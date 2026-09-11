<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/zipupload_lib.php';

final class ZipUploadTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ytds_zipupload_' . bin2hex(random_bytes(5));
        mkdir($this->tempRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->tempRoot)) {
            zip_upload_remove_directory($this->tempRoot);
        }
    }

    public function testAcceptsRootIndexHtmlAndIndexHtm(): void
    {
        $htmlPlan = $this->analyzeZip(['index.html' => '<html></html>']);
        $this->assertSame('direct', $htmlPlan['mode']);

        $htmPlan = $this->analyzeZip(['index.htm' => '<html></html>']);
        $this->assertSame('direct', $htmPlan['mode']);
    }

    public function testSingleInnerDirectoryWithIndexIsFlattened(): void
    {
        $zip = $this->openZip([
            'archive-page/index.html' => '<link rel="stylesheet" href="assets/style.css">',
            'archive-page/assets/style.css' => 'body { color: #111; }',
        ]);
        $plan = zip_upload_analyze_archive($zip);

        $this->assertSame('single_dir', $plan['mode']);
        $this->assertSame('archive-page/', $plan['prefix']);

        $target = $this->tempRoot . DIRECTORY_SEPARATOR . 'inner-safe-page';
        mkdir($target, 0755, true);
        zip_upload_extract_plan($zip, $plan, $target);
        $zip->close();

        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'index.html');
        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'style.css');
        $this->assertDirectoryDoesNotExist($target . DIRECTORY_SEPARATOR . 'archive-page');
    }

    public function testRejectsRootHtmlArchiveWithoutIndex(): void
    {
        $plan = $this->analyzeZip([
            'product.html' => '<link rel="stylesheet" href="assets/style.css">',
            'assets/style.css' => 'body { color: #111; }',
        ]);

        $this->assertSame(
            'ZIP invalid: no index.php, index.html, or index.htm found at root level',
            $plan['error'] ?? null
        );
    }

    public function testRejectsArchiveWithoutEntryPage(): void
    {
        $plan = $this->analyzeZip(['assets/app.js' => 'console.log("missing page");']);

        $this->assertSame(
            'ZIP invalid: the folder "assets" does not contain index.php, index.html, or index.htm',
            $plan['error'] ?? null
        );
    }

    public function testExtractionReportsWriteFailure(): void
    {
        $zip = $this->openZip([
            'index.html' => '<html></html>',
            'assets/' => '',
            'assets' => 'file where a directory already exists',
        ]);
        $plan = zip_upload_analyze_archive($zip);
        $target = $this->tempRoot . DIRECTORY_SEPARATOR . 'write-failure';
        mkdir($target, 0755, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write file "assets"');

        try {
            zip_upload_extract_plan($zip, $plan, $target);
        } finally {
            $zip->close();
        }
    }

    public function testUnsafeEntryNamesAreIgnored(): void
    {
        $entries = [
            ['index' => 0, 'name' => 'index.html', 'isDir' => false],
            ['index' => 1, 'name' => '../escape.txt', 'isDir' => false],
            ['index' => 2, 'name' => '/absolute.txt', 'isDir' => false],
        ];

        $this->assertSame('index.html', zip_upload_normalize_entry_name('index.html'));
        $this->assertNull(zip_upload_normalize_entry_name('../escape.txt'));
        $this->assertNull(zip_upload_normalize_entry_name('/absolute.txt'));

        $plan = zip_upload_analyze_entries(array_values(array_filter(
            $entries,
            static fn(array $entry): bool => zip_upload_normalize_entry_name($entry['name']) !== null
        )));
        $this->assertSame('direct', $plan['mode']);
    }

    /**
     * @param array<string, string> $files
     * @return array<string, mixed>
     */
    private function analyzeZip(array $files): array
    {
        $zip = $this->openZip($files);
        $plan = zip_upload_analyze_archive($zip);
        $zip->close();
        return $plan;
    }

    /** @param array<string, string> $files */
    private function openZip(array $files): ZipArchive
    {
        $path = $this->tempRoot . DIRECTORY_SEPARATOR . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $content) {
            $this->assertTrue($zip->addFromString($name, $content));
        }
        $zip->close();

        $opened = new ZipArchive();
        $this->assertTrue($opened->open($path));
        return $opened;
    }
}
