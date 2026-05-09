# Etcd Buzz Client

使用 Buzz HTTP 客户端的 Etcd v3 PHP 客户端。

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://packagist.org/packages/chenbool/etcd-buzz-client)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 特性

- ✨ 完整支持 Etcd v3 API
- 🔄 服务注册与发现（含心跳）
- 📡 服务调用（负载均衡）
- 🔐 用户认证与权限管理
- 📦 租约管理
- 🎯 PHP 8.2+ 语法兼容

## 环境要求

| 依赖 | 版本 |
|------|------|
| PHP | 8.2+ |
| kriswallsmith/buzz | ^1.3 |
| nyholm/psr7 | ^1.8 |
| psr/http-client | ^1.0 |
| psr/http-message | ^1.0 \|\| ^2.0 |

## 安装

```bash
composer require chenbool/etcd-buzz-client
```

---

## 快速开始

```php
<?php
require 'vendor/autoload.php';

use chenbool\Etcd\Client;

// 创建客户端
$client = new Client('127.0.0.1:2379');
```

### 1. KV 操作

```php
// 写入
$client->put('key', 'value');

// 读取
$value = $client->get('key');

// 删除
$client->del('key');
```

### 2. 服务注册

```php
// 单个注册
$client->registerService('user-svc', '192.168.1.10', 8080, 30, ['version' => '1.0']);

// 批量注册
$client->registerServices([
    ['name' => 'order-svc', 'host' => '192.168.1.20', 'port' => 8080],
    ['name' => 'order-svc', 'host' => '192.168.1.21', 'port' => 8080],
]);
```

### 3. 服务调用

```php
// GET 请求
$response = $client->call('user-svc', 'GET', '/api/user/1');

// POST 请求
$response = $client->call('user-svc', 'POST', '/api/user', ['name' => 'tom']);

// 轮询负载均衡
$response = $client->call('user-svc', 'GET', '/api/users', [], 'round');
```

### 4. 服务发现

```php
// 发现指定服务
$services = $client->discoverService('user-svc');

// 获取所有服务
$allServices = $client->getAllServices();

// 健康检查
$health = $client->getServiceHealth('user-svc', '192.168.1.10', 8080);
```

### 5. 租约管理

```php
// 刷新单个租约
$client->refreshServiceLease('user-svc', '192.168.1.10', 8080, 60);

// 批量刷新
$client->refreshServiceLeases([
    ['name' => 'user-svc', 'host' => '192.168.1.10', 'port' => 8080],
]);

// 刷新所有服务租约
$client->refreshAllServicesLease(60);

// 心跳（阻塞运行）
// $client->heartbeat('user-svc', '192.168.1.10', 8080, 10);
```

### 6. 注销服务

```php
$client->deregisterService('user-svc', '192.168.1.10', 8080);
```

---

## API 文档

### KV 操作

| 方法 | 说明 |
|------|------|
| `put($key, $value, $options)` | 设置键值对 |
| `get($key, $options)` | 获取值 |
| `getAllKeys()` | 获取所有键 |
| `getKeysWithPrefix($prefix)` | 获取前缀匹配的键 |
| `del($key, $options)` | 删除键 |
| `compaction($revision)` | 压缩存储 |

### 租约 Lease

| 方法 | 说明 |
|------|------|
| `grant($ttl, $id)` | 创建租约 |
| `revoke($id)` | 撤销租约 |
| `keepAlive($id)` | 保持租约活跃 |
| `timeToLive($id, $keys)` | 获取租约信息 |

### 认证 Auth

| 方法 | 说明 |
|------|------|
| `authEnable()` / `authDisable()` | 启用/禁用认证 |
| `authenticate($user, $password)` | 用户认证 |
| `addUser()` / `getUser()` / `deleteUser()` | 用户管理 |
| `userList()` | 用户列表 |
| `changeUserPassword()` | 修改密码 |
| `addRole()` / `getRole()` / `deleteRole()` | 角色管理 |
| `roleList()` | 角色列表 |
| `grantUserRole()` / `revokeUserRole()` | 用户角色管理 |
| `grantRolePermission()` / `revokeRolePermission()` | 权限管理 |

### 服务注册与发现

| 方法 | 说明 |
|------|------|
| `registerService($name, $host, $port, $ttl, $metadata)` | 注册服务 |
| `registerServices($services)` | 批量注册 |
| `deregisterService($name, $host, $port)` | 注销服务 |
| `discoverService($name)` | 发现服务 |
| `getAllServices()` | 获取所有服务 |
| `getServiceHealth($name, $host, $port)` | 健康检查 |
| `refreshServiceLease($name, $host, $port, $ttl)` | 刷新租约 |
| `refreshServiceLeases($services)` | 批量刷新 |
| `refreshAllServicesLease($ttl)` | 刷新所有 |
| `heartbeat($name, $host, $port, $ttl)` | 心跳 |

### 服务调用

| 方法 | 说明 |
|------|------|
| `call($service, $method, $path, $data, $strategy)` | 调用服务 |

---

## 许可证

[MIT](LICENSE)
