<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_phpcs\Unit\Robo\Task;

use org\bovigo\vfs\vfsStream;
use Robo\State\Data as RoboStateData;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin_phpcs\Robo\Task\PhpcsConfigFallbackTask
 * @covers \Drupal\marvin_phpcs\Robo\PhpcsConfigFallbackTaskLoader
 */
class PhpcsConfigFallbackTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, array<mixed>>
   */
  public function casesRunSuccessCollect(): array {
    return [
      'type.library' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - PHP_CodeSniffer config fallback] ',
            '',
          ]),
          'assets' => [
            'files' => [],
            'exclude-patterns' => [],
          ],
        ],
        [
          'composer.json' => json_encode([
            'name' => 'a/b',
            'type' => 'library',
          ]),
        ],
      ],
      'type.drupal-project' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - PHP_CodeSniffer config fallback] ',
            '',
          ]),
          'assets' => [
            'files' => [
              'drush/custom/' => TRUE,
              'docroot/modules/custom/' => TRUE,
              'docroot/profiles/custom/' => FALSE,
              'docroot/themes/custom/' => TRUE,
              'tests/behat/subcontexts/' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'composer.json' => json_encode([
            'name' => 'drupal/project_01',
            'type' => 'drupal-project',
            'extra' => [
              'installer-paths' => [
                'docroot/core' => ['type:drupal-core'],
              ],
            ],
          ]),
          'drush' => [
            'custom' => [
              'foo' => [],
            ],
          ],
          'docroot' => [
            'modules' => [
              'custom' => [],
            ],
            'themes' => [
              'custom' => [],
            ],
          ],
          'tests' => [
            'behat' => [
              'subcontexts' => [],
            ],
          ],
        ],
      ],
      'type.drupal-module' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - PHP_CodeSniffer config fallback] ',
            '',
          ]),
          'assets' => [
            'files' => [
              'Commands/' => FALSE,
              'src/' => TRUE,
              'tests/' => FALSE,
              'dummy_m1.module' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'src' => [],
          'composer.json' => json_encode([
            'name' => 'drupal/dummy_m1',
            'type' => 'drupal-module',
          ]),
          'dummy_m1.module' => '<?php',
        ],
      ],
      'type.dru pal-profile' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - PHP_CodeSniffer config fallback] ',
            '',
          ]),
          'assets' => [
            'files' => [
              'Commands/' => FALSE,
              'src/' => TRUE,
              'tests/' => FALSE,
              'modules/custom/' => FALSE,
              'themes/custom/' => FALSE,
              'dummy_m1.profile' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'src' => [],
          'composer.json' => json_encode([
            'name' => 'drupal/dummy_p1',
            'type' => 'drupal-profile',
          ]),
          'dummy_m1.profile' => '<?php',
        ],
      ],
    ];
  }

  /**
   * @phpstan-param array<mixed> $expected
   * @phpstan-param array<mixed> $vfsStructure
   * @phpstan-param array<string, mixed> $options
   *
   * @dataProvider casesRunSuccessCollect
   */
  public function testRunSuccessCollect(array $expected, array $vfsStructure, array $options = []): void {
    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['workingDirectory'] = $vfs->url();

    $result = $this
      ->taskBuilder
      ->taskMarvinPhpcsConfigFallback($options)
      ->setContainer($this->container)
      ->run();

    if (array_key_exists('exitCode', $expected)) {
      static::assertSame(
        $expected['exitCode'],
        $result->getExitCode(),
        'exitCode',
      );
    }

    /** @var \Drupal\Tests\marvin_phpcs\Helper\DummyOutput $stdOutput */
    $stdOutput = $this->container->get('output');
    if (array_key_exists('stdOutput', $expected)) {
      static::assertSame(
        $expected['stdOutput'],
        $stdOutput->output,
        'stdOutput',
      );
    }

    if (array_key_exists('stdError', $expected)) {
      static::assertSame(
        $expected['stdError'],
        $stdOutput->getErrorOutput()->output,
        'stdError',
      );
    }

    if (array_key_exists('assets', $expected)) {
      foreach ($expected['assets'] as $key => $expectedValue) {
        static::assertSame(
          $expectedValue,
          $result[$key],
          "result.assets.$key"
        );
      }
    }
  }

  public function testRunSuccessSkip(): void {
    $expected = [
      'exitCode' => 0,
      'stdOutput' => '',
      'stdError' => implode("\n", []),
    ];

    $stateData = [
      'my.files' => [
        'a.php' => TRUE,
      ],
      'my.exclude-patterns' => [
        'b.php' => TRUE,
      ],
    ];

    $state = new RoboStateData('', $stateData);

    $task = $this->taskBuilder->taskMarvinPhpcsConfigFallback();
    $task->original()->setState($state);

    $result = $task
      ->setContainer($this->container)
      ->setAssetNamePrefix('my.')
      ->run();

    static::assertSame($expected['exitCode'], $result->getExitCode());
    static::assertSame($stateData['my.files'], $state['my.files']);
    static::assertSame($stateData['my.exclude-patterns'], $state['my.exclude-patterns']);

    /** @var \Drupal\Tests\marvin_phpcs\Helper\DummyOutput $stdOutput */
    $stdOutput = $this->container->get('output');
    if (array_key_exists('stdOutput', $expected)) {
      static::assertSame(
        $expected['stdOutput'],
        $stdOutput->output,
        'stdOutput',
      );
    }

    if (array_key_exists('stdError', $expected)) {
      static::assertSame(
        $expected['stdError'],
        $stdOutput->getErrorOutput()->output,
        'stdError',
      );
    }
  }

}
