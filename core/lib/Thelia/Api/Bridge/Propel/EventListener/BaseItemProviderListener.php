<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Api\Bridge\Propel\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Api\Bridge\Propel\Event\ItemProviderQueryEvent;
use Thelia\Api\Bridge\Propel\Service\ApiResourcePropelTransformerService;

class BaseItemProviderListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ApiResourcePropelTransformerService $apiResourcePropelTransformerService,
    ) {
    }

    public function baseProvider(ItemProviderQueryEvent $event): void
    {
        $query = $event->getQuery();

        $reflector = new \ReflectionClass($event->getResourceClass());

        $compositeIdentifiers = $this->apiResourcePropelTransformerService->getResourceCompositeIdentifierValues(reflector: $reflector, param: 'keys');

        $columnValues = $this->apiResourcePropelTransformerService->getColumnValues(reflector: $reflector, columns: $compositeIdentifiers);

        $this->apiResourcePropelTransformerService->queryFilterById(uriVariables: $event->getUriVariables(),query: $query,columnValues: $columnValues);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemProviderQueryEvent::class => [
                ['baseProvider', 128],
            ],
        ];
    }
}