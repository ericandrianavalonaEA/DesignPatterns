<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250705080040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__store AS SELECT id, name, address, city, postal_code, latitude, longitude FROM store');
        $this->addSql('DROP TABLE store');
        $this->addSql('CREATE TABLE store (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(20) NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL)');
        $this->addSql('INSERT INTO store (id, name, address, city, postal_code, latitude, longitude) SELECT id, name, address, city, postal_code, latitude, longitude FROM __temp__store');
        $this->addSql('DROP TABLE __temp__store');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__store AS SELECT id, name, address, city, postal_code, latitude, longitude FROM store');
        $this->addSql('DROP TABLE store');
        $this->addSql('CREATE TABLE store (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL)');
        $this->addSql('INSERT INTO store (id, name, address, city, postal_code, latitude, longitude) SELECT id, name, address, city, postal_code, latitude, longitude FROM __temp__store');
        $this->addSql('DROP TABLE __temp__store');
    }
}
