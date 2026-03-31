<?php

namespace Drupal\zero_ajax_api\Exception;

use Exception;
use Throwable;

class ZeroAjaxAPIException extends Exception {

  protected $type;
  protected $more = [];

  public function __construct(string $type, $message = '', $code = 0, Throwable|null $previous = null) {
    parent::__construct($message, $code, $previous);
    $this->type = 'error.' . $type;
  }

  public function getType(): string {
    return $this->type;
  }

  public function addInfo(string $message) {
    $this->more[] = $message;
    return $this;
  }

  public function getMore(): array {
    return $this->more;
  }

  public function invoke(): array {
    return [];
  }

}
