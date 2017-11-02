CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_order`(
     `id_order`        INT(11) UNSIGNED NOT NULL PRIMARY KEY,
     `id_transaction`  VARCHAR(255) NOT NULL,
     `contract_number` VARCHAR(255) NOT NULL,
     `payment_status`  VARCHAR(255) NOT NULL,
     `mode`            VARCHAR(255) NOT NULL,
     `amount`          INT(11) UNSIGNED NOT NULL,
     `currency`        INT(11) UNSIGNED NOT NULL,
     `payment_by`      VARCHAR(255) NOT NULL,
     KEY ( `id_transaction` )
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_wallet`(
     `id_customer` INT(11) NOT NULL,
     `id_wallet`   VARCHAR(30) NOT NULL,
     UNIQUE (`id_customer`, `id_wallet`)
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_card` (
	`type` VARCHAR(12) NOT NULL,
	`contract` VARCHAR(12) NOT NULL,
	`label` VARCHAR(50) NOT NULL,
  `logo` VARCHAR(255) NOT NULL,
	`primary` TINYINT(3) UNSIGNED NULL DEFAULT 0,
	`secondary` TINYINT(3) UNSIGNED NULL DEFAULT 0,
	`id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`id_shop_group` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	`position_primary` TINYINT(3) UNSIGNED NULL DEFAULT 0,
	`position_secondary` TINYINT(3) UNSIGNED NULL DEFAULT 0,
	PRIMARY KEY (`type`, `contract`, `id_shop`),
	KEY `id_shop` (`id_shop`),
	KEY `id_shop_group` (`id_shop_group`),
	KEY `type` (`type`),
	KEY `contract` (`contract`),
	KEY `label` (`label`),
  KEY `logo` (`logo`),
	KEY `secondary` (`secondary`),
	KEY `primary_2` (`primary`),
	KEY `position_primary` (`position_primary`),
	KEY `position_secondary` (`position_secondary`)
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_subscribe` (
     `id_payline_subscribe` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
     `id_cart`              INT(11) NOT NULL,
     `paymentrecordid`      INT(11) NOT NULL,
     `id_customer`          INT(11) NOT NULL,
     `cardind`              INT(11) NOT NULL,
     `contractnumber`       VARCHAR(255) NOT NULL,
     `periodicity`          INT(2) NOT NULL,
     KEY ( `id_cart` ),
    KEY ( `paymentrecordid` ),  
      KEY ( `id_customer` )
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_subscribe_order` (
     `id_payline_subscribe_order` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
     `id_payline_subscribe`       INT(11) NOT NULL,
     `id_order`                   INT(11) NOT NULL,
     KEY ( `id_payline_subscribe` ),
     KEY ( `id_order` )
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_subscribe_state` (
     `id_payline_subscribe_state` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
     `id_payline_subscribe`       INT(11) NOT NULL,
     `status`                     INT(1) NOT NULL,
     `date_add`					  DATETIME,
     KEY ( `id_payline_subscribe` )
) DEFAULT charset=utf8;

CREATE TABLE IF NOT EXISTS `%%PREFIX%%payline_token`(
     `id_cart` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
     `token`   VARCHAR(255) NOT NULL
) DEFAULT charset=utf8;

CREATE TABLE `%%PREFIX%%payline_dirdebit`(
     `id_direct_debit`   int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `id_cart_origin`    int(10) UNSIGNED NOT NULL COMMENT 'Cart ID',
     `id_customer`       int(10) UNSIGNED NOT NULL COMMENT 'Customer ID',
     `id_order`          int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'NULL if no order is associated yet',
     `payment_record_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'Payment record ID',
     `contract_number`   varchar(255) NOT NULL COMMENT 'Contract number',
     `date_debit`        date NOT NULL COMMENT 'Date to proceed the debit',
     `date_paid`         datetime NULL DEFAULT NULL COMMENT 'Date when the payment has been processed',
     `date_call`         datetime NULL DEFAULT NULL COMMENT 'Date when call to doScheduledWalletPayment has been made',
     `paid`              tinyint(3) UNSIGNED NULL DEFAULT 0 COMMENT 'Yes/No, order has been paid',
     PRIMARY KEY (`id_direct_debit`),
     KEY `id_cart` (`id_cart_origin`),
     KEY `id_order` (`id_order`),
     KEY `date_debit` (`date_debit`),
     KEY `payment_record_id` (`payment_record_id`)
) DEFAULT charset=utf8;