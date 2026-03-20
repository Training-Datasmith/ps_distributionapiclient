<?php

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
declare (strict_types=1);
namespace Presta_Shop\Module\Distribution_Api_Client;

use Presta_Shop\Circuit_Breaker\Contract\Circuit_Breaker_Interface;
use Presta_Shop\Presta_Shop\Adapter\Module\Module_Data_Provider;
use Presta_Shop\Presta_Shop\Core\Module\Source_Handler\Source_Handler_Factory;
use RuntimeException;
class Distribution_Api
{
    public const ALLOWED_FAILURES = 2;
    public const TIMEOUT_IN_SECONDS = 3;
    public const THRESHOLD_SECONDS = 86400;
    // 24 hours
    public const CACHE_LIFETIME_SECONDS = 86400;
    // 24 hours
    public const URL_TRACKING_ENV_NAME = 'PS_URL_TRACKING';
    private const API_ENDPOINT = 'https://api.prestashop-project.org';
    /** @var CircuitBreakerInterface */
    private $circruit_breaker;
    /** @var SourceHandlerFactory */
    private $source_handler_factory;
    /** @var ModuleDataProvider */
    private $module_data_provider;
    private readonly string $download_directory;
    public function __construct(Circuit_Breaker_Interface $circruit_breaker, Source_Handler_Factory $source_handler_factory, Module_Data_Provider $module_data_provider, private readonly Shop_Data_Provider $shop_data_provider, private readonly string $prestashop_version, string $download_directory, private readonly string $project_directory)
    {
        $this->circruit_breaker = $circruit_breaker;
        $this->source_handler_factory = $source_handler_factory;
        $this->module_data_provider = $module_data_provider;
        $this->download_directory = rtrim($download_directory, '/');
    }
    /**
     * @return array<array<string, string>>
     */
    public function get_module_list(): array
    {
        $endpoint = $this->get_modules_list_url();
        $response = $this->get_response($endpoint);
        $modules = [];
        foreach ($response as $name => $module) {
            $attributes = ['name' => $name, 'version_available' => $module['version'], 'download_url' => $module['download_url']];
            if (!$this->is_module_on_disk($name)) {
                $attributes += ['displayName' => $module['display_name'], 'description' => $module['description'], 'version' => $module['version'], 'author' => $module['author'], 'img' => $module['icon'], 'tab' => $module['tab']];
            }
            $modules[] = $attributes;
        }
        return $modules;
    }
    public function download_module(string $module_name): void
    {
        $modules = $this->get_module_list();
        foreach ($modules as $module) {
            if ($module['name'] === $module_name) {
                $this->do_download($module);
                break;
            }
        }
    }
    public function is_module_on_disk(string $module_name): bool
    {
        return $this->module_data_provider->is_on_disk($module_name);
    }
    /**
     * Extracts the download URL from a module data structure
     *
     * @param array{download_url?: string} $module Module data structure, from API response
     *
     * @return string Download URL
     */
    protected function get_module_download_url(array $module): string
    {
        if (!isset($module['download_url'])) {
            throw new RuntimeException('Could not determine URL to download the module from');
        }
        return $this->add_shop_info_to_url($module['download_url']);
    }
    /**
     * Returns the URL to the list of modules for this version
     */
    private function get_modules_list_url(): string
    {
        $url = self::API_ENDPOINT . '/modules/' . $this->prestashop_version;
        return $this->add_shop_info_to_url($url);
    }
    /**
     * Adds shop information to an URL
     *
     * @param string $url API endpoint
     *
     * @return string Modified URL
     */
    private function add_shop_info_to_url(string $url): string
    {
        if (isset($_SERVER[self::URL_TRACKING_ENV_NAME]) && ((bool) $_SERVER[self::URL_TRACKING_ENV_NAME] === false || $_SERVER[self::URL_TRACKING_ENV_NAME] === 'false')) {
            return $url;
        }
        $separator = str_contains($url, '?') ? '&' : '?';
        // Add shop URL
        $shop_url = urlencode($this->shop_data_provider->get_shop_url());
        $url = sprintf('%s%sshop_domain=%s', $url, $separator, $shop_url);
        // Add distribution details
        $metadata_file = $this->project_directory . '/app/metadata.json';
        if (file_exists($metadata_file)) {
            $metadata_file_content = file_get_contents($metadata_file);
            if (!empty($metadata_file_content)) {
                /** @var array<string, string>|false $metadata */
                $metadata = json_decode($metadata_file_content, true);
                if (!empty($metadata['distribution']) && !empty($metadata['distributionVersion'])) {
                    $url = sprintf('%s&distribution=%s&distribution_version=%s', $url, $metadata['distribution'], $metadata['distributionVersion']);
                }
            }
        }
        return $url;
    }
    /**
     * @param array<string, string> $module
     */
    private function do_download(array $module): void
    {
        $download_url = $this->get_module_download_url($module);
        $module_zip = file_get_contents($download_url);
        $download_path = $this->get_module_download_directory($module['name']);
        $this->create_download_directory_if_needed($download_path);
        file_put_contents($this->get_module_download_directory($module['name']), $module_zip);
        $handler = $this->source_handler_factory->get_handler($this->get_module_download_directory($module['name']));
        $handler->handle($this->get_module_download_directory($module['name']));
    }
    private function get_module_download_directory(string $module_name): string
    {
        if (str_contains($module_name, '/') || str_contains($module_name, '\\')) {
            throw new RuntimeException('Invalid module name: path separators are not allowed.');
        }
        return $this->download_directory . '/' . $module_name . '.zip';
    }
    private function create_download_directory_if_needed(string $download_path): void
    {
        if (!file_exists(dirname($download_path))) {
            mkdir(dirname($download_path), 0755, true);
        }
    }
    /**
     * @return array<array<string, string>>
     */
    private function get_response(string $endpoint): array
    {
        $response = $this->circruit_breaker->call($endpoint, [], function (): void {
            throw new \Presta_Shop_Exception('Unable to retrieve informations from Distribution API : cannot automatically update native modules for the moment.');
        });
        /** @var array<array<string, string>> $json */
        $json = json_decode((string) $response, true) ?: [];
        return $json;
    }
}