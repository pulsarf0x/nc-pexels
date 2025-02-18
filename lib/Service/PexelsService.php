<?php

declare(strict_types=1);

namespace OCA\Pexels\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Pexels\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;


class PexelsService {

	private LoggerInterface $logger;
	private IClient $client;
	private IConfig $config;
	private IL10N $l10n;

	public function __construct (LoggerInterface $logger,
								 IClientService  $clientService,
								 IConfig $config,
								 IL10N $l10n) {
		$this->client = $clientService->newClient();

		$this->logger = $logger;
		$this->config = $config;
		$this->l10n = $l10n;
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return array [perPage, page, leftPadding]
	 */
	public static function getPexelsPaginationValues(int $offset = 0, int $limit = 5): array {
		// compute pagination values
		// indexes offset => offset + limit
		if (($offset % $limit) === 0) {
			$perPage = $limit;
			// page number starts at 1
			$page = ($offset / $limit) + 1;
			return [$perPage, $page, 0];
		} else {
			$firstIndex = $offset;
			$lastIndex = $offset + $limit - 1;
			$perPage = $limit;
			// while there is no page that contains them'all
			while (intdiv($firstIndex, $perPage) !== intdiv($lastIndex, $perPage)) {
				$perPage++;
			}
			$page = intdiv($offset, $perPage) + 1;
			$leftPadding = $firstIndex % $perPage;

			return [$perPage, $page, $leftPadding];
		}
	}

	/**
	 * @param string $query What to search for
	 * @param int $offset
	 * @param int $limit
	 * @return array request result
	 */
	public function searchPhotos(string $query, int $offset = 0, int $limit = 5): array {
		[$perPage, $page, $leftPadding] = self::getPexelsPaginationValues($offset, $limit);
		$params = [
			'query' => $query,
			'per_page' => $perPage,
			'page' => $page,
		];
		$result = $this->request('v1/search', $params);
		if (!isset($result['error'])) {
			$result['photos'] = array_slice($result['photos'], $leftPadding, $limit);
		}
		return $result;
	}

	public function getApiKey(): string {
		return $this->config->getAppValue(Application::APP_ID, 'api_key');
	}

	public function getPhotoInfo(int $photoId): array {
		return $this->request('v1/photos/' . $photoId);
	}

	public function getPhotoContent(int $photoId, string $size): ?array {
		$photoInfo = $this->getPhotoInfo($photoId);
		if (!isset($photoInfo['error']) && isset($photoInfo['src'], $photoInfo['src'][$size])) {
			try {
				$photoResponse = $this->client->get($photoInfo['src'][$size]);
				return [
					'body' => $photoResponse->getBody(),
					'headers' => $photoResponse->getHeaders(),
				];
			} catch (Exception|Throwable $e) {
				$this->logger->warning('Pexels photo content request error: ' . $e->getMessage(), ['app' => Application::APP_ID]);
				return null;
			}
		}
		return null;
	}

	/**
	 * Make an authenticated HTTP request to Pexels API
	 * @param string $endPoint The path to reach in api.github.com
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @param int $timeout
	 * @return array decoded request result or error
	 */
	public function request(string $endPoint, array $params = [], string $method = 'GET', int $timeout = 30): array {
		try {
			$url = 'https://api.pexels.com/' . $endPoint;
			$options = [
				'timeout' => $timeout,
				'headers' => [
					'User-Agent' => 'Nextcloud Pexels integration',
				],
			];
			$apiKey = $this->getApiKey();
			if ($apiKey !== '') {
				$options['headers']['Authorization'] = $apiKey;
			}

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException | ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->logger->debug('Pexels API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('Pexels API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Pexels API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
