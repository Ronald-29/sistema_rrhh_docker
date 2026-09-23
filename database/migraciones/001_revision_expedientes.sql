-- Punto 1 de los términos de referencia de EMAPA:
-- registrar la revisión física de cada expediente personal.
--
-- revisado_por: persona que revisó físicamente el expediente (texto libre,
-- igual que entregado_por en préstamos).
-- id_usuario_revision: usuario del sistema que registró la revisión.

BEGIN;

ALTER TABLE expedientes
    ADD COLUMN revisado BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN revisado_por VARCHAR(150),
    ADD COLUMN fecha_revision TIMESTAMP WITHOUT TIME ZONE,
    ADD COLUMN id_usuario_revision INTEGER;

ALTER TABLE expedientes
    ADD CONSTRAINT fk_expediente_usuario_revision
        FOREIGN KEY (id_usuario_revision) REFERENCES usuarios(id_usuario);

ALTER TABLE expedientes
    ADD CONSTRAINT chk_datos_revision CHECK (
        (
            revisado = TRUE
            AND revisado_por IS NOT NULL
            AND fecha_revision IS NOT NULL
            AND id_usuario_revision IS NOT NULL
        )
        OR (
            revisado = FALSE
            AND revisado_por IS NULL
            AND fecha_revision IS NULL
            AND id_usuario_revision IS NULL
        )
    );

COMMIT;
