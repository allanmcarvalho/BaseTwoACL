/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  allan
 * Created: 07/02/2017
 */

CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB

/*
 * Must be power base two
 * Ex Data;
 * INSERT INTO `modules` (`id`, `name`, `description`, `created`, `modified`) VALUES
 * (1, 'Users', 'manage users', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 * (2, 'Customers', 'manage customers', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 * (4, 'Logs', 'manage logs', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 * (8, 'Products', 'manage products', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 * (16, 'Articles', 'manage articles', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 * (32, 'Others', 'manage others', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
 */



/*
 * Base example of user table
 */
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(80) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(12) NULL,
  `read` INT NOT NULL DEFAULT 0, /* This field is required */
  `write` INT NOT NULL DEFAULT 0, /* This field is required */
  `delete` INT NOT NULL DEFAULT 0, /* This field is required */
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`)
ENGINE = InnoDB