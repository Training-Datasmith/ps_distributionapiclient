<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
namespace Presta_Shop\Module\Distribution_Api_Client\Middleware;

use Symfony\Component\Http_Client\Exception\Json_Exception;
use Symfony\Contracts\Http_Client\Response_Interface;
/**
 * Simple DTO containing the response data to allow serializing it into cache.
 */
class Cached_Response implements Response_Interface
{
    private readonly int $status_code;
    /**
     * @var string[][]
     */
    private readonly array $headers;
    private readonly string $content;
    /**
     * @var mixed[]|array|null
     */
    private ?array $json_data = null;
    /**
     * @var array<string, mixed>
     */
    private array $info;
    public function __construct(Response_Interface $response)
    {
        $info = $response->get_info();
        if (is_array($info)) {
            $this->info = ['canceled' => $info['canceled'] ?? false, 'error' => $info['error'] ?? null, 'http_code' => $info['http_code'] ?? 0, 'http_method' => $info['http_method'] ?? 'GET', 'redirect_count' => $info['redirect_count'] ?? 0, 'redirect_url' => $info['redirect_url'] ?? null, 'start_time' => $info['start_time'] ?? 0.0, 'url' => $info['url'] ?? '', 'user_data' => $info['user_data'] ?? null];
        } elseif (is_object($info)) {
            $this->info = ['canceled' => property_exists($info, 'canceled') ? $info->canceled : false, 'error' => property_exists($info, 'error') ? $info->error : null, 'http_code' => property_exists($info, 'http_code') ? $info->http_code : 0, 'http_method' => property_exists($info, 'http_method') ? $info->http_method : 'GET', 'redirect_count' => property_exists($info, 'redirect_count') ? $info->redirect_count : 0, 'redirect_url' => property_exists($info, 'redirect_url') ? $info->redirect_url : null, 'start_time' => property_exists($info, 'start_time') ? $info->start_time : 0.0, 'url' => property_exists($info, 'url') ? $info->url : '', 'user_data' => property_exists($info, 'user_data') ? $info->user_data : null];
        } else {
            $this->info = ['canceled' => false, 'error' => null, 'http_code' => 0, 'http_method' => 'GET', 'redirect_count' => 0, 'redirect_url' => null, 'start_time' => 0.0, 'url' => '', 'user_data' => null];
        }
        $this->status_code = $response->get_status_code();
        $this->headers = $response->get_headers(false);
        $this->content = $response->get_content(false);
    }
    public function get_status_code(): int
    {
        return $this->status_code;
    }
    public function get_headers(bool $throw = true): array
    {
        return $this->headers;
    }
    public function get_content(bool $throw = true): string
    {
        return $this->content;
    }
    /**
     * @return array|mixed[]
     */
    public function to_array(bool $throw = true): array
    {
        // Code copied from CommonResponseTrait
        if ('' === $content = $this->get_content($throw)) {
            throw new Json_Exception('Response body is empty.');
        }
        if (null !== $this->json_data) {
            return $this->json_data;
        }
        try {
            $content = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
        } catch (\Json_Exception $e) {
            /** @var string $url */
            $url = $this->get_info('url');
            throw new Json_Exception($e->get_message() . sprintf(' for "%s".', $url), $e->get_code());
        }
        if (!\is_array($content)) {
            /** @var string $url */
            $url = $this->get_info('url');
            throw new Json_Exception(sprintf('JSON content was expected to decode to an array, "%s" returned for "%s".', get_debug_type($content), $url));
        }
        return $this->json_data = $content;
    }
    public function cancel(): void
    {
    }
    public function get_info(?string $type = null): mixed
    {
        if (null !== $type) {
            return $this->info[$type] ?? null;
        }
        return $this->info;
    }
}