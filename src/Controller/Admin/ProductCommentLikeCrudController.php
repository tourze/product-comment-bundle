<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\ProductCommentBundle\Entity\ProductCommentLike;

/**
 * 产品评论点赞管理控制器
 *
 * @extends AbstractCrudController<ProductCommentLike>
 */
#[AdminCrud(
    routePath: '/product-comment/like',
    routeName: 'product_comment_like',
)]
final class ProductCommentLikeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductCommentLike::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('评论点赞')
            ->setEntityLabelInPlural('评论点赞列表')
            ->setPageTitle('index', '评论点赞列表')
            ->setPageTitle('new', '新建评论点赞')
            ->setPageTitle('edit', '编辑评论点赞')
            ->setPageTitle('detail', '评论点赞详情')
            ->setHelp('index', '管理用户对评论的点赞记录')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        // 评论关联
        yield AssociationField::new('productComment', '评论')
            ->formatValue(function ($value, $entity) {
                if (!$value) {
                    return '-';
                }

                $content = $value->getContent();
                if (!$content) {
                    return sprintf('#%s (无内容)', $value->getId());
                }

                $shortContent = mb_strlen($content) > 30 ? mb_substr($content, 0, 30) . '...' : $content;

                return sprintf('#%s %s', $value->getId(), $shortContent);
            })
        ;

        // 用户关联
        yield AssociationField::new('user', '点赞用户')
            ->formatValue(function ($value, $entity) {
                if (!$value) {
                    return '-';
                }

                if (is_object($value) && method_exists($value, 'getUsername')) {
                    return $value->getUsername();
                }
                if (is_object($value) && method_exists($value, 'getUserIdentifier')) {
                    return $value->getUserIdentifier();
                }

                return '-';
            })
        ;

        // 状态字段
        yield ChoiceField::new('status', '点赞状态')
            ->setChoices([
                '已取消' => 0,
                '已点赞' => 1,
            ])
            ->renderAsBadges([
                0 => 'secondary',
                1 => 'success',
            ])
        ;

        // IP 地址
        yield TextField::new('createdFromIp', '创建IP')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ?? '-';
            })
        ;

        yield TextField::new('updatedFromIp', '更新IP')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ?? '-';
            })
        ;

        // 时间字段
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '-';
            })
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '-';
            })
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(EntityFilter::new('productComment', '评论'))
            ->add(EntityFilter::new('user', '点赞用户'))
            ->add(BooleanFilter::new('status', '点赞状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(TextFilter::new('createdFromIp', '创建IP'))
        ;
    }

    /**
     * @param FieldCollection $fields
     * @param FilterCollection $filters
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity', 'productComment', 'user')
            ->leftJoin('entity.productComment', 'productComment')
            ->leftJoin('entity.user', 'user')
            ->orderBy('entity.id', 'DESC')
        ;
    }
}
