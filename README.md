# Product Comment Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/product-comment-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-comment-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)](#)
[![Coverage Status](https://img.shields.io/badge/coverage-WIP-yellow.svg?style=flat-square)](#)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/product-comment-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/product-comment-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/product-comment-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-comment-bundle)

A Symfony bundle for managing product comments, including hierarchical comments, 
ratings, likes, and administrative controls.

## Table of Contents

- [Quick Start](#quick-start)
- [Features](#features)
- [Installation](#installation)
- [Dependencies](#dependencies)
- [API Endpoints](#api-endpoints)
- [Entity Structure](#entity-structure)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

## Quick Start

### Register the Bundle

Add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ...
    ProductCommentBundle\ProductCommentBundle::class => ['all' => true],
];
```

### Configure Database

Run the database migrations to create the necessary tables:

```bash
php bin/console doctrine:migrations:migrate
```

### Basic Usage

#### Submit a Product Comment

```php
<?php

use ProductCommentBundle\Procedure\SubmitProductComment;

// Through JSON-RPC API
$procedure = new SubmitProductComment($orderProductRepository, $productCommentRepository, $requestStack, $security, $entityManager);
$procedure->orderProductId = 'order-product-123';
$procedure->content = 'Great product! Highly recommended.';
$procedure->images = ['image1.jpg', 'image2.jpg'];
$result = $procedure->execute();
```

#### Get Product Comments

```php
<?php

use ProductCommentBundle\Procedure\GetProductCommentList;

$procedure = new GetProductCommentList($security, $productCommentRepository, $spuService);
$procedure->productId = 'product-123';
$procedure->skuId = 'sku-456';  // optional
$procedure->rootParentId = '0'; // optional, defaults to '0' for top-level comments
$comments = $procedure->execute();
```

#### Like a Comment

```php
<?php

use ProductCommentBundle\Procedure\LikeProductComment;

$procedure = new LikeProductComment($productCommentRepository, $entityManager, $security);
$procedure->contentId = 'comment-123';
$result = $procedure->execute();
```

### Configuration

The bundle uses the following configuration structure:

```yaml
# config/packages/product_comment.yaml
product_comment:
    # Configuration will be added here as needed
```

## Features

- 🔗 Hierarchical comment system with parent-child relationships
- ⭐ Product rating system with numeric scores
- 👍 Like/dislike functionality with tracking
- 🖼️ Support for image and video attachments
- 🔐 User authentication and authorization
- 📱 IP tracking and client identification
- 👨‍💼 Administrative review and management
- 🎯 JSON-RPC API endpoints for frontend integration
- 🔧 EasyAdmin integration for backend management

## Installation

```bash
composer require tourze/product-comment-bundle
```

## Dependencies

### Required Dependencies

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher
- EasyAdmin Bundle 4.0 or higher

### Internal Dependencies

- `tourze/bundle-dependency` - Bundle dependency management
- `tourze/doctrine-indexed-bundle` - Doctrine indexing utilities
- `tourze/doctrine-snowflake-bundle` - Snowflake ID generation
- `tourze/json-rpc-core` - JSON-RPC core functionality
- `tourze/json-rpc-paginator-bundle` - Pagination support


## API Endpoints

The bundle provides the following JSON-RPC procedures:

- `SubmitProductComment` - Submit a new product comment
- `GetProductCommentList` - Retrieve paginated comment lists
- `LikeProductComment` - Like or unlike a comment (requires `contentId` parameter)
- `ReplyProductComment` - Reply to an existing comment (requires `contentId` and `content` parameters)

## Entity Structure

### ProductComment
- Hierarchical structure with parent/child relationships
- Support for ratings, likes, and multimedia content
- User authentication and IP tracking
- Administrative controls and state management

### ProductCommentLike
- Tracks user likes/dislikes on comments
- Prevents duplicate likes from same user

### CommentLikeLog
- Audit trail for like/unlike actions
- Tracks user actions and timestamps

## Advanced Usage

### Custom Comment Validation

You can extend the comment validation by implementing custom validators:

```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CustomCommentValidator
{
    public function validate($value, ExecutionContextInterface $context)
    {
        // Your custom validation logic here
        if (strlen($value) < 10) {
            $context->buildViolation('Comment must be at least 10 characters long')
                ->addViolation();
        }
    }
}
```

### Event Subscribers

The bundle dispatches events that you can listen to:

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
        // Handle comment creation
    }
}
```

### Custom Admin Controllers

Extend the provided admin controllers for custom functionality:

```php
use ProductCommentBundle\Controller\Admin\ProductCommentCrudController;

class CustomProductCommentCrudController extends ProductCommentCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        // Your custom actions
        return parent::configureActions($actions);
    }
}
```

## Security

### Authentication

The bundle requires authenticated users for comment operations. Ensure your application 
has proper authentication configured:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/admin/product-comment, roles: ROLE_ADMIN }
        - { path: ^/api/product-comment, roles: ROLE_USER }
```

### Authorization

User permissions are checked at the procedure level using `#[IsGranted]` attributes:

- Comment submission requires `IS_AUTHENTICATED_FULLY`
- Admin operations require `ROLE_ADMIN`
- Like operations require `IS_AUTHENTICATED_FULLY`

### Data Validation

All input data is validated using Symfony's validation component:

- Content length validation
- Image format validation
- User ownership verification
- Rate limiting protection

### IP Tracking

The bundle tracks IP addresses for security and auditing purposes. This data is used for:

- Preventing abuse
- Audit trails
- Geographic analytics

## Contributing

Please see [CONTRIBUTING.md](../../CONTRIBUTING.md) for details on how to contribute to this project.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.