CREATE DATABASE cnab240 CHARSET = UTF8 COLLATE = utf8_general_ci;
USE cnab240;

CREATE TABLE `fulladdress` (
    `id`            int(10)         UNSIGNED NOT NULL AUTO_INCREMENT,
    `county`        int(10)         UNSIGNED NOT NULL, -- Foreign to database Address: `counties` (`id`)
    `neighborhood`  varchar(15)     NOT NULL,
    `place`         varchar(40)     NOT NULL,
    `number`        varchar(40)     NOT NULL,
    `zipcode`       varchar(8)      NOT NULL,
    `detail`        varchar(40)     NOT NULL DEFAULT '',
    `stamp`         timestamp       NOT NULL, -- to check changes
    PRIMARY KEY (`id`)
);

CREATE TABLE `banks` (
    `id`            int(10)         UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`          char(3)         NOT NULL,
    `name`          varchar(30)     NOT NULL,
    `tax`           decimal(6,4)    NOT NULL, -- used in the bank billet
    PRIMARY KEY (`id`)
);

CREATE TABLE `assignors` (
    `id`            int(10)         UNSIGNED NOT NULL,
    `bank`          int(10)         UNSIGNED NOT NULL,
    `document`      varchar(14)     NOT NULL,
    `name`          varchar(30)     NOT NULL,
    `covenant`      char(20)        NOT NULL,
    `agency`        char(5)         NOT NULL,
    `agency_cd`     char(1)         NOT NULL,
    `account`       char(12)        NOT NULL,
    `account_cd`    char(1)         NOT NULL,
    `edi7`          char(6)         NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`bank`) REFERENCES `banks` (`id`)
);

CREATE TABLE `payers` (
    `id`            int(10)         UNSIGNED NOT NULL AUTO_INCREMENT,
    `address`       int(10)         UNSIGNED NOT NULL,
    `document`      varchar(14)     NOT NULL,
    `name`          varchar(40)     NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`address`) REFERENCES `fulladdress` (`id`)
);

CREATE TABLE `titles` (
    `id`            int(10)         UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignor`      int(10)         UNSIGNED NOT NULL,
    `payer`         int(10)         UNSIGNED NOT NULL,
    `guarantor`     int(10)         UNSIGNED,
    `onum`          int(10)         UNSIGNED NOT NULL,
    `status`        tinyint(1)      UNSIGNED NOT NULL DEFAULT 0,
    `wallet`        tinyint(1)      UNSIGNED NOT NULL DEFAULT 1,
    `doc_type`      char(1)         NOT NULL,
    `kind`          tinyint(2)      UNSIGNED NOT NULL,
    `specie`        tinyint(2)      UNSIGNED NOT NULL,
    `value`         decimal(17,4)   NOT NULL,
    `iof`           decimal(17,4)   NOT NULL,
    `rebate`        decimal(17,4)   NOT NULL,
    `fine_type`     tinyint(1)      NOT NULL DEFAULT 3,
    `fine_date`     date,
    `fine_value`    decimal(17,4),
    `discount_type` tinyint(1)      NOT NULL DEFAULT 3,
    `discount_date` date,
    `discount_value` decimal(17,4),
    `description`   varchar(25)     NOT NULL,
    `due`           date            NOT NULL,
    `stamp`         timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `update`        datetime,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`assignor`) REFERENCES `assignors` (`id`),
    FOREIGN KEY (`payer`) REFERENCES `payers` (`id`),
    FOREIGN KEY (`guarantor`) REFERENCES `payers` (`id`)
);

-- @TODO
-- CREATE TABLE `shipping_files` (
--     `id`            int(10)         UNSIGNED NOT NULL AUTO_INCREMENT,
--     `description`   varchar(40)     NOT NULL,
--     PRIMARY KEY (`transaction`, `title`)
-- );