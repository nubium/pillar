CREATE DATABASE IF NOT EXISTS `testdb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `testdb`;

# CREATE USER 'travis'@'localhost' IDENTIFIED BY 'travis';
# GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP ON *.* TO 'travis'@'localhost';
# GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP ON *.* TO 'travis'@'127.0.0.1';
# GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP ON *.* TO 'travis'@'*';

CREATE TABLE products
(
    id int PRIMARY KEY AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    image_id int NOT NULL,
    price float NOT NULL
) engine = InnoDB;

create table images
(
  id   int auto_increment primary key,
  path varchar(255) not null
) engine = InnoDB;

ALTER TABLE products
ADD CONSTRAINT products_images_id_fk
FOREIGN KEY (image_id) REFERENCES images (id);

CREATE INDEX idx_try_me_out ON products(name);
CREATE INDEX idx_img ON images(path);
