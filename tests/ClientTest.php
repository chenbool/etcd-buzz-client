<?php

declare(strict_types=1);

namespace chenbool\Etcd\Tests;

use chenbool\Etcd\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Buzz\Client\Curl;
use ReflectionClass;

class ClientTest extends TestCase
{
    private Client $client;
    private string $testServer = 'http://127.0.0.1:2379';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client($this->testServer);
    }

    public function testConstructor(): void
    {
        $client = new Client('127.0.0.1:2379');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithHttpClient(): void
    {
        $psr17Factory = new Psr17Factory();
        $httpClient = new Curl($psr17Factory);
        $client = new Client('127.0.0.1:2379', 'v3alpha', $httpClient);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithFullUrl(): void
    {
        $client = new Client('http://127.0.0.1:2379');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorWithVersion(): void
    {
        $client = new Client('127.0.0.1:2379', 'v3');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testSetPretty(): void
    {
        $this->client->setPretty(true);
        $this->client->setPretty(false);
        $this->assertTrue(true);
    }

    public function testSetToken(): void
    {
        $this->client->setToken('test-token');
        $this->client->clearToken();
        $this->assertTrue(true);
    }

    public function testConstants(): void
    {
        $this->assertEquals(0, Client::PERMISSION_READ);
        $this->assertEquals(1, Client::PERMISSION_WRITE);
        $this->assertEquals(2, Client::PERMISSION_READWRITE);
    }

    public function testGetAllKeysMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getAllKeys'));
    }

    public function testGetKeysWithPrefixMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getKeysWithPrefix'));
    }

    public function testPutMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'put'));
    }

    public function testGetMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'get'));
    }

    public function testDelMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'del'));
    }

    public function testCompactionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'compaction'));
    }

    public function testGrantMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'grant'));
    }

    public function testRevokeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'revoke'));
    }

    public function testKeepAliveMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'keepAlive'));
    }

    public function testTimeToLiveMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'timeToLive'));
    }

    public function testAuthEnableMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'authEnable'));
    }

    public function testAuthDisableMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'authDisable'));
    }

    public function testAuthenticateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'authenticate'));
    }

    public function testAddRoleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'addRole'));
    }

    public function testGetRoleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getRole'));
    }

    public function testDeleteRoleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteRole'));
    }

    public function testRoleListMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'roleList'));
    }

    public function testAddUserMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'addUser'));
    }

    public function testGetUserMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getUser'));
    }

    public function testDeleteUserMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteUser'));
    }

    public function testUserListMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'userList'));
    }

    public function testChangeUserPasswordMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'changeUserPassword'));
    }

    public function testGrantRolePermissionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'grantRolePermission'));
    }

    public function testRevokeRolePermissionMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'revokeRolePermission'));
    }

    public function testGrantUserRoleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'grantUserRole'));
    }

    public function testRevokeUserRoleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->client, 'revokeUserRole'));
    }
}
