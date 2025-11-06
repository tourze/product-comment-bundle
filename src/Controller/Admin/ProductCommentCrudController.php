<?php

declare(strict_types=1);

namespace Tourze\ProductCommentBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\ProductCommentBundle\Entity\ProductComment;
use Tourze\ProductCommentBundle\Enum\CommentStateEnum;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * 产品评论管理控制器
 *
 * @extends AbstractCrudController<ProductComment>
 */
#[AdminCrud(
    routePath: '/product-comment/comment',
    routeName: 'product_comment_comment',
)]
final class ProductCommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductComment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('产品评论')
            ->setEntityLabelInPlural('产品评论列表')
            ->setPageTitle('index', '产品评论列表')
            ->setPageTitle('new', '新建产品评论')
            ->setPageTitle('edit', '编辑产品评论')
            ->setPageTitle('detail', '产品评论详情')
            ->setHelp('index', '管理产品评论信息，包括审核、精选、回复等操作')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'content', 'clientIp'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->getBasicFields();
        yield from $this->getProductAssociationFields();
        yield from $this->getOrderAssociationFields();
        yield from $this->getUserAssociationFields();
        yield from $this->getHierarchyFields();
        yield from $this->getContentFields();
        yield from $this->getStateFields();
        yield from $this->getMetricsFields();
        yield from $this->getFlagFields();
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
        ;

        yield TextField::new('clientIp', '客户端IP')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getProductAssociationFields(): iterable
    {
        yield AssociationField::new('spu', 'SPU商品')
            ->setFormTypeOptions([
                'class' => Spu::class,
                'choice_label' => 'title',
            ])
            ->formatValue(fn ($value) => $value ? sprintf('#%s %s', $value->getId(), $value->getTitle()) : '-')
        ;

        yield AssociationField::new('sku', 'SKU规格')
            ->setFormTypeOptions([
                'class' => Sku::class,
                'choice_label' => 'title',
            ])
            ->formatValue(fn ($value) => $value ? sprintf('#%s %s', $value->getId(), $value->getTitle()) : '-')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getOrderAssociationFields(): iterable
    {
        yield AssociationField::new('contract', '订单合同')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ? sprintf('#%s', $value->getId()) : '-')
        ;

        yield AssociationField::new('orderProduct', '订单商品')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ? sprintf('#%s', $value->getId()) : '-')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getUserAssociationFields(): iterable
    {
        yield AssociationField::new('fromUser', '评论用户')
            ->formatValue(fn ($value) => $this->formatUserValue($value))
        ;

        yield AssociationField::new('toUser', '回复用户')
            ->hideOnIndex()
            ->formatValue(fn ($value) => $this->formatUserValue($value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getHierarchyFields(): iterable
    {
        yield IntegerField::new('parentId', '父级ID')
            ->hideOnIndex()
            ->setHelp('直接上级评论的ID，0表示顶级评论')
        ;

        yield IntegerField::new('rootParentId', '根父级ID')
            ->hideOnIndex()
            ->setHelp('最顶级评论的ID，用于查询评论树')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getContentFields(): iterable
    {
        yield ChoiceField::new('topicType', '评论类型')
            ->setChoices(['首评' => 0, '追评' => 1, '回复' => 2])
            ->renderAsBadges([0 => 'primary', 1 => 'info', 2 => 'secondary'])
        ;

        yield TextareaField::new('content', '评论内容')
            ->setMaxLength(100)
            ->formatValue(fn ($value) => $value ? (mb_strlen($value) > 100 ? mb_substr($value, 0, 100) . '...' : $value) : '-')
        ;

        yield ArrayField::new('images', '评论图片')
            ->hideOnIndex()
            ->hideOnForm()
            ->setHelp('评论附带的图片列表')
        ;

        yield TextField::new('video', '评论视频')
            ->hideOnIndex()
            ->hideOnForm()
            ->setHelp('评论附带的视频URL')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getStateFields(): iterable
    {
        yield ChoiceField::new('state', '审核状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => CommentStateEnum::class])
            ->formatValue(fn ($value) => $value instanceof CommentStateEnum ? $value->getLabel() : '')
            ->renderAsBadges([
                CommentStateEnum::PENDING->value => 'warning',
                CommentStateEnum::APPROVED->value => 'success',
                CommentStateEnum::REJECTED->value => 'danger',
                CommentStateEnum::POSTPONED->value => 'secondary',
            ])
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getMetricsFields(): iterable
    {
        yield IntegerField::new('rateNum', '评分')
            ->setHelp('评分范围：0-100')
            ->formatValue(fn ($value) => sprintf('%d/100', $value))
        ;

        yield IntegerField::new('likeNum', '点赞数')
            ->formatValue(fn ($value) => sprintf('%d 次', $value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getFlagFields(): iterable
    {
        yield ChoiceField::new('isGoods', '是否精选')
            ->setChoices(['否' => 0, '是' => 1])
            ->renderAsBadges([0 => 'secondary', 1 => 'success'])
        ;

        yield ChoiceField::new('isAdmin', '管理员回复')
            ->setChoices(['否' => 0, '是' => 1])
            ->renderAsBadges([0 => 'secondary', 1 => 'primary'])
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getTimestampFields(): iterable
    {
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value ? $value->format('Y-m-d H:i:s') : '-')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value ? $value->format('Y-m-d H:i:s') : '-')
        ;
    }

    private function formatUserValue(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }

        if (is_object($value) && method_exists($value, 'getUsername')) {
            return $value->getUsername();
        }
        if (is_object($value) && method_exists($value, 'getUserIdentifier')) {
            return $value->getUserIdentifier();
        }

        return '-';
    }

    public function configureActions(Actions $actions): Actions
    {
        // 批准评论操作
        $approveAction = Action::new('approve', '批准')
            ->linkToCrudAction('approveComment')
            ->setCssClass('btn btn-success btn-sm')
            ->setIcon('fa fa-check')
            ->displayIf(static function ($entity) {
                return CommentStateEnum::APPROVED !== $entity->getState();
            })
        ;

        // 拒绝评论操作
        $rejectAction = Action::new('reject', '拒绝')
            ->linkToCrudAction('rejectComment')
            ->setCssClass('btn btn-danger btn-sm')
            ->setIcon('fa fa-times')
            ->displayIf(static function ($entity) {
                return CommentStateEnum::REJECTED !== $entity->getState();
            })
        ;

        // 设为精选操作
        $setGoodsAction = Action::new('setGoods', '设为精选')
            ->linkToCrudAction('setAsGoods')
            ->setCssClass('btn btn-warning btn-sm')
            ->setIcon('fa fa-star')
            ->displayIf(static function ($entity) {
                return 0 === $entity->getIsGoods();
            })
        ;

        // 取消精选操作
        $unsetGoodsAction = Action::new('unsetGoods', '取消精选')
            ->linkToCrudAction('unsetAsGoods')
            ->setCssClass('btn btn-secondary btn-sm')
            ->setIcon('fa fa-star-o')
            ->displayIf(static function ($entity) {
                return 1 === $entity->getIsGoods();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $setGoodsAction)
            ->add(Crud::PAGE_INDEX, $unsetGoodsAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $setGoodsAction)
            ->add(Crud::PAGE_DETAIL, $unsetGoodsAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 构建状态选项
        $stateChoices = [];
        foreach (CommentStateEnum::cases() as $case) {
            $stateChoices[$case->getLabel()] = $case->value;
        }

        // 构建评论类型选项
        $topicTypeChoices = [
            '首评' => 0,
            '追评' => 1,
            '回复' => 2,
        ];

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(EntityFilter::new('spu', 'SPU商品'))
            ->add(EntityFilter::new('sku', 'SKU规格'))
            ->add(EntityFilter::new('fromUser', '评论用户'))
            ->add(ChoiceFilter::new('topicType', '评论类型')->setChoices($topicTypeChoices))
            ->add(ChoiceFilter::new('state', '审核状态')->setChoices($stateChoices))
            ->add(NumericFilter::new('rateNum', '评分'))
            ->add(NumericFilter::new('likeNum', '点赞数'))
            ->add(BooleanFilter::new('isGoods', '是否精选'))
            ->add(BooleanFilter::new('isAdmin', '管理员回复'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(TextFilter::new('content', '评论内容'))
            ->add(TextFilter::new('clientIp', '客户端IP'))
        ;
    }

    /**
     * @param FieldCollection $fields
     * @param FilterCollection $filters
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity', 'spu', 'sku', 'fromUser')
            ->leftJoin('entity.spu', 'spu')
            ->leftJoin('entity.sku', 'sku')
            ->leftJoin('entity.fromUser', 'fromUser')
            ->orderBy('entity.id', 'DESC')
        ;
    }

    /**
     * 批准评论
     */
    #[AdminAction(routePath: '{entityId}/approve', routeName: 'product_comment_approve')]
    public function approveComment(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ProductComment);

        $entity->setState(CommentStateEnum::APPROVED);

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $doctrine->getManager()->flush();

        $this->addFlash('success', sprintf('评论 #%s 已批准公开显示', $entity->getId()));

        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 拒绝评论
     */
    #[AdminAction(routePath: '{entityId}/reject', routeName: 'product_comment_reject')]
    public function rejectComment(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ProductComment);

        $entity->setState(CommentStateEnum::REJECTED);

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $doctrine->getManager()->flush();

        $this->addFlash('danger', sprintf('评论 #%s 已被拒绝', $entity->getId()));

        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 设为精选
     */
    #[AdminAction(routePath: '{entityId}/setGoods', routeName: 'product_comment_set_goods')]
    public function setAsGoods(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ProductComment);

        $entity->setIsGoods(1);

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $doctrine->getManager()->flush();

        $this->addFlash('success', sprintf('评论 #%s 已设为精选', $entity->getId()));

        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 取消精选
     */
    #[AdminAction(routePath: '{entityId}/unsetGoods', routeName: 'product_comment_unset_goods')]
    public function unsetAsGoods(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ProductComment);

        $entity->setIsGoods(0);

        /** @var \Doctrine\Persistence\ManagerRegistry $doctrine */
        $doctrine = $this->container->get('doctrine');
        $doctrine->getManager()->flush();

        $this->addFlash('info', sprintf('评论 #%s 已取消精选', $entity->getId()));

        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }
}
