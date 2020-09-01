<?php
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('vs7_canonical')} (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `url_rewrite_id` INT(11) NOT NULL,
  `canonical` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();