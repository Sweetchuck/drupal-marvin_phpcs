<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_phpcs\Unit\Commands;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Utils;
use Drush\Commands\marvin\CommandsBase;
use bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Webmozart\PathUtil\Path;

class CommandsTestBase extends TestCase {

  protected static function getMarvinRootDir(): string {
    return Path::canonicalize(__DIR__ . '/../../../..');
  }

  protected vfsStream $vfs;

  protected ComposerInfo $composerInfo;

  protected string $marvinRootDir = '';

  protected \Drush\Commands\marvin\CommandsBase $commands;

  protected string $commandsClass = CommandsBase::class;

  protected function setUp(): void {
    parent::setUp();

    $this
      ->setUpVfs()
      ->setUpComposerInfo()
      ->setUpCommands();
  }

  /**
   * @return $this
   */
  protected function setUpVfs() {
    $this->vfs = vfsStream::setup(
      __FUNCTION__,
      NULL,
      [
        'project_01' => [
          'docroot' => [],
          'vendor' => [],
          'composer.json' => '{"name": "drupal/marvin-tester"}',
        ],
      ]
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function setUpComposerInfo() {
    $this->composerInfo = ComposerInfo::create($this->vfs->url() . '/project_01');

    return $this;
  }

  /**
   * @return $this
   */
  protected function setUpCommands() {
    $this->commands = new $this->commandsClass($this->composerInfo);

    return $this;
  }

  protected function getDefaultConfigData(): array {
    return [
      'drush' => [
        'vendor-dir' => $this->vfs->url() . '/vendor',
      ],
    ];
  }

  protected function initContainerLintReporters(ContainerInterface $container) {
    $lintServices = BaseReporter::getServices();
    foreach ($lintServices as $id => $class) {
      Utils::addDefinitionsToContainer(
        [
          $id => [
            'shared' => FALSE,
            'class' => $class,
          ],
        ],
        $container,
      );
    }
  }

}
