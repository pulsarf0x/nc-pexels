<?php

declare(strict_types=1);

namespace OCA\Pexels\Reference;

use OC\Collaboration\Reference\ReferenceManager;
use OCA\Pexels\AppInfo\Application;
use OCA\Pexels\Service\PexelsService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;

class PhotoReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_photo';

	private ?string $userId;
	private IConfig $config;
	private ReferenceManager $referenceManager;
	private IL10N $l10n;
	private IURLGenerator $urlGenerator;
	private PexelsService $pexelsService;

	public function __construct(IConfig $config,
								IL10N $l10n,
								IURLGenerator $urlGenerator,
								PexelsService $pexelsService,
								ReferenceManager $referenceManager,
								?string $userId) {
		$this->userId = $userId;
		$this->config = $config;
		$this->referenceManager = $referenceManager;
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->pexelsService = $pexelsService;
	}

	public function getId(): string	{
		return 'pexels-photo';
	}

	public function getTitle(): string {
		return $this->l10n->t('Pexels photos');
	}

	public function getOrder(): int	{
		return 10;
	}

	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app.svg')
		);
	}

	public function getSupportedSearchProviderIds(): array {
		return ['pexels-search-photos'];

	}


	public function matchReference(string $referenceText): bool {
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		return preg_match('/^(?:https?:\/\/)?(?:www\.)?pexels\.com\/photo\/[^\/\?]+-\d+\/?$/i', $referenceText) === 1
			|| preg_match('/^(?:https?:\/\/)?(?:www\.)?pexels\.com\/photo\/\d+\/?$/i', $referenceText) === 1;
	}


	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$photoId = $this->getPhotoId($referenceText);
			if ($photoId !== null) {
				$photoInfo = $this->pexelsService->getPhotoInfo($photoId);
				$reference = new Reference($referenceText);
				$reference->setTitle($photoInfo['alt'] ?? $this->l10n->t('Pexels photo'));
				$reference->setDescription($photoInfo['photographer'] ?? $this->l10n->t('Unknown photographer'));
				$imageUrl = $this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.pexels.getPhotoContent', ['photoId' => $photoId, 'size' => 'large']);
				$reference->setImageUrl($imageUrl);
				$photoInfo['proxied_url'] = $imageUrl;
				$reference->setRichObject(
					self::RICH_OBJECT_TYPE,
					$photoInfo
				);
				return $reference;
			}
		}

		return null;
	}

	private function getPhotoId(string $url): ?int {
		preg_match('/^(?:https?:\/\/)?(?:www\.)?pexels\.com\/photo\/[^\/\?]+-(\d+)\/?$/i', $url, $matches);
		if (count($matches) > 1) {
			return (int)$matches[1];
		}

		preg_match('/^(?:https?:\/\/)?(?:www\.)?pexels\.com\/photo\/(\d+)\/?$/i', $url, $matches);
		if (count($matches) > 1) {
			return (int)$matches[1];
		}
		return null;
	}

	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	public function getCacheKey(string $referenceId): ?string {
		return $referenceId;
	}

	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
