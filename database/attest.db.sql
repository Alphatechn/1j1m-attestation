BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "attestations" (
	"id"	integer NOT NULL,
	"participant_id"	integer NOT NULL,
	"periode_id"	integer NOT NULL,
	"generated_by"	integer,
	"attestation_number"	varchar NOT NULL,
	"qr_token"	varchar NOT NULL,
	"issue_date"	date NOT NULL,
	"content_text"	text,
	"status"	varchar NOT NULL DEFAULT 'pending' CHECK("status" IN ('pending', 'sent')),
	"sent_at"	datetime,
	"view_count"	integer NOT NULL DEFAULT '0',
	"last_viewed_at"	datetime,
	"email_status"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("generated_by") REFERENCES "users"("id") on delete set null,
	FOREIGN KEY("participant_id") REFERENCES "participants"("id") on delete cascade,
	FOREIGN KEY("periode_id") REFERENCES "periodes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id"	integer NOT NULL,
	"uuid"	varchar NOT NULL,
	"connection"	text NOT NULL,
	"queue"	text NOT NULL,
	"payload"	text NOT NULL,
	"exception"	text NOT NULL,
	"failed_at"	datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "migrations" (
	"id"	integer NOT NULL,
	"migration"	varchar NOT NULL,
	"batch"	integer NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "model_has_permissions" (
	"permission_id"	integer NOT NULL,
	"model_type"	varchar NOT NULL,
	"model_id"	integer NOT NULL,
	PRIMARY KEY("permission_id","model_id","model_type"),
	FOREIGN KEY("permission_id") REFERENCES "permissions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "model_has_roles" (
	"role_id"	integer NOT NULL,
	"model_type"	varchar NOT NULL,
	"model_id"	integer NOT NULL,
	PRIMARY KEY("role_id","model_id","model_type"),
	FOREIGN KEY("role_id") REFERENCES "roles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "participants" (
	"id"	integer NOT NULL,
	"periode_id"	integer NOT NULL,
	"first_name"	varchar,
	"last_name"	varchar NOT NULL,
	"email"	varchar,
	"phone"	varchar,
	"matricule"	varchar,
	"organisation"	varchar,
	"fonction"	varchar,
	"is_active"	tinyint(1) NOT NULL DEFAULT '1',
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT),
	FOREIGN KEY("periode_id") REFERENCES "periodes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email"	varchar NOT NULL,
	"token"	varchar NOT NULL,
	"created_at"	datetime,
	PRIMARY KEY("email")
);
CREATE TABLE IF NOT EXISTS "periodes" (
	"id"	integer NOT NULL,
	"libelle"	varchar NOT NULL,
	"mois_debut"	varchar NOT NULL,
	"annee_debut"	varchar NOT NULL,
	"mois_fin"	varchar NOT NULL,
	"annee_fin"	varchar NOT NULL,
	"is_active"	tinyint(1) NOT NULL DEFAULT '1',
	"date_debut"	date,
	"date_fin"	date,
	"description"	text,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "permissions" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"guard_name"	varchar NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
	"id"	integer NOT NULL,
	"tokenable_type"	varchar NOT NULL,
	"tokenable_id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"token"	varchar NOT NULL,
	"abilities"	text,
	"last_used_at"	datetime,
	"expires_at"	datetime,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "role_has_permissions" (
	"permission_id"	integer NOT NULL,
	"role_id"	integer NOT NULL,
	PRIMARY KEY("permission_id","role_id"),
	FOREIGN KEY("permission_id") REFERENCES "permissions"("id") on delete cascade,
	FOREIGN KEY("role_id") REFERENCES "roles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "roles" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"guard_name"	varchar NOT NULL,
	"created_at"	datetime,
	"updated_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "users" (
	"id"	integer NOT NULL,
	"name"	varchar NOT NULL,
	"login"	varchar NOT NULL,
	"email"	varchar,
	"photo"	varchar NOT NULL DEFAULT 'assets/images/default.jpg',
	"email_verified_at"	datetime,
	"password"	varchar NOT NULL,
	"is_active"	tinyint(1) NOT NULL DEFAULT '1',
	"is_delete"	tinyint(1) NOT NULL DEFAULT '0',
	"remember_token"	varchar,
	"created_at"	datetime,
	"updated_at"	datetime,
	"deleted_at"	datetime,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE INDEX IF NOT EXISTS "attestations_attestation_number_index" ON "attestations" (
	"attestation_number"
);
CREATE UNIQUE INDEX IF NOT EXISTS "attestations_attestation_number_unique" ON "attestations" (
	"attestation_number"
);
CREATE INDEX IF NOT EXISTS "attestations_issue_date_index" ON "attestations" (
	"issue_date"
);
CREATE INDEX IF NOT EXISTS "attestations_participant_id_index" ON "attestations" (
	"participant_id"
);
CREATE INDEX IF NOT EXISTS "attestations_periode_id_index" ON "attestations" (
	"periode_id"
);
CREATE INDEX IF NOT EXISTS "attestations_qr_token_index" ON "attestations" (
	"qr_token"
);
CREATE UNIQUE INDEX IF NOT EXISTS "attestations_qr_token_unique" ON "attestations" (
	"qr_token"
);
CREATE INDEX IF NOT EXISTS "attestations_status_index" ON "attestations" (
	"status"
);
CREATE UNIQUE INDEX IF NOT EXISTS "failed_jobs_uuid_unique" ON "failed_jobs" (
	"uuid"
);
CREATE INDEX IF NOT EXISTS "model_has_permissions_model_id_model_type_index" ON "model_has_permissions" (
	"model_id",
	"model_type"
);
CREATE INDEX IF NOT EXISTS "model_has_roles_model_id_model_type_index" ON "model_has_roles" (
	"model_id",
	"model_type"
);
CREATE INDEX IF NOT EXISTS "participants_email_index" ON "participants" (
	"email"
);
CREATE INDEX IF NOT EXISTS "participants_first_name_last_name_index" ON "participants" (
	"first_name",
	"last_name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "participants_matricule_unique" ON "participants" (
	"matricule"
);
CREATE INDEX IF NOT EXISTS "participants_periode_id_index" ON "participants" (
	"periode_id"
);
CREATE INDEX IF NOT EXISTS "periodes_annee_debut_mois_debut_index" ON "periodes" (
	"annee_debut",
	"mois_debut"
);
CREATE INDEX IF NOT EXISTS "periodes_is_active_index" ON "periodes" (
	"is_active"
);
CREATE UNIQUE INDEX IF NOT EXISTS "permissions_name_guard_name_unique" ON "permissions" (
	"name",
	"guard_name"
);
CREATE UNIQUE INDEX IF NOT EXISTS "personal_access_tokens_token_unique" ON "personal_access_tokens" (
	"token"
);
CREATE INDEX IF NOT EXISTS "personal_access_tokens_tokenable_type_tokenable_id_index" ON "personal_access_tokens" (
	"tokenable_type",
	"tokenable_id"
);
CREATE UNIQUE INDEX IF NOT EXISTS "roles_name_guard_name_unique" ON "roles" (
	"name",
	"guard_name"
);
CREATE INDEX IF NOT EXISTS "users_email_index" ON "users" (
	"email"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" (
	"email"
);
CREATE INDEX IF NOT EXISTS "users_is_active_index" ON "users" (
	"is_active"
);
CREATE INDEX IF NOT EXISTS "users_login_index" ON "users" (
	"login"
);
CREATE UNIQUE INDEX IF NOT EXISTS "users_login_unique" ON "users" (
	"login"
);
COMMIT;
