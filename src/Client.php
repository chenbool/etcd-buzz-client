<?php

declare(strict_types=1);

namespace chenbool\Etcd;

use Buzz\Browser;
use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Etcd v3 客户端
 * 
 * 使用 Buzz HTTP 客户端连接 Etcd v3 API
 */
class Client
{
    private const URI_PUT = 'kv/put';
    private const URI_RANGE = 'kv/range';
    private const URI_DELETE_RANGE = 'kv/deleterange';
    private const URI_TXN = 'kv/txn';
    private const URI_COMPACTION = 'kv/compaction';

    private const URI_GRANT = 'lease/grant';
    private const URI_REVOKE = 'lease/revoke';
    private const URI_KEEPALIVE = 'lease/keepalive';
    private const URI_TIMETOLIVE = 'lease/timetolive';

    private const URI_AUTH_ROLE_ADD = 'auth/role/add';
    private const URI_AUTH_ROLE_GET = 'auth/role/get';
    private const URI_AUTH_ROLE_DELETE = 'auth/role/delete';
    private const URI_AUTH_ROLE_LIST = 'auth/role/list';

    private const URI_AUTH_ENABLE = 'auth/enable';
    private const URI_AUTH_DISABLE = 'auth/disable';
    private const URI_AUTH_AUTHENTICATE = 'auth/authenticate';

    private const URI_AUTH_USER_ADD = 'auth/user/add';
    private const URI_AUTH_USER_GET = 'auth/user/get';
    private const URI_AUTH_USER_DELETE = 'auth/user/delete';
    private const URI_AUTH_USER_CHANGE_PASSWORD = 'auth/user/changepw';
    private const URI_AUTH_USER_LIST = 'auth/user/list';

    private const URI_AUTH_ROLE_GRANT = 'auth/role/grant';
    private const URI_AUTH_ROLE_REVOKE = 'auth/role/revoke';

    private const URI_AUTH_USER_GRANT = 'auth/user/grant';
    private const URI_AUTH_USER_REVOKE = 'auth/user/revoke';

    /** 读权限 */
    public const PERMISSION_READ = 0;
    /** 写权限 */
    public const PERMISSION_WRITE = 1;
    /** 读写权限 */
    public const PERMISSION_READWRITE = 2;

    private string $server;
    private string $version;
    private ClientInterface $httpClient;
    private Browser $browser;
    private bool $pretty = false;
    private ?string $token = null;

    /**
     * 构造函数
     * 
     * @param string $server Etcd 服务器地址，默认为 127.0.0.1:2379
     * @param string $version API 版本，默认为 v3alpha
     * @param ClientInterface|null $httpClient 自定义 HTTP 客户端
     */
    public function __construct(
        string $server = '127.0.0.1:2379',
        string $version = 'v3',
        ?ClientInterface $httpClient = null
    ) {
        $this->server = rtrim($server);
        if (str_starts_with($this->server, 'http') === false) {
            $this->server = 'http://' . $this->server;
        }
        $this->version = trim($version);

        $psr17Factory = new Psr17Factory();
        $this->httpClient = $httpClient ?? new Curl($psr17Factory);
        $this->browser = new Browser($this->httpClient, $psr17Factory);
    }

    /**
     * 设置简洁模式
     * 
     * 启用后，返回结果只包含所需字段
     * 
     * @param bool $enabled 是否启用
     */
    public function setPretty(bool $enabled): void
    {
        $this->pretty = $enabled;
    }

    /**
     * 设置认证令牌
     * 
     * @param string $token 认证令牌
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * 清除认证令牌
     */
    public function clearToken(): void
    {
        $this->token = null;
    }

    /**
     * 设置键值对
     * 
     * @param string $key 键
     * @param string $value 值
     * @param array $options 可选参数
     * @return array|string
     */
    public function put(string $key, string $value, array $options = []): array|string
    {
        $params = [
            'key' => $key,
            'value' => $value,
        ];

        $params = $this->encode($params);
        $options = $this->encode($options);
        $body = $this->request(self::URI_PUT, $params, $options);
        $body = $this->decodeBodyForFields(
            $body,
            'prev_kv',
            ['key', 'value']
        );

        if (isset($body['prev_kv']) && $this->pretty) {
            return $this->convertFields($body['prev_kv']);
        }

        return $body;
    }

