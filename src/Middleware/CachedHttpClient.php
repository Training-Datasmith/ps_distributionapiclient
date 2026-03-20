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

use Doctrine\Common\Cache\Cache_Provider;
use Symfony\Component\Http_Client\Http_Client;
use Symfony\Contracts\Http_Client\Http_Client_Interface;
use Symfony\Contracts\Http_Client\Response_Interface;
use Symfony\Contracts\Http_Client\Response_Stream_Interface;
class Cached_Http_Client implements Http_Client_Interface
{
    private readonly Cache_Provider $cache;
    private readonly Http_Client_Interface $client;
    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(Cache_Provider $cache, array $default_options = [], ?Http_Client_Interface $client = null)
    {
        $this->cache = $cache;
        $this->client = $client ?? Http_Client::create($default_options);
    }
    /**
     * @param array<string, mixed> $options
     *
     */
    public function request(string $method, string $url, array $options = []): Response_Interface
    {
        $cache_key = $this->get_cache_key($method, $url);
        if ($this->cache->contains($cache_key)) {
            /** @var CachedResponse $cachedResponse */
            $cached_response = $this->cache->fetch($cache_key);
            return $cached_response;
        }
        $response = $this->client->request($method, $url, $options);
        if ($response->get_status_code() !== 200) {
            return $response;
        }
        $cached_response = new Cached_Response($response);
        $this->cache->save($cache_key, $cached_response);
        return $cached_response;
    }
    public function stream($responses, ?float $timeout = null): Response_Stream_Interface
    {
        return $this->client->stream($responses, $timeout);
    }
    /**
     * @param array<string, mixed> $options
     */
    public function with_options(array $options): static
    {
        // @phpstan-ignore-next-line
        return new static($this->cache, $options);
    }
    private function get_cache_key(string $method, string $url): string
    {
        return md5($method . $url);
    }
}