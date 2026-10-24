<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\Client;
use ReflectionProperty;
use RuntimeException;

final class PasskeyBrowserAuthenticator
{
    public static function enable(AwaitableWebpage $webpage): void
    {
        $page = $webpage->page();
        $context = $page->context();
        $pageId = new ReflectionProperty($page, 'guid')->getValue($page);
        $contextId = new ReflectionProperty($context, 'guid')->getValue($context);

        $messages = Client::instance()->execute($contextId, 'newCDPSession', ['page' => ['guid' => $pageId]]);
        $sessionId = null;
        foreach ($messages as $message) {
            $sessionId = $message['result']['session']['guid'] ?? $sessionId;
        }

        if (! is_string($sessionId)) {
            throw new RuntimeException('Unable to create the virtual authenticator session.');
        }

        iterator_to_array(Client::instance()->execute($sessionId, 'send', [
            'method' => 'WebAuthn.enable',
            'params' => ['enableUI' => false],
        ]));
        iterator_to_array(Client::instance()->execute($sessionId, 'send', [
            'method' => 'WebAuthn.addVirtualAuthenticator',
            'params' => ['options' => [
                'protocol' => 'ctap2',
                'transport' => 'internal',
                'hasResidentKey' => true,
                'hasUserVerification' => true,
                'isUserVerified' => true,
                'automaticPresenceSimulation' => true,
            ]],
        ]));
    }
}
