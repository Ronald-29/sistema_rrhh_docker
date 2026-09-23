--
-- PostgreSQL database dump
--

-- Dumped from database version 16.4
-- Dumped by pg_dump version 16.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria (
    id_auditoria bigint NOT NULL,
    id_usuario integer,
    accion character varying(30) NOT NULL,
    tabla_afectada character varying(100) NOT NULL,
    id_registro integer,
    descripcion text,
    datos_anteriores jsonb,
    datos_nuevos jsonb,
    fecha_hora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_accion_auditoria CHECK (((accion)::text = ANY ((ARRAY['CREAR'::character varying, 'MODIFICAR'::character varying, 'ELIMINAR'::character varying, 'PRESTAR'::character varying, 'DEVOLVER'::character varying])::text[])))
);


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditoria_id_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditoria_id_auditoria_seq OWNED BY public.auditoria.id_auditoria;


--
-- Name: documentos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documentos (
    id_documento integer NOT NULL,
    id_expediente integer NOT NULL,
    nombre_documento character varying(150) NOT NULL,
    tipo_documento character varying(100),
    numero_documento character varying(50),
    fecha_documento date,
    estado_documento character varying(20) DEFAULT 'DISPONIBLE'::character varying NOT NULL,
    observaciones text,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_estado_documento CHECK (((estado_documento)::text = ANY ((ARRAY['DISPONIBLE'::character varying, 'PRESTADO'::character varying, 'DETERIORADO'::character varying, 'EXTRAVIADO'::character varying])::text[])))
);


--
-- Name: documentos_id_documento_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documentos_id_documento_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documentos_id_documento_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documentos_id_documento_seq OWNED BY public.documentos.id_documento;


--
-- Name: expedientes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.expedientes (
    id_expediente integer NOT NULL,
    numero_correlativo character varying(30) NOT NULL,
    id_personal integer NOT NULL,
    id_ubicacion integer,
    estado_expediente character varying(20) DEFAULT 'COMPLETO'::character varying NOT NULL,
    observaciones text,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    revisado boolean DEFAULT false NOT NULL,
    revisado_por character varying(150),
    fecha_revision timestamp without time zone,
    id_usuario_revision integer,
    CONSTRAINT chk_datos_revision CHECK ((((revisado = true) AND (revisado_por IS NOT NULL) AND (fecha_revision IS NOT NULL) AND (id_usuario_revision IS NOT NULL)) OR ((revisado = false) AND (revisado_por IS NULL) AND (fecha_revision IS NULL) AND (id_usuario_revision IS NULL)))),
    CONSTRAINT chk_estado_expediente CHECK (((estado_expediente)::text = ANY ((ARRAY['COMPLETO'::character varying, 'INCOMPLETO'::character varying, 'DETERIORADO'::character varying, 'OBSERVADO'::character varying])::text[])))
);


--
-- Name: expedientes_id_expediente_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.expedientes_id_expediente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: expedientes_id_expediente_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.expedientes_id_expediente_seq OWNED BY public.expedientes.id_expediente;


--
-- Name: personal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal (
    id_personal integer NOT NULL,
    nombres character varying(100) NOT NULL,
    apellido_paterno character varying(100),
    apellido_materno character varying(100),
    numero_documento character varying(30),
    cargo character varying(150) NOT NULL,
    fecha_ingreso date,
    fecha_retiro date,
    estado_laboral character varying(10) NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_estado_laboral CHECK (((estado_laboral)::text = ANY ((ARRAY['ACTIVO'::character varying, 'PASIVO'::character varying])::text[]))),
    CONSTRAINT chk_fechas_laborales CHECK (((fecha_retiro IS NULL) OR (fecha_ingreso IS NULL) OR (fecha_retiro >= fecha_ingreso)))
);


--
-- Name: personal_id_personal_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_id_personal_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_id_personal_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_id_personal_seq OWNED BY public.personal.id_personal;


--
-- Name: prestamos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prestamos (
    id_prestamo integer NOT NULL,
    id_expediente integer,
    id_documento integer,
    entregado_por character varying(150) NOT NULL,
    recibido_por character varying(150) NOT NULL,
    fecha_hora_entrega timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    motivo character varying(250),
    observaciones_entrega text,
    devuelto_por character varying(150),
    recibido_devolucion_por character varying(150),
    fecha_hora_devolucion timestamp without time zone,
    observaciones_devolucion text,
    estado character varying(20) DEFAULT 'PRESTADO'::character varying NOT NULL,
    id_usuario_registro integer NOT NULL,
    CONSTRAINT chk_datos_devolucion CHECK (((((estado)::text = 'PRESTADO'::text) AND (devuelto_por IS NULL) AND (recibido_devolucion_por IS NULL) AND (fecha_hora_devolucion IS NULL)) OR (((estado)::text = 'DEVUELTO'::text) AND (devuelto_por IS NOT NULL) AND (recibido_devolucion_por IS NOT NULL) AND (fecha_hora_devolucion IS NOT NULL)))),
    CONSTRAINT chk_estado_prestamo CHECK (((estado)::text = ANY ((ARRAY['PRESTADO'::character varying, 'DEVUELTO'::character varying])::text[]))),
    CONSTRAINT chk_fecha_devolucion CHECK (((fecha_hora_devolucion IS NULL) OR (fecha_hora_devolucion >= fecha_hora_entrega))),
    CONSTRAINT chk_tipo_prestamo CHECK ((((id_expediente IS NOT NULL) AND (id_documento IS NULL)) OR ((id_expediente IS NULL) AND (id_documento IS NOT NULL))))
);


