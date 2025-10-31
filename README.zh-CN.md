# 商品评论包

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/product-comment-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-comment-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)](#)
[![Coverage Status](https://img.shields.io/badge/coverage-WIP-yellow.svg?style=flat-square)](#)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/product-comment-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/product-comment-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/product-comment-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-comment-bundle)

一个用于管理商品评论的 Symfony 包，包括分层评论、评分、点赞和管理控制功能。

## 目录

- [快速开始](#快速开始)
- [功能特性](#功能特性)
- [安装](#安装)
- [依赖关系](#依赖关系)
- [API 端点](#api-端点)
- [实体结构](#实体结构)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [贡献](#贡献)
- [许可证](#许可证)

## 快速开始

### 注册包

将包添加到您的 `config/bundles.php`：

```php
<?php

return [
    // ...
    ProductCommentBundle\ProductCommentBundle::class => ['all' => true],
];
```

### 配置数据库

运行数据库迁移以创建必要的表：

```bash
php bin/console doctrine:migrations:migrate
```

### 基本使用

#### 提交商品评论

```php
<?php

use ProductCommentBundle\Procedure\SubmitProductComment;

// 通过 JSON-RPC API
$procedure = new SubmitProductComment($orderProductRepository, $productCommentRepository, $requestStack, $security, $entityManager);
$procedure->orderProductId = 'order-product-123';
$procedure->content = '很棒的产品！强烈推荐。';
$procedure->images = ['image1.jpg', 'image2.jpg'];
$result = $procedure->execute();
```

#### 获取商品评论

```php
<?php

use ProductCommentBundle\Procedure\GetProductCommentList;

$procedure = new GetProductCommentList($security, $productCommentRepository, $spuService);
$procedure->productId = 'product-123';
$procedure->skuId = 'sku-456';  // 可选参数
$procedure->rootParentId = '0'; // 可选参数，默认为'0'表示顶级评论
$comments = $procedure->execute();
```

#### 点赞评论

```php
<?php

use ProductCommentBundle\Procedure\LikeProductComment;

$procedure = new LikeProductComment($productCommentRepository, $entityManager, $security);
$procedure->contentId = 'comment-123';
$result = $procedure->execute();
```

### 配置

该包使用以下配置结构：

```yaml
# config/packages/product_comment.yaml
product_comment:
    # 根据需要在这里添加配置
```

## 功能特性

- 🔗 支持父子关系的分层评论系统
- ⭐ 数字评分的商品评级系统
- 👍 点赞/取消点赞功能，带有跟踪记录
- 🖼️ 支持图片和视频附件
- 🔐 用户认证和授权
- 📱 IP 跟踪和客户端识别
- 👨‍💼 管理员审核和管理
- 🎯 前端集成的 JSON-RPC API 端点
- 🔧 后台管理的 EasyAdmin 集成

## 安装

```bash
composer require tourze/product-comment-bundle
```

## 依赖关系

### 必需的依赖

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本
- EasyAdmin Bundle 4.0 或更高版本

### 内部依赖

- `tourze/bundle-dependency` - 包依赖管理
- `tourze/doctrine-indexed-bundle` - Doctrine 索引工具
- `tourze/doctrine-snowflake-bundle` - 雪花算法 ID 生成
- `tourze/json-rpc-core` - JSON-RPC 核心功能
- `tourze/json-rpc-paginator-bundle` - 分页支持

## API 端点

该包提供以下 JSON-RPC 程序：

- `SubmitProductComment` - 提交新的商品评论
- `GetProductCommentList` - 获取分页的评论列表
- `LikeProductComment` - 点赞或取消点赞评论（需要 `contentId` 参数）
- `ReplyProductComment` - 回复现有评论（需要 `contentId` 和 `content` 参数）

## 实体结构

### ProductComment
- 支持父子关系的分层结构
- 支持评分、点赞和多媒体内容
- 用户认证和 IP 跟踪
- 管理控制和状态管理

### ProductCommentLike
- 跟踪用户对评论的点赞/取消点赞
- 防止同一用户重复点赞

### CommentLikeLog
- 点赞/取消点赞操作的审计日志
- 跟踪用户操作和时间戳

## 高级用法

### 自定义评论验证

您可以通过实现自定义验证器来扩展评论验证：

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomCommentValidator
{
    public function validate($value, ExecutionContextInterface $context)
    {
        // 您的自定义验证逻辑
        if (strlen($value) < 10) {
            $context->buildViolation('评论内容至少需要10个字符')
                ->addViolation();
        }
    }
}
```

### 事件订阅器

该包会分发事件，您可以监听这些事件：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ProductCommentBundle\Event\CommentCreatedEvent;

class CommentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            CommentCreatedEvent::class => 'onCommentCreated',
        ];
    }
    
    public function onCommentCreated(CommentCreatedEvent $event)
    {
        // 处理评论创建事件
    }
}
```

### 自定义管理控制器

扩展提供的管理控制器以实现自定义功能：

```php
use ProductCommentBundle\Controller\Admin\ProductCommentCrudController;

class CustomProductCommentCrudController extends ProductCommentCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        // 您的自定义操作
        return parent::configureActions($actions);
    }
}
```

## 安全性

### 身份验证

该包要求用户进行身份验证才能执行评论操作。确保您的应用程序配置了正确的身份验证：

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/admin/product-comment, roles: ROLE_ADMIN }
        - { path: ^/api/product-comment, roles: ROLE_USER }
```

### 授权

在程序级别使用 `#[IsGranted]` 属性检查用户权限：

- 评论提交需要 `IS_AUTHENTICATED_FULLY`
- 管理操作需要 `ROLE_ADMIN`
- 点赞操作需要 `IS_AUTHENTICATED_FULLY`

### 数据验证

所有输入数据都使用 Symfony 的验证组件进行验证：

- 内容长度验证
- 图片格式验证
- 用户所有权验证
- 限频保护

### IP 跟踪

该包会跟踪 IP 地址用于安全和审计目的。这些数据用于：

- 防止滥用
- 审计日志
- 地理位置分析

## 贡献

请查看 [CONTRIBUTING.md](../../CONTRIBUTING.md) 了解如何为此项目做出贡献的详细信息。

## 许可证

MIT 许可证 (MIT)。请查看 [许可证文件](LICENSE) 获取更多信息。