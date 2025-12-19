<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\ProductCommentBundle\Entity\CommentLikeLog;

/**
 * 评论点赞日志管理控制器
 *
 * @extends AbstractCrudController<CommentLikeLog>
 */
#[AdminCrud(
    routePath: '/product-comment/like-log',
    routeName: 'product_comment_like_log',
)]
final class CommentLikeLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CommentLikeLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('评论点赞日志')
            ->setEntityLabelInPlural('评论点赞日志列表')
            ->setPageTitle('index', '评论点赞日志列表')
            ->setPageTitle('new', '新建点赞日志')
            ->setPageTitle('edit', '编辑点赞日志')
            ->setPageTitle('detail', '点赞日志详情')
            ->setHelp('index', '管理评论点赞和取消点赞的操作记录，用于统计分析和审计')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'commentId', 'memberId'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->getBasicFields();
        yield from $this->getAssociationFields();
        yield from $this->getStatusFields($pageName);
        yield from $this->getTimestampFields();
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getBasicFields(): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
            ->setHelp('点赞日志记录的唯一标识')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getAssociationFields(): iterable
    {
        yield IntegerField::new('commentId', '评论ID')
            ->setHelp('被点赞评论的ID')
            ->formatValue(fn ($value) => $value ? sprintf('#%d', $value) : '-')
        ;

        yield IntegerField::new('memberId', '点赞用户ID')
            ->setHelp('执行点赞操作的用户ID')
            ->formatValue(fn ($value) => $value ? sprintf('#%d', $value) : '-')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getStatusFields(string $pageName): iterable
    {
        yield ChoiceField::new('status', '点赞状态')
            ->setChoices([
                '取消点赞' => false,
                '点赞' => true,
            ])
            ->renderAsBadges([
                0 => 'secondary',
                1 => 'success',
            ])
            ->setHelp('true表示点赞，false表示取消点赞')
        ;

        // 在详情页添加布尔字段显示
        if (Crud::PAGE_DETAIL === $pageName) {
            yield BooleanField::new('status', '状态详情')
                ->renderAsSwitch(false)
                ->setHelp('布尔值：true=点赞，false=取消点赞')
            ;
        }
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getTimestampFields(): iterable
    {
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value ? $value->format('Y-m-d H:i:s') : '-')
            ->setHelp('记录创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ? $value->format('Y-m-d H:i:s') : '-')
            ->setHelp('记录最后更新时间')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(NumericFilter::new('commentId', '评论ID'))
            ->add(NumericFilter::new('memberId', '用户ID'))
            ->add(BooleanFilter::new('status', '点赞状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * @param FieldCollection $fields
     * @param FilterCollection $filters
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->orderBy('entity.id', 'DESC')
        ;
    }
}
