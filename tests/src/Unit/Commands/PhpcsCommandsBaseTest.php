<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_phpcs\Unit\Commands;

use Drush\Commands\marvin_phpcs\PhpcsCommandsBase;
use org\bovigo\vfs\vfsStream;
use Robo\Config\Config;

/**
 * @group marvin
 * @group marvin_phpcs
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin_phpcs\PhpcsCommandsBase
 */
class PhpcsCommandsBaseTest extends CommandsTestBase {

  public function testGetClassKey(): void {
    $commands = new PhpcsCommandsBase($this->composerInfo);

    $methodName = 'getClassKey';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin.phpcs.a', $method->invokeArgs($commands, ['a']));
  }

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'phpcs' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new PhpcsCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $commands = new PhpcsCommandsBase($this->composerInfo);
    $methodName = 'getCustomEventNamePrefix';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:phpcs', $method->invokeArgs($commands, []));
  }

  /**
   * @phpstan-return array<string, array<mixed>>
   */
  public function casesGetPhpcsConfigurationFileName(): array {
    return [
      'empty' => [
        '',
        [],
        '.',
      ],
      'only phpcs.xml.dist' => [
        'phpcs.xml.dist',
        [
          'phpcs.xml.dist' => '',
        ],
        '.',
      ],
      'only phpcs.xml' => [
        'phpcs.xml',
        [
          'phpcs.xml' => '',
        ],
        '.',
      ],
      'both' => [
        'phpcs.xml',
        [
          'phpcs.xml.dist' => '',
          'phpcs.xml' => '',
        ],
        '.',
      ],
    ];
  }

  /**
   * @phpstan-param array<mixed> $vfsStructure
   *
   * @dataProvider casesGetPhpcsConfigurationFileName
   */
  public function testGetPhpcsConfigurationFileName(string $expected, array $vfsStructure, string $directory): void {
    $baseDir = __FUNCTION__;
    $vfsBase = vfsStream::create([$baseDir => $vfsStructure], $this->vfs);
    $this->vfs->addChild($vfsBase);

    $commands = new PhpcsCommandsBase($this->composerInfo);

    $methodName = 'getPhpcsConfigurationFileName';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    if ($expected) {
      $expected = $this->vfs->url() . "/$baseDir/$expected";
    }

    $directory = $this->vfs->url() . "/$baseDir/$directory";

    static::assertSame($expected, $method->invokeArgs($commands, [$directory]));
  }

}