--
-- Name: prestamos_id_prestamo_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.prestamos_id_prestamo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prestamos_id_prestamo_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.prestamos_id_prestamo_seq OWNED BY public.prestamos.id_prestamo;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id_rol integer NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion character varying(200),
    estado boolean DEFAULT true NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: roles_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_rol_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_rol_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_rol_seq OWNED BY public.roles.id_rol;


--
-- Name: ubicaciones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ubicaciones (
    id_ubicacion integer NOT NULL,
    ambiente character varying(100),
    estante character varying(50),
    archivador character varying(50),
    caja character varying(50),
    carpeta character varying(50),
    descripcion character varying(200),
    estado boolean DEFAULT true NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: ubicaciones_id_ubicacion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ubicaciones_id_ubicacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ubicaciones_id_ubicacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ubicaciones_id_ubicacion_seq OWNED BY public.ubicaciones.id_ubicacion;


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    id_rol integer NOT NULL,
    nombres character varying(100) NOT NULL,
    apellidos character varying(100) NOT NULL,
    nombre_usuario character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    estado boolean DEFAULT true NOT NULL,
    ultimo_acceso timestamp without time zone,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    fecha_actualizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- Name: auditoria id_auditoria; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria ALTER COLUMN id_auditoria SET DEFAULT nextval('public.auditoria_id_auditoria_seq'::regclass);


--
-- Name: documentos id_documento; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos ALTER COLUMN id_documento SET DEFAULT nextval('public.documentos_id_documento_seq'::regclass);


--
-- Name: expedientes id_expediente; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes ALTER COLUMN id_expediente SET DEFAULT nextval('public.expedientes_id_expediente_seq'::regclass);


--
-- Name: personal id_personal; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal ALTER COLUMN id_personal SET DEFAULT nextval('public.personal_id_personal_seq'::regclass);


--
-- Name: prestamos id_prestamo; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestamos ALTER COLUMN id_prestamo SET DEFAULT nextval('public.prestamos_id_prestamo_seq'::regclass);


--
-- Name: roles id_rol; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id_rol SET DEFAULT nextval('public.roles_id_rol_seq'::regclass);


--
-- Name: ubicaciones id_ubicacion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones ALTER COLUMN id_ubicacion SET DEFAULT nextval('public.ubicaciones_id_ubicacion_seq'::regclass);


--
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- Name: auditoria auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_pkey PRIMARY KEY (id_auditoria);


--
-- Name: documentos documentos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos
    ADD CONSTRAINT documentos_pkey PRIMARY KEY (id_documento);


--
-- Name: expedientes expedientes_numero_correlativo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes
    ADD CONSTRAINT expedientes_numero_correlativo_key UNIQUE (numero_correlativo);


--
-- Name: expedientes expedientes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes
    ADD CONSTRAINT expedientes_pkey PRIMARY KEY (id_expediente);


--
-- Name: personal personal_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal
    ADD CONSTRAINT personal_pkey PRIMARY KEY (id_personal);


--
-- Name: prestamos prestamos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT prestamos_pkey PRIMARY KEY (id_prestamo);


--
-- Name: roles roles_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_key UNIQUE (nombre);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id_rol);


--
-- Name: ubicaciones ubicaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ubicaciones
    ADD CONSTRAINT ubicaciones_pkey PRIMARY KEY (id_ubicacion);


--
-- Name: personal uq_personal_documento; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal
    ADD CONSTRAINT uq_personal_documento UNIQUE (numero_documento);


--
-- Name: usuarios usuarios_nombre_usuario_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_nombre_usuario_key UNIQUE (nombre_usuario);


--
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


--
-- Name: uq_prestamo_documento_abierto; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_prestamo_documento_abierto ON public.prestamos USING btree (id_documento) WHERE (((estado)::text = 'PRESTADO'::text) AND (id_documento IS NOT NULL));


--
-- Name: uq_prestamo_expediente_abierto; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_prestamo_expediente_abierto ON public.prestamos USING btree (id_expediente) WHERE (((estado)::text = 'PRESTADO'::text) AND (id_expediente IS NOT NULL));


--
-- Name: auditoria fk_auditoria_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id_usuario);


--
-- Name: documentos fk_documento_expediente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documentos
    ADD CONSTRAINT fk_documento_expediente FOREIGN KEY (id_expediente) REFERENCES public.expedientes(id_expediente);


--
-- Name: expedientes fk_expediente_personal; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes
    ADD CONSTRAINT fk_expediente_personal FOREIGN KEY (id_personal) REFERENCES public.personal(id_personal);


--
-- Name: expedientes fk_expediente_ubicacion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes
    ADD CONSTRAINT fk_expediente_ubicacion FOREIGN KEY (id_ubicacion) REFERENCES public.ubicaciones(id_ubicacion);


--
-- Name: expedientes fk_expediente_usuario_revision; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.expedientes
    ADD CONSTRAINT fk_expediente_usuario_revision FOREIGN KEY (id_usuario_revision) REFERENCES public.usuarios(id_usuario);


--
-- Name: prestamos fk_prestamo_documento; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT fk_prestamo_documento FOREIGN KEY (id_documento) REFERENCES public.documentos(id_documento);


--
-- Name: prestamos fk_prestamo_expediente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT fk_prestamo_expediente FOREIGN KEY (id_expediente) REFERENCES public.expedientes(id_expediente);


--
-- Name: prestamos fk_prestamo_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prestamos
    ADD CONSTRAINT fk_prestamo_usuario FOREIGN KEY (id_usuario_registro) REFERENCES public.usuarios(id_usuario);


--
-- Name: usuarios fk_usuario_rol; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES public.roles(id_rol);


--
-- PostgreSQL database dump complete
--

