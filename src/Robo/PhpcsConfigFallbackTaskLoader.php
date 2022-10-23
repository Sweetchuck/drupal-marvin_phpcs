<?php

declare(strict_types = 1);

namespace Drupal\marvin_phpcs\Robo;

use Drupal\marvin_phpcs\Robo\Task\PhpcsConfigFallbackTask;

trait PhpcsConfigFallbackTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_phpcs\Robo\Task\PhpcsConfigFallbackTask
   */
  protected function taskMarvinPhpcsConfigFallback(array $options = []) {
    /** @var \Drupal\marvin_phpcs\Robo\Task\PhpcsConfigFallbackTask $task */
    $task = $this->task(PhpcsConfigFallbackTask::class);
    $task->setOptions($options);

    return $task;
  }

}
