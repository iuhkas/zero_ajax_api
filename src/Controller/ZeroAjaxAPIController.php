<?php

namespace Drupal\zero_ajax_api\Controller;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\zero_ajax_api\Ajax\ZeroAjaxAPICommand;
use Drupal\zero_ajax_api\Exception\ZeroAjaxAPIException;
use Drupal\zero_ajax_api\ZeroAjaxRequest;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ZeroAjaxAPIController extends ControllerBase {

  public function serve(string $id) {
    try {
      /** @var \Drupal\zero_ajax_api\ZeroAjaxPluginManager $manager */
      $manager = Drupal::service('plugin.manager.zero.ajax');

      if (empty($manager->getDefinitions()[$id])) {
        return $this->error('The plugin ID "' . $id . '" is unknown.');
      } else {
        $definition = $manager->getDefinitions()[$id];
        /** @var \Drupal\zero_ajax_api\ZeroAjaxInterface $plugin */
        $plugin = $manager->createInstance($definition['id'], $definition);
        $request = new ZeroAjaxRequest($definition);
        $merge = array_merge(Drupal::request()->request->all(), Drupal::request()->query->all());
        $request->setParams($merge, $plugin->getParamDefinitions());
        $response = $plugin->response($request);
        if ($response instanceof ZeroAjaxAPIException) {
          return $this->error($response->getMessage(), $response->getCode(), ['type' => $response->getType(), 'info' => $response->getMore()], $response->invoke());
        }
        return $this->response($response, $request->meta);
      }
    } catch (Exception $exception) {
      if ($exception instanceof ZeroAjaxAPIException) {
        return $this->error($exception->getMessage(), $exception->getCode(), ['type' => $exception->getType(), 'info' => $exception->getMore()], $exception->invoke());
      } else {
        return $this->error($exception->getMessage(), $exception->getCode());
      }
    }
  }

  public function error(string $message, int $code = 500, ?array $additionals = NULL, $invoke = []): JsonResponse {
    $value = [
      'message' => $message,
    ];
    if ($additionals) {
      $value['details'] = $additionals;
    }
    return $this->response($value, [
      'error' => TRUE,
      'code' => $code,
      'invoke' => $invoke,
    ]);
  }

  protected function response($data = [], $meta = [], int $code = 200): JsonResponse {
    if (!isset($meta['error'])) $meta['error'] = FALSE;

    $format = Drupal::request()->get('_format', 'json');
    if ($format === 'json') {
      $response = CacheableJsonResponse::create(['data' => $data, 'meta' => $meta], $code);
    } else if ($format === 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new ZeroAjaxAPICommand($data, $meta, $code));
    } else {
      throw new BadRequestHttpException('Unknown format "' . $format . '"');
    }

    return $response;
  }

}
