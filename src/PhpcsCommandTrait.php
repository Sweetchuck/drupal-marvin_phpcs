<?php

declare(strict_types=1);

namespace Drupal\marvin_phpcs;

use Drupal\marvin\LintCommandTrait;
use Drupal\marvin_phpcs\Robo\PhpcsConfigFallbackTaskLoader;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Symfony\Component\Filesystem\Path;

/**
 * @property \Drupal\marvin\Utils $utils
 * @property \Drush\Config\DrushConfig $drushConfig
 */
trait PhpcsCommandTrait {

  use LintCommandTrait;
  use PhpcsTaskLoader;
  use PhpcsConfigFallbackTaskLoader;
  use GitTaskLoader;

  /**
   * @param ?\Robo\Symfony\ConsoleIO $io
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  abstract protected function collectionBuilder($io = NULL);

  /**
   * @return \Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcsExtension(
    string $workingDirectory,
    string $composerBinDir,
    string $reportsDir,
    ?string $gitHookName,
  ): TaskInterface {
    $phpcsXml = $this->getPhpcsConfigurationFileName($workingDirectory);

    $conditionContext = $this->utils->getConditionContext($this->drushConfig);

    $presets = $this->drushConfig->get('marvin.phpcs.presets');
    $presetName = $this->utils->getFirstByConditions($presets, $conditionContext);
    $preset = $presets[$presetName] ?? ['options' => []];

    $options = $preset['options'] ?? [];
    if ($phpcsXml) {
      unset($options['standards']);
    }

    $backToBinDir = $this->utils->makeRelativePathToComposerBinDir(
      $workingDirectory,
      $this->utils->getProjectRootDir($this->drushConfig),
      $composerBinDir,
    );

    $options['phpcsExecutable'] = Path::join($backToBinDir, 'phpcs');
    $options['workingDirectory'] = $workingDirectory;
    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters([
      'reportsDir' => $reportsDir,
    ]);

    if ($gitHookName === 'pre-commit') {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskPhpcsParseXml()
          ->setWorkingDirectory($workingDirectory)
          ->setFailOnXmlFileNotExists(FALSE)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskGitListStagedFiles()
          ->setDiffFilter(['d' => FALSE])
          ->setWorkingDirectory($workingDirectory)
          ->setPaths($this->utils->drupalPhpExtensionPatterns()))
        ->addTask($this
          ->taskGitReadStagedFiles()
          ->setWorkingDirectory($workingDirectory)
          ->setCommandOnly(TRUE)
          ->deferTaskConfiguration('setPaths', 'fileNames'))
        ->addTask($this
          ->taskPhpcsLintInput($options)
          ->deferTaskConfiguration('setFiles', 'files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    if (!$phpcsXml) {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskMarvinPhpcsConfigFallback()
          ->setUtils($this->utils)
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskPhpcsLintFiles($options)
          ->setExtensions($this->getPhpcsExtensions())
          ->deferTaskConfiguration('setFiles', 'phpcsXml.files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    return $this->taskPhpcsLintFiles($options);
  }

  protected function getPhpcsConfigurationFileName(string $directory): string {
    $directory = $directory ?: '.';
    $candidates = [
      'phpcs.xml',
      'phpcs.xml.dist',
    ];

    foreach ($candidates as $candidate) {
      $fileName = Path::join($directory, $candidate);
      if (file_exists($fileName)) {
        return $fileName;
      }
    }

    return '';
  }

  /**
   * @return array<string>
   */
  protected function getPhpcsExtensions(): array {
    return $this->utils->prefixSuffixItems(
      array_keys($this->utils->drupalPhpExtensions, TRUE),
      '',
      '/PHP',
    );
  }

}
