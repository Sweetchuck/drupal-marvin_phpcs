<?php

declare(strict_types = 1);

namespace Drupal\marvin_phpcs\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin\Utils as MarvinUtils;
use Psr\Container\ContainerInterface;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Walker\FileSystemExistsWalker;

class PhpcsConfigFallbackTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  protected string $taskName = 'Marvin - PHP_CodeSniffer config fallback';

  public function setContainer(ContainerInterface $container): static {
    parent::setContainer($container);

    $pairs = [
      'marvin.file_system_exists_walker' => [
        'shared' => FALSE,
        'class' => FileSystemExistsWalker::class,
      ],
    ];
    MarvinUtils::addDefinitionsToContainer($pairs, $container);

    return $this;
  }

  protected string $workingDirectory = '';

  public function getWorkingDirectory(): string {
    return $this->workingDirectory;
  }

  public function setWorkingDirectory(string $value): static {
    $this->workingDirectory = $value;

    return $this;
  }

  /**
   * @phpstan-param array<string, mixed> $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('workingDirectory', $options)) {
      $this->setWorkingDirectory($options['workingDirectory']);
    }

    return $this;
  }

  protected function runAction(): static {
    $state = $this->getState();

    $assetNamePrefix = $this->getAssetNamePrefix();
    if (isset($state["{$assetNamePrefix}files"]) || isset($state["{$assetNamePrefix}exclude-patterns"])) {
      $this->printTaskDebug('The PHPCS config is already available from state data.');

      return $this;
    }

    $workingDirectory = $this->getWorkingDirectory() ?: '.';
    $this->assets = $this->getFilePathsByProjectType($workingDirectory);

    return $this;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  protected function getFilePathsByProjectType(string $workingDirectory): array {
    // @todo Get file paths from the drush.yml configuration.
    $composerInfo = ComposerInfo::create($workingDirectory);
    $filePaths = [
      'files' => [],
      'exclude-patterns' => [],
    ];

    // @todo Refactor, because this is conceptually wrong.
    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        $drupalRootDir = $composerInfo->getDrupalRootDir();
        $filePaths['files']['drush/custom/'] = TRUE;
        $filePaths['files']["$drupalRootDir/modules/custom/"] = TRUE;
        $filePaths['files']["$drupalRootDir/profiles/custom/"] = TRUE;
        $filePaths['files']["$drupalRootDir/themes/custom/"] = TRUE;
        $filePaths['files']["tests/behat/subcontexts/"] = TRUE;
        // @todo Add exclude-patterns - node_modules, vendor.
        break;

      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        $filePaths['files'] += array_fill_keys(
          MarvinUtils::getDirectDescendantDrupalPhpFiles($workingDirectory),
          TRUE
        );
        break;

      case 'drupal-profile':
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        $filePaths['files']['modules/custom/'] = TRUE;
        $filePaths['files']['themes/custom/'] = TRUE;
        $filePaths['files'] += array_fill_keys(
          MarvinUtils::getDirectDescendantDrupalPhpFiles($workingDirectory),
          TRUE
        );
        break;
    }

    $walker = $this->getContainer()->get('marvin.file_system_exists_walker');
    $walker->setBaseDir($workingDirectory);
    array_walk($filePaths['files'], $walker);

    return $filePaths;
  }

}
