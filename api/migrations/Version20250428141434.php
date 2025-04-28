<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428141434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE license (id INT AUTO_INCREMENT NOT NULL, owner VARCHAR(255) NOT NULL, uuid CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, ip_addr VARCHAR(15) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, license_id INT NOT NULL, location_id INT NOT NULL, date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', last_updated DATETIME NOT NULL, end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, INDEX IDX_D044D5D4460F904B (license_id), INDEX IDX_D044D5D464D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD CONSTRAINT FK_D044D5D4460F904B FOREIGN KEY (license_id) REFERENCES license (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session ADD CONSTRAINT FK_D044D5D464D218E FOREIGN KEY (location_id) REFERENCES location (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4460F904B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session DROP FOREIGN KEY FK_D044D5D464D218E
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE license
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE location
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE session
        SQL);
    }
}
