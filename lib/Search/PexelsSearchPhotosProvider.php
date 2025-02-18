<?php

declare(strict_types=1);

namespace OCA\Pexels\Search;

use OCA\Pexels\AppInfo\Application;
use OCA\Pexels\Service\PexelsService;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class PexelsSearchPhotosProvider implements IProvider {

	private IAppManager $appManager;
	private IL10N $l10n;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private PexelsService $pexelsService;

	public function __construct(
		IAppManager   $appManager,
		IL10N         $l10n,
		IConfig       $config,
		IURLGenerator $urlGenerator,
		PexelsService $pexelsService
	) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->pexelsService = $pexelsService;
	}


	public function getId(): string {
		return 'pexels-search-photos';
	}


	public function getName(): string {
		return $this->l10n->t('Pexels images');
	}


	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Pexels results
			return -1;
		}

		return 20;
	}


	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = $offset ? intval($offset) : 0;

		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		if ($apiKey === '') {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->pexelsService->searchPhotos($term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$photos = [];
		} else {
			$photos = $searchResult['photos'];
		}

		$formattedResults = array_map(function (array $entry): SearchResultEntry {
			return new SearchResultEntry(
				$this->getThumbnailUrl($entry),
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getUrl($entry),
				'',
				false
			);
		}, $photos);

		return SearchResult::paginated(
			$this->getName(),
			$formattedResults,
			$offset + $limit
		);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getMainText(array $entry): string {
		return $entry['alt'];
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getSubline(array $entry): string {
		return $entry['photographer'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getUrl(array $entry): string {
		return $entry['url'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getThumbnailUrl(array $entry): string {
		$photoId = $entry['id'] ?? 0;
		return $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.pexels.getPhotoContent', ['photoId' => $photoId, 'size' => 'small']);
	}
}
