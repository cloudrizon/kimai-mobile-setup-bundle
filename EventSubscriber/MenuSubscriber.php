<?php

namespace KimaiPlugin\KimaiMobileSetupBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMenuConfigure', 100],
        ];
    }

    public function onMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        $auth = $this->security;

        if (!$auth->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        if ($auth->isGranted('IS_AUTHENTICATED_FULLY')) {
            $item = new MenuItemModel('kimai_mobile_setup', 'Kimai Mobile Setup', 'kimai_mobile_setup', [], 'fa-solid fa-mobile-screen');

            $item->setChildRoutes([
                'kimai_mobile_setup',
                'user_profile_api_token_qr',
                'user_profile_access_token_qr'
            ]);

            $event->getMenu()->addChild($item);
        }
    }
}
