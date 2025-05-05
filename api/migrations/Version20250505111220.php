<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505111220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_5768F419D17F50A6 ON license (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session CHANGE is_active active TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_5768F419D17F50A6 ON license
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE session CHANGE active is_active TINYINT(1) NOT NULL
        SQL);
    }
}
