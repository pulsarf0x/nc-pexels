<?php

declare(strict_types=1);

namespace OCA\Pexels\Controller;

use OCA\Pexels\Service\PexelsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

class PexelsController extends Controller {

	private PexelsService $pexelsService;
	private ?string $userId;

	public function __construct(string        $appName,
								IRequest      $request,
								PexelsService $pexelsService,
								?string       $userId)
	{
		parent::__construct($appName, $request);
		$this->pexelsService = $pexelsService;
		$this->userId = $userId;
	}

	// We use this route to get the search result thumbnail and in the reference widget to get the image itself.
	// This is a way to avoid allowing the page to access Pexels directly. We let the server get the image.
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/photos/{photoId}/{size}')]
	public function getPhotoContent(int $photoId, string $size = 'original'): DataDisplayResponse {
		$photoResponse = $this->pexelsService->getPhotoContent($photoId, $size);
		if ($photoResponse !== null && isset($photoResponse['body'], $photoResponse['headers'])) {
			$response = new DataDisplayResponse(
				$photoResponse['body'],
				Http::STATUS_OK,
				['Content-Type' => $photoResponse['headers']['Content-Type'][0] ?? 'image/jpeg']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', Http::STATUS_BAD_REQUEST);
	}
}
