<?php

namespace Drupal\message_api\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\message\MessageInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * 渲染消息模板
 */
class MessageEntityNormalizer extends EntityNormalizer {

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = MessageInterface::class;

    public function __construct(EntityManagerInterface $entity_manager) {
        parent::__construct($entity_manager);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = []) {
        $data = parent::normalize($entity, $format, $context);
        /** @var MessageInterface $entity */
        $data['_content'] = $entity->getText();
        return $data;
    }
}