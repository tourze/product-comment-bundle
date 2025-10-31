# Product Comment Bundle - 后台管理功能

## 功能概述

Product Comment Bundle 提供了完整的产品评论后台管理功能，包括评论审核、精选管理、点赞记录查看等功能。

## 管理功能

### 1. 产品评论管理

**路由**: `/admin/product-comment/comment`

#### 功能特性

- **评论列表展示**
  - 显示所有产品评论信息
  - 支持按评论内容、用户、商品等条件搜索
  - 支持多条件筛选（状态、类型、评分等）
  - 关联显示 SPU/SKU 商品信息
  - 显示评论用户信息

- **评论审核功能**
  - 批准评论：将评论状态设为"已公开"
  - 拒绝评论：将评论状态设为"不通过"
  - 支持批量审核操作

- **精选管理**
  - 设为精选：标记优质评论
  - 取消精选：取消精选标记
  - 精选评论可在前端特殊展示

- **评论详情**
  - 查看完整评论内容
  - 查看评论图片和视频
  - 查看关联的订单信息
  - 查看评论层级关系（父级、根级）

#### 评论状态

```php
enum CommentStateEnum: string
{
    case PENDING = 'pending';    // 待审核
    case APPROVED = 'approved';  // 已公开
    case REJECTED = 'rejected';  // 不通过
    case POSTPONED = 'postponed'; // 不显示
}
```

#### 评论类型

- **0 - 首评**：用户的第一次评论
- **1 - 追评**：用户的追加评论
- **2 - 回复**：对其他评论的回复

### 2. 评论点赞管理

**路由**: `/admin/product-comment/like`

#### 功能特性

- **点赞记录列表**
  - 显示所有点赞记录
  - 关联显示评论内容
  - 显示点赞用户信息
  - 查看点赞状态（已点赞/已取消）

- **数据查看**
  - 查看点赞详情
  - 查看创建/更新时间
  - 查看操作IP地址

- **管理限制**
  - 点赞记录为只读数据
  - 不支持新建和编辑操作
  - 仅支持查看和删除

## 菜单结构

管理菜单会自动注册到 EasyAdmin 后台：

```
商品管理
└── 评论管理
    ├── 产品评论
    └── 评论点赞
```

## 自定义操作

### 批准评论
```php
// 路由: {entityId}/approve
// 将评论状态设为 APPROVED
$entity->setState(CommentStateEnum::APPROVED);
```

### 拒绝评论
```php
// 路由: {entityId}/reject
// 将评论状态设为 REJECTED
$entity->setState(CommentStateEnum::REJECTED);
```

### 设为精选
```php
// 路由: {entityId}/setGoods
// 标记为精选评论
$entity->setIsGoods(1);
```

### 取消精选
```php
// 路由: {entityId}/unsetGoods
// 取消精选标记
$entity->setIsGoods(0);
```

## 筛选器

### 产品评论筛选器

- **ID**: 按评论ID搜索
- **SPU商品**: 按商品SPU筛选
- **SKU规格**: 按商品SKU筛选
- **评论用户**: 按用户筛选
- **评论类型**: 首评/追评/回复
- **审核状态**: 待审核/已公开/不通过/不显示
- **评分**: 按评分范围筛选
- **点赞数**: 按点赞数筛选
- **是否精选**: 筛选精选评论
- **管理员回复**: 筛选管理员回复
- **创建时间**: 按时间范围筛选
- **评论内容**: 按内容关键词搜索
- **客户端IP**: 按IP地址搜索

### 评论点赞筛选器

- **ID**: 按记录ID搜索
- **评论**: 按关联评论筛选
- **点赞用户**: 按用户筛选
- **点赞状态**: 已点赞/已取消
- **创建时间**: 按时间范围筛选
- **创建IP**: 按IP地址搜索

## 性能优化

### 查询优化

控制器通过重写 `createIndexQueryBuilder` 方法优化了查询性能：

```php
public function createIndexQueryBuilder(...): QueryBuilder
{
    return parent::createIndexQueryBuilder(...)
        ->select('entity', 'spu', 'sku', 'fromUser')
        ->leftJoin('entity.spu', 'spu')
        ->leftJoin('entity.sku', 'sku')
        ->leftJoin('entity.fromUser', 'fromUser')
        ->orderBy('entity.id', 'DESC');
}
```

这样可以：
- 减少 N+1 查询问题
- 一次性加载关联数据
- 提高列表页加载速度

## 权限控制

后台管理功能需要管理员权限，建议配置以下角色：

- **ROLE_COMMENT_VIEWER**: 查看评论权限
- **ROLE_COMMENT_MODERATOR**: 评论审核权限
- **ROLE_COMMENT_ADMIN**: 完整管理权限

## 使用建议

1. **定期审核**：建议每天定期审核新评论，及时处理不当内容
2. **精选管理**：选择高质量评论设为精选，提升商品转化率
3. **数据分析**：定期分析评论数据，了解用户反馈
4. **批量操作**：对于大量评论，使用筛选器批量处理

## 注意事项

1. 删除评论会同时删除相关的点赞记录（CASCADE）
2. 评论状态修改会立即生效，请谨慎操作
3. 精选评论会在前端特殊展示，请选择优质内容
4. IP地址信息仅用于安全审计，请遵守隐私保护规定

## 扩展开发

如需扩展管理功能，可以：

1. 继承现有控制器添加新操作
2. 通过事件监听器扩展功能
3. 自定义字段显示格式
4. 添加新的筛选条件

## 技术栈

- **EasyAdmin Bundle**: 后台管理框架
- **Doctrine ORM**: 数据持久化
- **Symfony Form**: 表单处理
- **AdminAction**: 自定义操作路由

## 相关文档

- [EasyAdmin 官方文档](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- [产品评论实体定义](src/Entity/ProductComment.php)
- [评论点赞实体定义](src/Entity/ProductCommentLike.php)