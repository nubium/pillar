USE `test`;

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
