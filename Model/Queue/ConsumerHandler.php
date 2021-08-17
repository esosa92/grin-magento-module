<?php

declare(strict_types=1);

namespace Grin\Module\Model\Queue;

use Grin\Module\Model\Queue\Resource\ResponseHandler;
use Grin\Module\Api\GrinServiceInterface;
use Grin\Module\Api\Data\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @see \Magento\Framework\MessageQueue\Consumer
 */
class ConsumerHandler
{
    /**
     * @var GrinServiceInterface
     */
    private $grinService;

    /**
     * @var ResponseHandler
     */
    private $responseHandler;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param GrinServiceInterface $grinService
     * @param ResponseHandler $responseHandler
     * @param SerializerInterface $serializer
     */
    public function __construct(
        GrinServiceInterface $grinService,
        ResponseHandler $responseHandler,
        SerializerInterface $serializer
    ) {
        $this->grinService = $grinService;
        $this->responseHandler = $responseHandler;
        $this->serializer = $serializer;
    }

    /**
     * @param RequestInterface $request
     * @throws LocalizedException
     *
     * @return ?string
     */
    public function process(RequestInterface $request): ?string
    {
        $data = $this->serializer->unserialize($request->getSerializedData());
        $topic = $request->getTopic();

        $result = $this->grinService->send($topic, $data);

        if (is_string($result) && $request->getMessageStatusId()) {
            $this->responseHandler->addResponse([
                'id' => $request->getMessageStatusId(),
                'response' => $result,
            ]);
        }

        if ($this->grinService->hasErrors()) {
            throw new LocalizedException(__('Grin service webhook has failed with message: %1', $result));
        }

        return $result;
    }
}
