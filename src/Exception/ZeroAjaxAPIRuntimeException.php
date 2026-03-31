<?php

namespace Drupal\zero_ajax_api\Exception;

use Throwable;

class ZeroAjaxAPIRuntimeException extends ZeroAjaxAPIException {

  private $usererror = NULL;

  public function __construct(string $type, $message = '', $code = 0, Throwable|null $previous = null) {
    parent::__construct('runtime.' . $type, $message, $code, $previous);
  }

  public function setUserError(string $message) {
    $this->usererror = $message;
  }

  public function getUserError() {
    return $this->usererror;
  }

  public function invoke(): array {
    $invoke = parent::invoke();
    if ($this->usererror !== NULL) {
      $invoke[] = [
        'func' => 'showMessage',
        'params' => ['error', $this->usererror],
      ];
    }
    return $invoke;
  }

}
