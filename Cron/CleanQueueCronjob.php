<?php

namespace Grin\Module\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface;
use Magento\MysqlMq\Model\QueueManagement;
use Symfony\Component\Console\Output\OutputInterface;

class CleanQueueCronjob
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Cronjob Description
     *
     * @return void
     */
    public function execute(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $timeThirtyDaysInThePast = date("Y-m-d H:i:s", strtotime("-30 days", time()));
        $timeSixtyDaysInThePast = date("Y-m-d H:i:s", strtotime("-60 days", time()));
        $statusComplete = QueueManagement::MESSAGE_STATUS_COMPLETE;
        $statusError = QueueManagement::MESSAGE_STATUS_ERROR;

        $queueTable = $connection->getTableName("queue");
        $select = $connection->select()
            ->from("$queueTable", "id")
            ->where("name = ?", "grin_module_webhook");

        $result = $connection->fetchRow($select);
        $grinQueueId = $result["id"];

        $connection->delete("queue_message_status",
            "updated_at <= '$timeThirtyDaysInThePast' and status = $statusComplete and queue_id = $grinQueueId"
        );
        $connection->delete("queue_message_status",
            "updated_at <= '$timeSixtyDaysInThePast' and status = $statusError and queue_id = $grinQueueId"
        );
    }
}
