# Etcd Buzz Client

使用 Buzz HTTP 客户端的 Etcd v3 PHP 客户端。

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://packagist.org/packages/chenbool/etcd-buzz-client)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 特性

- ✨ 完整支持 Etcd v3 API
- 🔄 服务注册与发现（含心跳）
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

## 快速开始

```php
<?php
require 'vendor/autoload.php';

use chenbool\Etcd\Client;

// 1. 创建客户端
$client = new Client('127.0.0.1:2379');

// 2. 基础 KV 操作
$client->put('name', 'chenbool');           // 写入
$value = $client->get('name');               // 读取 → ['key' => 'name', 'value' => 'chenbool']
$client->del('name');                        // 删除

// 3. 服务注册
$client->registerService('user-svc', '192.168.1.10', 8080, 30, ['version' => '1.0']);
// 参数: 服务名, 主机, 端口, TTL(秒), 元数据

// 4. 服务发现
$services = $client->discoverService('user-svc');
// 返回: [['key' => '...', 'value' => '{"host":"...","port":8080,...}'], ...]

// 5. 健康检查
$health = $client->getServiceHealth('user-svc', '192.168.1.10', 8080);
// 返回: ['healthy' => true, 'service' => [...]]

// 6. 刷新租约（续期）
$client->refreshServiceLease('user-svc', '192.168.1.10', 8080, 60);

// 7. 注销服务
$client->deregisterService('user-svc', '192.168.1.10', 8080);

// 8. 心跳（保持服务在线，阻塞运行）
// $client->heartbeat('user-svc', '192.168.1.10', 8080, 10);
```

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
| `deregisterService($name, $host, $port)` | 注销服务 |
| `discoverService($name)` | 发现服务 |
| `getServiceHealth($name, $host, $port)` | 健康检查 |
| `refreshServiceLease($name, $host, $port, $ttl)` | 刷新租约 |
| `heartbeat($name, $host, $port, $ttl)` | 心跳（自动续期） |

## 许可证

[MIT](LICENSE)
