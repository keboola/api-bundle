<?php

declare(strict_types=1);

namespace Keboola\ApiBundle\Tests\Security\ManageApiToken;

use Keboola\ApiBundle\Security\ManageApiToken\ManageApiToken;
use PHPUnit\Framework\TestCase;

class ManageApiTokenTest extends TestCase
{
    public function testAccessors(): void
    {
        $token = ManageApiToken::fromVerifyResponse([
            'id' => 100001,
            'description' => 'test',
            'created' => '2024-03-21T12:28:49+0100',
            'lastUsed' => '2024-03-21T12:28:57+0100',
            'expires' => '2024-03-21T13:28:49+0100',
            'isSessionToken' => false,
            'isExpired' => false,
            'isDisabled' => false,
            'scopes' => [],
            'type' => 'admin',
            'creator' => [
                'id' => 3801,
                'name' => 'John Doe',
            ],
            'user' => [
                'id' => 3801,
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'mfaEnabled' => true,
                'features' => [],
                'canAccessLogs' => true,
                'isSuperAdmin' => true,
            ],
        ]);

        self::assertSame('100001', $token->getUserIdentifier());
        self::assertSame('test', $token->getUsername());
        self::assertSame([], $token->getScopes());
        self::assertSame([], $token->getFeatures());
        self::assertSame(true, $token->isSuperAdmin());
    }

    public function testHasScope(): void
    {
        $token = ManageApiToken::fromVerifyResponse([
            'id' => 99994,
            'description' => 'John Doe test',
            'created' => '2024-03-21T12:26:43+0100',
            'lastUsed' => '2024-03-21T12:26:54+0100',
            'expires' => null,
            'isSessionToken' => false,
            'isExpired' => false,
            'isDisabled' => false,
            'scopes' => ['some:scope'],
            'type' => 'super',
            'creator' => [
                'id' => 3801,
                'name' => 'John Doe',
            ],
            'user' => [
                'id' => 3801,
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'mfaEnabled' => true,
                'features' => [],
                'isSuperAdmin' => false,
                'canAccessLogs' => true,
            ],
        ]);

        self::assertTrue($token->hasScope('some:scope'));
        self::assertFalse($token->hasScope('other:scope'));
        self::assertFalse($token->isSuperAdmin());
    }

    public function testHasFeature(): void
    {
        $token = ManageApiToken::fromVerifyResponse([
            'id' => 99994,
            'description' => 'John Doe test',
            'created' => '2024-03-21T12:26:43+0100',
            'lastUsed' => '2024-03-21T12:26:54+0100',
            'expires' => null,
            'isSessionToken' => false,
            'isExpired' => false,
            'isDisabled' => false,
            'scopes' => [],
            'type' => 'super',
            'creator' => [
                'id' => 3801,
                'name' => 'John Doe',
            ],
            'user' => [
                'id' => 3801,
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'mfaEnabled' => true,
                'features' => ['feat-1', 'feat-2'],
                'isSuperAdmin' => false,
                'canAccessLogs' => true,
            ],
        ]);

        self::assertSame(['feat-1', 'feat-2'], $token->getFeatures());
        self::assertTrue($token->hasFeature('feat-1'));
        self::assertFalse($token->hasFeature('feat-3'));
    }
}
