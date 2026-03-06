<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305171508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move user roles from JSON column to dedicated user_role table, preparing for clinic-scoped roles';
    }

    public function up(Schema $schema): void
    {
        // 1. Create user_role table immediately (executeStatement, not addSql, so it runs before data migration)
        $this->connection->executeStatement('CREATE TABLE user_role (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, clinic_id INT DEFAULT NULL, role VARCHAR(50) NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3CC22AD4 (clinic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3CC22AD4 FOREIGN KEY (clinic_id) REFERENCES clinic (id) ON DELETE CASCADE');

        // 2. Migrate existing roles data BEFORE dropping the column
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM `user`');
        foreach ($users as $row) {
            $roles = json_decode($row['roles'], true) ?? [];
            foreach ($roles as $role) {
                $this->connection->executeStatement(
                    'INSERT INTO user_role (user_id, role, clinic_id) VALUES (?, ?, NULL)',
                    [$row['id'], $role]
                );
            }
        }

        // 3. Drop the old column
        $this->addSql('ALTER TABLE `user` DROP roles');
    }

    public function down(Schema $schema): void
    {
        // Add column with default so it works on populated tables
        $this->connection->executeStatement("ALTER TABLE `user` ADD roles JSON NOT NULL DEFAULT '[]' COMMENT '(DC2Type:json)'");

        // Restore roles for users that have rows in user_role
        $rows = $this->connection->fetchAllAssociative(
            'SELECT user_id, GROUP_CONCAT(role) as roles FROM user_role WHERE clinic_id IS NULL GROUP BY user_id'
        );
        foreach ($rows as $row) {
            $roles = json_encode(explode(',', $row['roles']));
            $this->connection->executeStatement(
                'UPDATE `user` SET roles = ? WHERE id = ?',
                [$roles, $row['user_id']]
            );
        }

        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3CC22AD4');
        $this->addSql('DROP TABLE user_role');
    }
}
