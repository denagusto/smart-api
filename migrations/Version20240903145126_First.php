<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240903145126_First extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and projects tables, and define stored procedures for CRUD operations';
    }

    public function up(Schema $schema): void
    {
        // Creating the users table
        $this->addSql('
            CREATE TABLE users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(180) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                roles JSON NOT NULL
            );
        ');

        // Creating the projects table
        $this->addSql("
            CREATE TABLE projects (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                code VARCHAR(6) UNIQUE NOT NULL,
                name VARCHAR(200) NOT NULL,
                location VARCHAR(500) NOT NULL,
                stage VARCHAR(50) NOT NULL,
                category VARCHAR(200) NOT NULL,
                category_text VARCHAR(200) NULL,
                fee VARCHAR(50),
                start_date DATE NOT NULL,
                details TEXT,
                creator_id UUID NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                CONSTRAINT fk_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT stage_check CHECK (stage IN ('Concept', 'Design & Documentation', 'Pre-Construction', 'Construction')),
                CONSTRAINT category_check CHECK (category IN ('Education', 'Health', 'Office', 'Others')),
                CONSTRAINT category_text_check CHECK (
                    (category = 'Others' AND category_text IS NOT NULL) OR 
                    (category != 'Others' AND category_text IS NULL)
                )
            );
        ");

        // Adding indexes
        $this->addSql('CREATE INDEX idx_project_stage ON projects(stage);');
        $this->addSql('CREATE INDEX idx_project_category ON projects(category);');
        $this->addSql('CREATE INDEX idx_project_creator_id ON projects(creator_id);');

        // Creating stored procedures
        $this->addSql('
            CREATE OR REPLACE PROCEDURE create_project(
                p_code VARCHAR,
                p_name VARCHAR,
                p_location VARCHAR,
                p_stage VARCHAR,
                p_category VARCHAR,
                p_fee VARCHAR,
                p_start_date DATE,
                p_details TEXT,
                p_creator_id UUID
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                INSERT INTO projects (id, code, name, location, stage, category, fee, start_date, details, creator_id)
                VALUES (gen_random_uuid(), p_code, p_name, p_location, p_stage, p_category, p_fee, p_start_date, p_details, p_creator_id);
            END;
            $$;
        ');

        $this->addSql('
            CREATE OR REPLACE PROCEDURE get_project_by_id(
                p_project_id UUID
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                PERFORM id, code, name, location, stage, category, fee, start_date, details, creator_id, created_at, updated_at
                FROM projects
                WHERE id = p_project_id;
            END;
            $$;
        ');

        $this->addSql('
            CREATE OR REPLACE PROCEDURE update_project(
                p_project_id UUID,
                p_code VARCHAR,
                p_name VARCHAR,
                p_location VARCHAR,
                p_stage VARCHAR,
                p_category VARCHAR,
                p_fee VARCHAR,
                p_start_date DATE,
                p_details TEXT,
                p_creator_id UUID
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                UPDATE projects
                SET name = p_name,
                    code = p_code,
                    location = p_location,
                    stage = p_stage,
                    category = p_category,
                    fee = p_fee,
                    start_date = p_start_date,
                    details = p_details,
                    creator_id = p_creator_id,
                    updated_at = NOW()
                WHERE id = p_project_id;
            END;
            $$;
        ');

        $this->addSql('
            CREATE OR REPLACE PROCEDURE delete_project(
                p_project_id UUID
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                DELETE FROM projects WHERE id = p_project_id;
            END;
            $$;
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop the projects table and related stored procedures
        $this->addSql('DROP TABLE IF EXISTS projects CASCADE;');
        $this->addSql('DROP TABLE IF EXISTS users CASCADE;');

        // Optionally, drop stored procedures if they exist
        $this->addSql('DROP PROCEDURE IF EXISTS create_project;');
        $this->addSql('DROP PROCEDURE IF EXISTS get_project_by_id;');
        $this->addSql('DROP PROCEDURE IF EXISTS update_project;');
        $this->addSql('DROP PROCEDURE IF EXISTS delete_project;');
    }
}
