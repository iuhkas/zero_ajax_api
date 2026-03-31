<?php

namespace Drupal\zero_ajax_api\Exception;

use Throwable;

class ZeroAjaxAPIDefinitionException extends ZeroAjaxAPIException {

  public function __construct(string $type, $message = '', $code = 0, ?Throwable $previous = null) {
    parent::__construct('definition.' . $type, $message, $code, $previous);
  }

}
