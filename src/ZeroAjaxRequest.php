<?php

namespace Drupal\zero_ajax_api;

use Drupal\zero_ajax_api\Exception\ZeroAjaxAPIRuntimeException;

class ZeroAjaxRequest {

  private $params = [];
  public $meta = [];
  private $definition;

  public function __construct($definition) {
    $this->definition = $definition;
  }

  public function setMeta($key, $value): ZeroAjaxRequest {
    $this->meta[$key] = $value;
    return $this;
  }

  public function addMeta($key, $value): ZeroAjaxRequest {
    if (isset($this->meta[$key]) && !is_array($this->meta[$key])) {
      throw new ZeroAjaxAPIRuntimeException('meta.invalid.value', 'The meta key "' . $key . '" exists and is not an array.');
    }
    $this->meta[$key][] = $value;
    return $this;
  }

  public function getParams() {
    return $this->params;
  }

  public function castValue($type, $value) {
    if ($value === NULL) return NULL;
    switch ($type) {
      case 'array':
        if ($value === NULL) return NULL;
        if (is_string($value)) return json_decode($value);
        return $value;
      case 'string':
        return $value . '';
      case 'number':
        $value = $value . '';
        if (strpos($value, '.') === FALSE) {
          return (int)$value;
        } else {
          return (float)$value;
        }
      case 'float':
        return (float)$value;
      case 'int':
        return (int)$value;
    }
    return NULL;
  }

  public function setParams($params, $definitions, &$bag = NULL, $full_key = []): ZeroAjaxRequest {
    if ($bag === NULL) $bag = &$this->params;
    foreach ($definitions as $key => $definition) {
      if (isset($definition['_type'])) {
        if (isset($params[$key])) {
          $bag[$key] = $this->castValue($definition['_type'], $params[$key]);
        } else if ($definition['_required']) {
          throw new ZeroAjaxAPIRuntimeException('invalid.parameter.type', 'The parameter "' . implode('.', [...$full_key, $key]) . '" is required as type "' . $definition['_type'] . '".');
        } else {
          $bag[$key] = $this->castValue($definition['_type'], $definition['_fallback'] ?? NULL);
        }
      } else if (isset($definition['_children'])) {
        $bag[$key] = [];
        $this->setParams($params[$key], $definition['_children'], $bag[$key], [...$full_key, $key]);
      }
    }
    return $this;
  }

  public function throwError(string $message, ?array $info = NULL, ?string $user_error = NULL) {
    throw $this->createError($message, $info, $user_error);
  }

  public function createError(string $message, ?array $info = NULL, ?string $user_error = NULL): ZeroAjaxAPIRuntimeException {
    $error = new ZeroAjaxAPIRuntimeException('plugin.' . $this->definition['id'], $message, 500);
    if ($info !== NULL) {
      foreach ($info as $item) {
        $error->addInfo($item);
      }
    }
    if ($user_error !== NULL) {
      $error->setUserError($user_error);
    }
    return $error;
  }

  public function addInvoke(string $func, ...$params) {
    $this->meta['invoke'][] = [
      'func' => $func,
      'params' => $params,
    ];
    return $this;
  }

  public function setMessage(string $type, string $message) {
    return $this->addInvoke('showMessage', $type, $message);
  }

}
