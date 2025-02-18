<?php

declare(strict_types=1);

namespace OCA\Pexels\AppInfo;

use OCA\Pexels\Listener\PexelsReferenceListener;
use OCA\Pexels\Reference\PhotoReferenceProvider;
use OCA\Pexels\Search\PexelsSearchPhotosProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'pexels';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerSearchProvider(PexelsSearchPhotosProvider::class);
		$context->registerReferenceProvider(PhotoReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, PexelsReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
