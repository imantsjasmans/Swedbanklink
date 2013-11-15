<?php
$installer = $this;
$installer->startSetup();
try {
	$installer->run("
	DROP TABLE IF EXISTS {$this->getTable('swedbanklink/transaction')};
    CREATE TABLE {$this->getTable('swedbanklink/transaction')} (
        `transaction_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `vk_service` VARCHAR(4) default NULL,
     	`vk_version` VARCHAR(3) default NULL,
     	`vk_snd_id` VARCHAR(10) default NULL,
     	`vk_rec_id` VARCHAR(10) default NULL,
     	`vk_stamp` VARCHAR(20) default NULL,
     	`vk_t_no` VARCHAR(5) default NULL,
     	`vk_amount` VARCHAR(17) default NULL,
     	`vk_curr` VARCHAR(3) default NULL,
     	`vk_rec_acc` VARCHAR(34) default NULL,
     	`vk_rec_name` VARCHAR(70) default NULL,
     	`vk_snd_acc` VARCHAR(34) default NULL,
     	`vk_snd_name` VARCHAR(70) default NULL,
     	`vk_ref` VARCHAR(20) default NULL,
     	`vk_msg` TEXT default NULL,
     	`vk_t_date` VARCHAR(10) default NULL,
        PRIMARY KEY ( `transaction_id` )
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
	");
} catch (Exception $e){}
$installer->endSetup();