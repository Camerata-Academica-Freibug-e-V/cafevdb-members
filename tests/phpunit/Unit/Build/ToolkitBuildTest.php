<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Build;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use OCA\RotDrop\DevScripts\PhpToTypeScript\PhpToTypeScript;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Service\AppInfoService;

/** Regression tests for the Composer toolkit build and its generated modules. */
#[Attributes\CoversNothing]
class ToolkitBuildTest extends TestCase
{
  private string $appDir;
  private ?string $temporaryDir = null;

  /** {@inheritdoc} */
  protected function setUp(): void
  {
    $this->appDir = dirname(__DIR__, 4);
    require_once $this->appDir . '/vendor/autoload.php';
    require_once $this->appDir . '/vendor-wrapped/autoload.php';
    require_once $this->appDir . '/vendor-bin/php-to-typescript/vendor/autoload.php';
  }

  /**
   * The scoped toolkit must find the consuming app, not its package folder.
   *
   * @return void
   */
  public function testToolkitAppFolder(): void
  {
    self::assertSame($this->appDir, AppInfoService::getAppFolderPath());
    self::assertSame('cafevdbmembers', AppInfoService::getAppInfoAppName());
  }

  /**
   * An unchanged wrapped tree must not resolve or scope dependencies again.
   *
   * @return void
   */
  public function testWrappedBuildIsUpToDate(): void
  {
    exec('make --no-print-directory -C ' . escapeshellarg($this->appDir) . ' --question vendor-wrapped 2>&1', $output, $status);
    self::assertSame(0, $status, implode(PHP_EOL, $output));
  }

  /**
   * Release archives need both runtime trees, but not the unscoped build input.
   *
   * @return void
   */
  public function testReleaseDependencyTrees(): void
  {
    $rule = 'print-release-files:;@echo $(APPSTORE_FILES)';
    exec(
      'make --no-print-directory -s -C ' . escapeshellarg($this->appDir)
        . ' --eval ' . escapeshellarg($rule) . ' print-release-files 2>&1',
      $output,
      $status,
    );
    self::assertSame(0, $status, implode(PHP_EOL, $output));
    $files = preg_split('/\s+/', implode(' ', $output));
    self::assertContains('vendor', $files);
    self::assertContains('vendor-wrapped', $files);
    self::assertNotContains('build', $files);
    self::assertNotContains('build/vendor-wrapped', $files);
    self::assertNotContains('l10n-sources', $files);
  }

  /**
   * Toolkit strings must remain visible to the checkout-only translation scan.
   *
   * @return void
   */
  public function testToolkitTranslationCatalogue(): void
  {
    $this->temporaryDir = sys_get_temp_dir() . '/cafevdbmembers-toolkit-' . bin2hex(random_bytes(8));
    mkdir($this->temporaryDir);
    $catalogue = $this->temporaryDir . '/toolkit-strings.php';
    exec(
      'make --no-print-directory -s -C ' . escapeshellarg($this->appDir)
        . ' toolkit-l10n ' . escapeshellarg('APP_TOOLKIT_L10N_DEST=' . $catalogue) . ' 2>&1',
      $output,
      $status,
    );
    self::assertSame(0, $status, implode(PHP_EOL, $output));
    $translationCalls = static function (string $path): array {
      $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      $calls = array_filter(
        $lines,
        static fn (string $line): bool => str_starts_with($line, '$l->t('),
      );
      sort($calls);
      return $calls;
    };
    self::assertSame(
      $translationCalls($this->appDir . '/l10n-sources/toolkit-strings.php'),
      $translationCalls($catalogue),
    );
  }

  /**
   * Generate a real scoped toolkit enum at the path used by the frontend.
   *
   * @return void
   */
  public function testToolkitModulePath(): void
  {
    $this->temporaryDir = sys_get_temp_dir() . '/cafevdbmembers-toolkit-' . bin2hex(random_bytes(8));
    mkdir($this->temporaryDir);
    mkdir($this->temporaryDir . '/input');
    mkdir($this->temporaryDir . '/output');
    copy(
      $this->appDir . '/vendor-wrapped/rotdrop/nextcloud-app-toolkit/src/Doctrine/ORM/EnumOrderByOptions.php',
      $this->temporaryDir . '/input/EnumOrderByOptions.php',
    );

    $command = new PhpToTypeScript(
      devScriptsFolder: $this->appDir . '/dev-scripts',
      collectors: [EnumCollector::class],
    );
    $input = new ArrayInput([
      '--source-dir' => [$this->temporaryDir . '/input'],
      '--output-prefix' => $this->temporaryDir . '/output',
      '--ns-prefix' => 'OCA\\CAFeVDBMembers',
      '--scoped-ns-prefix' => 'Wrapped',
      '--as-modules' => true,
      '--quiet' => true,
    ], $command->getDefinition());

    self::assertSame(0, $command->run($input, new ConsoleOutput(OutputInterface::VERBOSITY_QUIET)));
    $modulePath = 'php-modules/Wrapped/OCA/RotDrop/Toolkit/Doctrine/ORM.ts';
    self::assertFileExists($this->temporaryDir . '/output/' . $modulePath);
    self::assertStringContainsString(
      'export enum EnumOrderByOptions',
      file_get_contents($this->temporaryDir . '/output/' . $modulePath),
    );
    self::assertStringContainsString(
      'build/ts-types/' . $modulePath,
      file_get_contents($this->appDir . '/src/toolkit/services/entity-repository.ts'),
    );
  }

  /** {@inheritdoc} */
  protected function tearDown(): void
  {
    if ($this->temporaryDir === null) {
      return;
    }
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->temporaryDir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
      if ($file->isDir()) {
        rmdir($file->getPathname());
      } else {
        unlink($file->getPathname());
      }
    }
    rmdir($this->temporaryDir);
  }
}
