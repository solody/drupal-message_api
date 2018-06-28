<?php

namespace Drupal\message_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\message\Entity\Message;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "message_api_query_message_list",
 *   label = @Translation("Query Message list"),
 *   uri_paths = {
 *     "create" = "/api/rest/message/query-message-list"
 *   }
 * )
 */
class QueryMessageList extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a new MessageList object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('message_api'),
            $container->get('current_user')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @param $data
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     */
    public function post($data)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $page = 0;
        $page_length = 10;
        if (isset($data['page'])) $page = (int)$data['page'];
        if (isset($data['page_length'])) $page_length = (int)$data['page_length'];

        $query = \Drupal::entityTypeManager()->getStorage('message')->getQuery();
        $query->condition('uid', $this->currentUser->id())
            ->sort('created', 'DESC')
            ->range($page * $page_length, $page_length);

        $message_ids = $query->execute();

        $messages = [];
        if (count($message_ids)) {
            $messages = Message::loadMultiple($message_ids);
        }

        $response = new ResourceResponse(array_values($messages), 200);
        $response->getCacheableMetadata()
            ->setCacheTags([
                'user',
                'entity:message'
            ])
            ->setCacheMaxAge(0);

        return $response;
    }

}