    /**
     * 获取值
     * 
     * @param string $key 键
     * @param array $options 可选参数
     * @return array
     */
    public function get(string $key, array $options = []): array
    {
        $params = [
            'key' => $key,
        ];
        $params = $this->encode($params);
        $options = $this->encode($options);
        $body = $this->request(self::URI_RANGE, $params, $options);
        $body = $this->decodeBodyForFields(
            $body,
            'kvs',
            ['key', 'value']
        );

        if (isset($body['kvs']) && $this->pretty) {
            return $this->convertFields($body['kvs']);
        }

        return $body;
    }

    /**
     * 获取所有键
     * 
     * @return array
     */
    public function getAllKeys(): array
    {
        return $this->get("\0", ['range_end' => "\0"]);
    }

    /**
     * 获取指定前缀的所有键
     * 
     * @param string $prefix 键前缀
     * @return array
     */
    public function getKeysWithPrefix(string $prefix): array
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return [];
        }
        $lastIndex = strlen($prefix) - 1;
        $lastChar = $prefix[$lastIndex];
        $nextAsciiCode = ord($lastChar) + 1;
        $rangeEnd = $prefix;
        $rangeEnd[$lastIndex] = chr($nextAsciiCode);

        return $this->get($prefix, ['range_end' => $rangeEnd]);
    }

    /**
     * 删除键
     * 
     * @param string $key 键
     * @param array $options 可选参数
     * @return array
     */
    public function del(string $key, array $options = []): array
    {
        $params = [
            'key' => $key,
        ];
        $params = $this->encode($params);
        $options = $this->encode($options);
        $body = $this->request(self::URI_DELETE_RANGE, $params, $options);
        $body = $this->decodeBodyForFields(
            $body,
            'prev_kvs',
            ['key', 'value']
        );

        if (isset($body['prev_kvs']) && $this->pretty) {
            return $this->convertFields($body['prev_kvs']);
        }

        return $body;
    }

    /**
     * 压缩键值存储
     * 
     * @param int $revision 压缩到的版本号
     * @param bool $physical 是否物理压缩
     * @return array
     */
    public function compaction(int $revision, bool $physical = false): array
    {
        $params = [
            'revision' => $revision,
            'physical' => $physical,
        ];

        return $this->request(self::URI_COMPACTION, $params);
    }

    /**
     * 创建租约
     * 
     * @param int $ttl 租约存活时间（秒）
     * @param int $id 租约 ID，0 表示自动生成
     * @return array
     */
    public function grant(int $ttl, int $id = 0): array
    {
        $params = [
            'ttl' => $ttl,
            'ID' => $id,
        ];

        return $this->request(self::URI_GRANT, $params);
    }

    /**
     * 撤销租约
     * 
     * @param int $id 租约 ID
     * @return array
     */
    public function revoke(int $id): array
    {
        $params = [
            'ID' => $id,
        ];

        return $this->request(self::URI_REVOKE, $params);
    }

    /**
     * 保持租约活跃
     * 
     * @param int $id 租约 ID
     * @return array
     */
    public function keepAlive(int $id): array
    {
        $params = [
            'ID' => $id,
        ];

        $body = $this->request(self::URI_KEEPALIVE, $params);

        if (!isset($body['ID'])) {
            return $body;
        }

        return [
            'ID' => $body['ID'],
            'TTL' => $body['TTL'] ?? 0,
        ];
    }

    /**
     * 获取租约信息
     * 
     * @param int $id 租约 ID
     * @param bool $keys 是否返回关联的键
     * @return array
     */
    public function timeToLive(int $id, bool $keys = false): array
    {
        $params = [
            'ID' => $id,
            'keys' => $keys,
        ];

        $body = $this->request(self::URI_TIMETOLIVE, $params);

        if (isset($body['keys'])) {
            $body['keys'] = array_map(function ($value) {
                return base64_decode($value);
            }, $body['keys']);
        }

        return $body;
    }

    /**
     * 启用认证
     * 
     * @return array
     */
    public function authEnable(): array
    {
        $body = $this->request(self::URI_AUTH_ENABLE);
        $this->clearToken();

        return $body;
    }

    /**
     * 禁用认证
     * 
     * @return array
     */
    public function authDisable(): array
    {
        $body = $this->request(self::URI_AUTH_DISABLE);
        $this->clearToken();

        return $body;
    }

    /**
     * 用户认证
     * 
     * @param string $user 用户名
     * @param string $password 密码
     * @return array|string
     */
    public function authenticate(string $user, string $password): array|string
    {
        $params = [
            'name' => $user,
            'password' => $password,
        ];

        $body = $this->request(self::URI_AUTH_AUTHENTICATE, $params);
        
        if (isset($body['token'])) {
            return $body['token'];
        }

        return $body;
    }

    /**
     * 添加角色
     * 
     * @param string $name 角色名
     * @return array
     */
    public function addRole(string $name): array
    {
        $params = [
            'name' => $name,
        ];

        return $this->request(self::URI_AUTH_ROLE_ADD, $params);
    }

    /**
     * 获取角色信息
     * 
     * @param string $role 角色名
     * @return array
     */
    public function getRole(string $role): array
    {
        $params = [
            'role' => $role,
        ];

        $body = $this->request(self::URI_AUTH_ROLE_GET, $params);
        $body = $this->decodeBodyForFields(
            $body,
            'perm',
            ['key', 'range_end']
        );
        if ($this->pretty && isset($body['perm'])) {
            return $body['perm'];
        }

        return $body;
    }

    /**
     * 删除角色
     * 
     * @param string $role 角色名
     * @return array
     */
    public function deleteRole(string $role): array
    {
        $params = [
            'role' => $role,
        ];

        return $this->request(self::URI_AUTH_ROLE_DELETE, $params);
    }

    /**
     * 获取角色列表
     * 
     * @return array
     */
    public function roleList(): array
    {
        $body = $this->request(self::URI_AUTH_ROLE_LIST);

        if ($this->pretty && isset($body['roles'])) {
            return $body['roles'];
        }

        return $body;
    }

    /**
     * 添加用户
     * 
     * @param string $user 用户名
     * @param string $password 密码
     * @return array
     */
    public function addUser(string $user, string $password): array
    {
        $params = [
            'name' => $user,
            'password' => $password,
        ];

        return $this->request(self::URI_AUTH_USER_ADD, $params);
    }

    /**
     * 获取用户信息
     * 
     * @param string $user 用户名
     * @return array
     */
    public function getUser(string $user): array
    {
        $params = [
            'name' => $user,
        ];

        $body = $this->request(self::URI_AUTH_USER_GET, $params);
        if ($this->pretty && isset($body['roles'])) {
            return $body['roles'];
        }

        return $body;
    }

    /**
     * 删除用户
     * 
     * @param string $user 用户名
     * @return array
     */
    public function deleteUser(string $user): array
    {
        $params = [
            'name' => $user,
        ];

        return $this->request(self::URI_AUTH_USER_DELETE, $params);
    }

    /**
     * 获取用户列表
     * 
     * @return array
     */
    public function userList(): array
    {
        $body = $this->request(self::URI_AUTH_USER_LIST);
        if ($this->pretty && isset($body['users'])) {
            return $body['users'];
        }

        return $body;
    }

    /**
     * 修改用户密码
     * 
     * @param string $user 用户名
     * @param string $password 新密码
     * @return array
     */
    public function changeUserPassword(string $user, string $password): array
    {
        $params = [
            'name' => $user,
            'password' => $password,
        ];

        return $this->request(self::URI_AUTH_USER_CHANGE_PASSWORD, $params);
    }

    /**
     * 授予角色权限
     * 
     * @param string $role 角色名
     * @param int $permType 权限类型
     * @param string $key 键
     * @param string|null $rangeEnd 键范围结束
     * @return array
     */
    public function grantRolePermission(
        string $role,
        int $permType,
        string $key,
        ?string $rangeEnd = null
    ): array {
        $params = [
            'name' => $role,
            'perm' => [
                'permType' => $permType,
                'key' => base64_encode($key),
            ],
        ];
        if ($rangeEnd !== null) {
            $params['perm']['range_end'] = base64_encode($rangeEnd);
        }

        return $this->request(self::URI_AUTH_ROLE_GRANT, $params);
    }

    /**
     * 撤销角色权限
     * 
     * @param string $role 角色名
     * @param string $key 键
     * @param string|null $rangeEnd 键范围结束
     * @return array
     */
    public function revokeRolePermission(
        string $role,
        string $key,
        ?string $rangeEnd = null
    ): array {
        $params = [
            'role' => $role,
            'key' => $key,
        ];
        if ($rangeEnd !== null) {
            $params['range_end'] = $rangeEnd;
        }

        return $this->request(self::URI_AUTH_ROLE_REVOKE, $params);
    }

    /**
     * 授予用户角色
     * 
     * @param string $user 用户名
     * @param string $role 角色名
     * @return array
     */
    public function grantUserRole(string $user, string $role): array
    {
        $params = [
            'user' => $user,
            'role' => $role,
        ];

        return $this->request(self::URI_AUTH_USER_GRANT, $params);
    }

    /**
     * 撤销用户角色
     * 
     * @param string $user 用户名
     * @param string $role 角色名
     * @return array
     */
    public function revokeUserRole(string $user, string $role): array
    {
        $params = [
            'name' => $user,
            'role' => $role,
        ];

        return $this->request(self::URI_AUTH_USER_REVOKE, $params);
    }

    /**
     * 注册服务
     * 
     * 将服务信息注册到 Etcd，使用租约保持服务在线
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机地址
     * @param int $servicePort 服务端口
     * @param int $ttl 租约存活时间（秒），默认 30 秒
     * @param array $metadata 额外元数据
     * @return array 注册结果
     */
    public function registerService(
        string $serviceName,
        string $serviceHost,
        int $servicePort,
        int $ttl = 30,
        array $metadata = []
    ): array {
        $leaseResult = $this->grant($ttl);
        $leaseId = $leaseResult['ID'] ?? 0;
        
        if (empty($leaseId)) {
            return ['error' => 'Failed to create lease'];
        }

        $leaseId = (int) $leaseId;

        $serviceKey = $this->getServiceKey($serviceName, $serviceHost, $servicePort);
        $serviceValue = json_encode(array_merge([
            'host' => $serviceHost,
            'port' => $servicePort,
            'name' => $serviceName,
        ], $metadata));

        return $this->put($serviceKey, $serviceValue, ['lease' => $leaseId]);
    }

    /**
     * 注销服务
     * 
     * 从 Etcd 中移除服务信息
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机地址
     * @param int $servicePort 服务端口
     * @return array 注销结果
     */
    public function deregisterService(
        string $serviceName,
        string $serviceHost,
        int $servicePort
    ): array {
        $serviceKey = $this->getServiceKey($serviceName, $serviceHost, $servicePort);
        return $this->del($serviceKey);
    }

    /**
     * 发现服务
     * 
     * 根据服务名称查找所有可用的服务实例
     * 
     * @param string $serviceName 服务名称
     * @return array 服务实例列表
     */
    public function discoverService(string $serviceName): array
    {
        $prefix = $this->getServicePrefix($serviceName);
        return $this->getKeysWithPrefix($prefix);
    }

    /**
     * 获取服务健康状态
     * 
     * 检查服务是否仍然在线（通过检查租约是否有效）
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机地址
     * @param int $servicePort 服务端口
     * @return array 健康状态信息
     */
    public function getServiceHealth(
        string $serviceName,
        string $serviceHost,
        int $servicePort
    ): array {
        $serviceKey = $this->getServiceKey($serviceName, $serviceHost, $servicePort);
        $result = $this->get($serviceKey);
        
        if (empty($result)) {
            return ['healthy' => false, 'reason' => 'Service not found'];
        }
        
        return ['healthy' => true, 'service' => $result];
    }

    /**
     * 刷新服务租约
     * 
     * 保持服务注册信息有效
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机地址
     * @param int $servicePort 服务端口
     * @param int $ttl 新的租约存活时间
     * @return array 刷新结果
     */
    public function refreshServiceLease(
        string $serviceName,
        string $serviceHost,
        int $servicePort,
        int $ttl = 30
    ): array {
        $serviceKey = $this->getServiceKey($serviceName, $serviceHost, $servicePort);
        $result = $this->get($serviceKey);
        
        if (empty($result)) {
            return ['error' => 'Service not found'];
        }
        
        return $this->registerService($serviceName, $serviceHost, $servicePort, $ttl);
    }

    /**
     * 心跳 - 保持服务在线
     * 
     * 自动续期服务租约，直到进程结束
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机
     * @param int $servicePort 服务端口
     * @param int $ttl 租约时间（秒），默认 10 秒
     * @return void
     */
    public function heartbeat(
        string $serviceName,
        string $serviceHost,
        int $servicePort,
        int $ttl = 10
    ): void {
        $interval = (int) ($ttl / 2);
        if ($interval < 1) {
            $interval = 1;
        }

        while (true) {
            $this->refreshServiceLease($serviceName, $serviceHost, $servicePort, $ttl);
            sleep($interval);
        }
    }

    /**
     * 获取所有服务
     * 
     * 获取所有已注册的服务实例
     * 
     * @return array 所有服务列表
     */
    public function getAllServices(): array
    {
        return $this->getKeysWithPrefix('/services/');
    }

    /**
     * 批量注册服务
     * 
     * 一次性注册多个服务实例
     * 
     * @param array $services 服务数组，每项包含 host, port, ttl, metadata
     * @return array 注册结果
     */
    public function registerServices(array $services): array
    {
        $results = [];
        foreach ($services as $service) {
            $name = $service['name'] ?? '';
            $host = $service['host'] ?? '';
            $port = $service['port'] ?? 0;
            $ttl = $service['ttl'] ?? 30;
            $metadata = $service['metadata'] ?? [];

            if ($name && $host && $port) {
                $results[] = $this->registerService($name, $host, $port, $ttl, $metadata);
            }
        }
        return $results;
    }

    /**
     * 批量刷新租约
     * 
     * 一次性刷新多个服务的租约
     * 
     * @param array $services 服务数组，每项包含 name, host, port, ttl
     * @return array 刷新结果
     */
    public function refreshServiceLeases(array $services): array
    {
        $results = [];
        foreach ($services as $service) {
            $name = $service['name'] ?? '';
            $host = $service['host'] ?? '';
            $port = $service['port'] ?? 0;
            $ttl = $service['ttl'] ?? 30;

            if ($name && $host && $port) {
                $results[] = $this->refreshServiceLease($name, $host, $port, $ttl);
            }
        }
        return $results;
    }

    /**
     * 刷新所有服务租约
     * 
     * 自动发现所有服务并刷新其租约
     * 
     * @param int $ttl 租约存活时间，默认 30 秒
     * @return array 刷新结果
     */
    public function refreshAllServicesLease(int $ttl = 30): array
    {
        $allServices = $this->getAllServices();
        
        if (empty($allServices)) {
            return ['message' => 'No services found'];
        }

        $results = [];
        foreach ($allServices as $service) {
            $value = json_decode($service['value'] ?? '{}', true);
            $name = $value['name'] ?? '';
            $host = $value['host'] ?? '';
            $port = $value['port'] ?? 0;

            if ($name && $host && $port) {
                $results[] = $this->refreshServiceLease($name, $host, $port, $ttl);
            }
        }
        return $results;
    }

    /**
     * 调用服务
     * 
     * 从 Etcd 发现服务并发起 HTTP 调用，支持负载均衡
     * 
     * @param string $serviceName 服务名称
     * @param string $method HTTP 方法 (GET/POST/PUT/DELETE/PATCH)
     * @param string $path 请求路径
     * @param array $data 请求数据
     * @param string $strategy 负载均衡策略: random/round 默认 random
     * @return array 响应结果
     */
    public function call(
        string $serviceName,
        string $method,
        string $path,
        array $data = [],
        string $strategy = 'random'
    ): array {
        $services = $this->discoverService($serviceName);
        
        if (empty($services)) {
            return ['error' => 'No available service instances'];
        }

        $service = $this->selectService($services, $strategy);
        $url = sprintf('http://%s:%d%s', $service['host'], $service['port'], $path);

        return $this->makeHttpRequest($method, $url, $data);
    }

    /**
     * 选择服务实例
     * 
     * @param array $services 服务列表
     * @param string $strategy 负载均衡策略
     * @return array
     */
    private function selectService(array $services, string $strategy): array
    {
        if ($strategy === 'round') {
            static $index = 0;
            $service = $services[$index % count($services)];
            $index++;
            return $service;
        }

        return $services[array_rand($services)];
    }

    /**
     * 发起 HTTP 请求
     * 
     * @param string $method HTTP 方法
     * @param string $url 请求 URL
     * @param array $data 请求数据
     * @return array
     */
    private function makeHttpRequest(string $method, string $url, array $data = []): array
    {
        try {
            $headers = ['Content-Type' => 'application/json'];
            
            $body = '';
            if ($data !== []) {
                $body = json_encode($data);
            }

            $response = match (strtoupper($method)) {
                'GET' => $this->browser->get($url, $headers),
                'POST' => $this->browser->post($url, $headers, $body),
                'PUT' => $this->browser->put($url, $headers, $body),
                'DELETE' => $this->browser->delete($url, $headers, $body),
                'PATCH' => $this->browser->patch($url, $headers, $body),
                default => throw new \Exception("Unsupported HTTP method: $method"),
            };

            return [
                'status' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents(),
                'headers' => $response->getHeaders(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 获取服务键前缀
     * 
     * @param string $serviceName 服务名称
     * @return string
     */
    private function getServicePrefix(string $serviceName): string
    {
        return '/services/' . $serviceName . '/';
    }

    /**
     * 获取服务完整键名
     * 
     * @param string $serviceName 服务名称
     * @param string $serviceHost 服务主机
     * @param int $servicePort 服务端口
     * @return string
     */
    private function getServiceKey(
        string $serviceName,
        string $serviceHost,
        int $servicePort
    ): string {
        return $this->getServicePrefix($serviceName) 
            . $serviceHost . ':' . $servicePort;
    }

    /**
     * 发送 HTTP 请求
     * 
     * @param string $uri API 路径
     * @param array $params 请求参数
     * @param array $options 可选参数
     * @return array
     */
    private function request(string $uri, array $params = [], array $options = []): array
    {
        if ($options !== []) {
            $params = array_merge($params, $options);
        }
        if ($params === []) {
            $params['php-etcd-client'] = 1;
        }

        $url = sprintf('%s/%s/%s', $this->server, $this->version, $uri);

        $headers = ['Content-Type' => 'application/json'];
        if ($this->token !== null) {
            $headers['Grpc-Metadata-Token'] = $this->token;
        }

        $response = $this->browser->post(
            $url,
            $headers,
            json_encode($params)
        );

        return $this->parseResponse($response);
    }

    /**
     * 解析响应
     * 
     * @param ResponseInterface $response PSR-7 响应对象
     * @return array
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        $body = json_decode($content, true) ?? [];

        if ($this->pretty && isset($body['header'])) {
            unset($body['header']);
        }

        return $body;
    }

    /**
     * Base64 编码字符串字段
     * 
     * @param array $data 待编码数据
     * @return array
     */
    private function encode(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && !is_numeric($value)) {
                $data[$key] = base64_encode($value);
            }
        }

        return $data;
    }

    /**
     * Base64 解码指定字段
     * 
     * @param array $body 响应体
     * @param string $bodyKey 需要解码的字段名
     * @param array $fields 需要解码的子字段
     * @return array
     */
    private function decodeBodyForFields(
        array $body,
        string $bodyKey,
        array $fields
    ): array {
        if (!isset($body[$bodyKey])) {
            return $body;
        }
        $data = $body[$bodyKey];
        if (!isset($data[0])) {
            $data = [$data];
        }
        foreach ($data as $key => $value) {
            foreach ($fields as $field) {
                if (isset($value[$field])) {
                    $data[$key][$field] = base64_decode($value[$field]);
                }
            }
        }

        if (isset($body[$bodyKey][0])) {
            $body[$bodyKey] = $data;
        } else {
            $body[$bodyKey] = $data[0];
        }

        return $body;
    }

    /**
     * 转换字段为简化格式
     * 
     * @param array $data 数据
     * @return array|string
     */
    private function convertFields(array $data): array|string
    {
        if (!isset($data[0])) {
            return $data['value'];
        }

        $map = [];
        foreach ($data as $index => $value) {
            $key = $value['key'];
            $map[$key] = $value['value'];
        }

        return $map;
    }

    /**
     * 发现所有服务
     * 
     * 获取所有已注册的服务，按服务名分组返回
     * 
     * @param string $prefix 服务前缀，默认 '/services/'
     * @return array 按服务名分组的服务列表
     */
    public function discoverAll(string $prefix = '/services/'): array
    {
        $prefix = rtrim($prefix, '/') . '/';
        $result = $this->getKeysWithPrefix($prefix);
        
        // Handle different response formats
        $allKeys = [];
        if (isset($result['kvs'])) {
            $allKeys = $result['kvs'];
        } elseif (is_array($result)) {
            $allKeys = $result;
        }
        
        if (empty($allKeys)) {
            return [];
        }
        
        $services = [];
        
        foreach ($allKeys as $key => $value) {
            // Handle different formats: array with key/value or map with key=>value
            if (is_array($value) && isset($value['key'])) {
                $keyStr = $value['key'];
                $valStr = $value['value'] ?? '';
            } else {
                $keyStr = $key;
                $valStr = $value;
            }
            
            // Decode if needed
            if (is_string($keyStr)) {
                $decodedKey = base64_decode($keyStr, true) ?: $keyStr;
            } else {
                $decodedKey = $keyStr;
            }
            
            $decodedValue = is_string($valStr) ? json_decode($valStr, true) : $valStr;
            if (!$decodedValue) $decodedValue = $valStr;
            
            $parts = explode('/', $decodedKey);
            $serviceName = $parts[2] ?? 'unknown';
            $services[$serviceName][] = $decodedValue;
        }
        
        return $services;
    }

    /**
     * 批量心跳 - 保持多个服务在线
     * 
     * @param array $services 服务数组 [['name' => 'svc', 'host' => '127.0.0.1', 'port' => 8080], ...]
     * @param int $ttl 租约存活时间（秒）
     * @param int $interval 心跳间隔（秒）
     * @param callable|null $callback 心跳回调函数 function($name, $host, $port)
     * @return void
     */
    public function heartbeatAll(
        array $services,
        int $ttl = 30,
        int $interval = 25,
        ?callable $callback = null
    ): void {
        while (true) {
            foreach ($services as $service) {
                $name = $service['name'] ?? '';
                $host = $service['host'] ?? '';
                $port = $service['port'] ?? 0;
                
                if ($name && $host && $port) {
                    $this->refreshServiceLease($name, $host, $port, $ttl);
                    
                    if ($callback) {
                        $callback($name, $host, $port);
                    }
                }
            }
            sleep($interval);
        }
    }
}
