<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_phpcs;

use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_phpcs\Robo\PhpcsConfigFallbackTaskLoader;
use Drush\Commands\marvin\LintCommandsBase;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Symfony\Component\Filesystem\Path;

class PhpcsCommandsBase extends LintCommandsBase {

  use PhpcsTaskLoader;
  use PhpcsConfigFallbackTaskLoader;
  use GitTaskLoader;

  protected static string $classKeyPrefix = 'marvin.phpcs';

  protected string $customEventNamePrefix = 'marvin:phpcs';

  /**
   * @return \Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcsExtension(string $workingDirectory): TaskInterface {
    $config = $this->getConfig();

    $gitHook = $config->get('marvin.gitHook');
    $phpcsXml = $this->getPhpcsConfigurationFileName($workingDirectory);

    $presetName = $this->getPresetNameByEnvironmentVariant();
    $options = $this->getConfigValue("preset.$presetName", []);
    if ($phpcsXml) {
      unset($options['standards']);
    }

    $options['phpcsExecutable'] = Path::join(
      $this->makeRelativePathToComposerBinDir($workingDirectory),
      'phpcs',
    );
    $options['workingDirectory'] = $workingDirectory;
    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters();

    if ($gitHook === 'pre-commit') {
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
          ->setPaths(MarvinUtils::drupalPhpExtensionPatterns()))
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
          ->setContainer($this->getContainer())
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskPhpcsLintFiles($options)
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
   * @deprecated Use dealerdirect/phpcodesniffer-composer-installer.
   */
  protected function getTaskPhpcsConfigSetInstalledPaths(string $workingDirectory): TaskInterface {
    $composerInfo = $this->getComposerInfo();
    $binDir = $composerInfo['config']['bin-dir'];
    $vendorDir = $composerInfo['config']['vendor-dir'];
    $vendorDirAbs = Path::makeAbsolute($vendorDir, $workingDirectory);
    $cmdPattern = '%s --config-set installed_paths %s';
    $cmdArgs = [
      escapeshellcmd("$binDir/phpcs"),
      escapeshellarg("$vendorDirAbs/drupal/coder/coder_sniffer"),
    ];

    return $this->taskExec(vsprintf($cmdPattern, $cmdArgs));
  }

}
