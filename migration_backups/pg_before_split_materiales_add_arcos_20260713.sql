--
-- PostgreSQL database dump
--

\restrict LM7eoFVlIHYb8ogFph55kZM3hr5IgF8T1E6Y34uxglSu6lyLVnROc0zG5BCnXML

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.revisiones DROP CONSTRAINT IF EXISTS revisiones_arco_id_fkey;
ALTER TABLE IF EXISTS ONLY public.revision_material DROP CONSTRAINT IF EXISTS revision_material_revision_id_fkey;
ALTER TABLE IF EXISTS ONLY public.revision_material DROP CONSTRAINT IF EXISTS revision_material_material_id_fkey;
ALTER TABLE IF EXISTS ONLY public.revision_material DROP CONSTRAINT IF EXISTS revision_material_arco_material_id_fkey;
ALTER TABLE IF EXISTS ONLY public.revision_evidencias DROP CONSTRAINT IF EXISTS revision_evidencias_revision_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revisiones DROP CONSTRAINT IF EXISTS infraestructura_revisiones_infraestructura_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revision_material DROP CONSTRAINT IF EXISTS infraestructura_revision_material_revision_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revision_material DROP CONSTRAINT IF EXISTS infraestructura_revision_material_material_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revision_evidencias DROP CONSTRAINT IF EXISTS infraestructura_revision_evidencias_revision_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_nodos DROP CONSTRAINT IF EXISTS infraestructura_nodos_ubicacion_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_material DROP CONSTRAINT IF EXISTS infraestructura_material_material_id_fkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_material DROP CONSTRAINT IF EXISTS infraestructura_material_infraestructura_id_fkey;
ALTER TABLE IF EXISTS ONLY public.formatos_mantenimiento DROP CONSTRAINT IF EXISTS formatos_mantenimiento_tecnico_id_fkey;
ALTER TABLE IF EXISTS ONLY public.formatos_mantenimiento DROP CONSTRAINT IF EXISTS formatos_mantenimiento_revision_id_fkey;
ALTER TABLE IF EXISTS ONLY public.formatos_mantenimiento DROP CONSTRAINT IF EXISTS formatos_mantenimiento_arco_id_fkey;
ALTER TABLE IF EXISTS ONLY public.revisiones DROP CONSTRAINT IF EXISTS fk_revisiones_tecnico;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revisiones DROP CONSTRAINT IF EXISTS fk_infra_revisiones_tecnico;
ALTER TABLE IF EXISTS ONLY public.formatos_mantenimiento DROP CONSTRAINT IF EXISTS fk_formatos_mantenimiento_tecnico;
ALTER TABLE IF EXISTS ONLY public.bitacoras_arco DROP CONSTRAINT IF EXISTS fk_bitacoras_arco_tecnico;
ALTER TABLE IF EXISTS ONLY public.arcos_bajas DROP CONSTRAINT IF EXISTS fk_arcos_bajas_tecnico;
ALTER TABLE IF EXISTS ONLY public.bitacoras_arco DROP CONSTRAINT IF EXISTS bitacoras_arco_arco_id_fkey;
ALTER TABLE IF EXISTS ONLY public.bitacora_checklist DROP CONSTRAINT IF EXISTS bitacora_checklist_concepto_id_fkey;
ALTER TABLE IF EXISTS ONLY public.bitacora_checklist DROP CONSTRAINT IF EXISTS bitacora_checklist_bitacora_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arcos DROP CONSTRAINT IF EXISTS arcos_ubicacion_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arcos_bajas_evidencias DROP CONSTRAINT IF EXISTS arcos_bajas_evidencias_baja_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arcos_bajas DROP CONSTRAINT IF EXISTS arcos_bajas_arco_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arco_material DROP CONSTRAINT IF EXISTS arco_material_material_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arco_material DROP CONSTRAINT IF EXISTS arco_material_arco_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arco_infraestructura DROP CONSTRAINT IF EXISTS arco_infraestructura_infraestructura_id_fkey;
ALTER TABLE IF EXISTS ONLY public.arco_infraestructura DROP CONSTRAINT IF EXISTS arco_infraestructura_arco_id_fkey;
DROP INDEX IF EXISTS public.idx_tecnicos_activo;
DROP INDEX IF EXISTS public.idx_revisiones_tecnico_id;
DROP INDEX IF EXISTS public.idx_revisiones_fecha;
DROP INDEX IF EXISTS public.idx_revisiones_arco_id;
DROP INDEX IF EXISTS public.idx_revisiones_arco_fecha;
DROP INDEX IF EXISTS public.idx_revision_material_revision_id;
DROP INDEX IF EXISTS public.idx_revision_material_arco_material_id;
DROP INDEX IF EXISTS public.idx_revision_evidencias_revision_id;
DROP INDEX IF EXISTS public.idx_infraestructura_revisiones_tecnico_id;
DROP INDEX IF EXISTS public.idx_infraestructura_revisiones_infra_id;
DROP INDEX IF EXISTS public.idx_infraestructura_nodos_ubicacion_id;
DROP INDEX IF EXISTS public.idx_infraestructura_material_infra_id;
DROP INDEX IF EXISTS public.idx_infra_revisiones_infra_fecha;
DROP INDEX IF EXISTS public.idx_infra_revision_material_revision_id;
DROP INDEX IF EXISTS public.idx_infra_revision_evidencias_revision_id;
DROP INDEX IF EXISTS public.idx_formatos_mantenimiento_tecnico_id;
DROP INDEX IF EXISTS public.idx_formatos_mantenimiento_revision;
DROP INDEX IF EXISTS public.idx_formatos_mantenimiento_arco;
DROP INDEX IF EXISTS public.idx_bitacoras_arco_tecnico_id;
DROP INDEX IF EXISTS public.idx_bitacoras_arco_id;
DROP INDEX IF EXISTS public.idx_bitacoras_arco_fecha;
DROP INDEX IF EXISTS public.idx_arcos_ubicacion_id;
DROP INDEX IF EXISTS public.idx_arcos_estado;
DROP INDEX IF EXISTS public.idx_arcos_bajas_tecnico_id;
DROP INDEX IF EXISTS public.idx_arcos_bajas_fecha;
DROP INDEX IF EXISTS public.idx_arcos_bajas_evidencias_baja_id;
DROP INDEX IF EXISTS public.idx_arcos_bajas_arco_id;
DROP INDEX IF EXISTS public.idx_arco_material_material_id;
DROP INDEX IF EXISTS public.idx_arco_material_arco_id;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_username_key;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.ubicaciones DROP CONSTRAINT IF EXISTS ubicaciones_pkey;
ALTER TABLE IF EXISTS ONLY public.ubicaciones DROP CONSTRAINT IF EXISTS ubicaciones_nombre_key;
ALTER TABLE IF EXISTS ONLY public.tecnicos DROP CONSTRAINT IF EXISTS tecnicos_pkey;
ALTER TABLE IF EXISTS ONLY public.tecnicos DROP CONSTRAINT IF EXISTS tecnicos_nombre_key;
ALTER TABLE IF EXISTS ONLY public.revisiones DROP CONSTRAINT IF EXISTS revisiones_pkey;
ALTER TABLE IF EXISTS ONLY public.revision_material DROP CONSTRAINT IF EXISTS revision_material_pkey;
ALTER TABLE IF EXISTS ONLY public.revision_evidencias DROP CONSTRAINT IF EXISTS revision_evidencias_pkey;
ALTER TABLE IF EXISTS ONLY public.materiales DROP CONSTRAINT IF EXISTS materiales_pkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revisiones DROP CONSTRAINT IF EXISTS infraestructura_revisiones_pkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revision_material DROP CONSTRAINT IF EXISTS infraestructura_revision_material_pkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_revision_evidencias DROP CONSTRAINT IF EXISTS infraestructura_revision_evidencias_pkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_nodos DROP CONSTRAINT IF EXISTS infraestructura_nodos_tipo_nombre_key;
ALTER TABLE IF EXISTS ONLY public.infraestructura_nodos DROP CONSTRAINT IF EXISTS infraestructura_nodos_pkey;
ALTER TABLE IF EXISTS ONLY public.infraestructura_material DROP CONSTRAINT IF EXISTS infraestructura_material_pkey;
ALTER TABLE IF EXISTS ONLY public.formatos_mantenimiento DROP CONSTRAINT IF EXISTS formatos_mantenimiento_pkey;
ALTER TABLE IF EXISTS ONLY public.checklist_conceptos DROP CONSTRAINT IF EXISTS checklist_conceptos_pkey;
ALTER TABLE IF EXISTS ONLY public.bitacoras_arco DROP CONSTRAINT IF EXISTS bitacoras_arco_pkey;
ALTER TABLE IF EXISTS ONLY public.bitacora_checklist DROP CONSTRAINT IF EXISTS bitacora_checklist_pkey;
ALTER TABLE IF EXISTS ONLY public.bitacora_checklist DROP CONSTRAINT IF EXISTS bitacora_checklist_bitacora_id_concepto_id_key;
ALTER TABLE IF EXISTS ONLY public.arcos DROP CONSTRAINT IF EXISTS arcos_pkey;
ALTER TABLE IF EXISTS ONLY public.arcos DROP CONSTRAINT IF EXISTS arcos_nombre_key;
ALTER TABLE IF EXISTS ONLY public.arcos_bajas DROP CONSTRAINT IF EXISTS arcos_bajas_pkey;
ALTER TABLE IF EXISTS ONLY public.arcos_bajas_evidencias DROP CONSTRAINT IF EXISTS arcos_bajas_evidencias_pkey;
ALTER TABLE IF EXISTS ONLY public.arco_material DROP CONSTRAINT IF EXISTS arco_material_pkey;
ALTER TABLE IF EXISTS ONLY public.arco_infraestructura DROP CONSTRAINT IF EXISTS arco_infraestructura_pkey;
DROP TABLE IF EXISTS public.users;
DROP TABLE IF EXISTS public.ubicaciones;
DROP TABLE IF EXISTS public.tecnicos;
DROP TABLE IF EXISTS public.revisiones;
DROP TABLE IF EXISTS public.revision_material;
DROP TABLE IF EXISTS public.revision_evidencias;
DROP TABLE IF EXISTS public.materiales;
DROP TABLE IF EXISTS public.infraestructura_revisiones;
DROP TABLE IF EXISTS public.infraestructura_revision_material;
DROP TABLE IF EXISTS public.infraestructura_revision_evidencias;
DROP TABLE IF EXISTS public.infraestructura_nodos;
DROP TABLE IF EXISTS public.infraestructura_material;
DROP TABLE IF EXISTS public.formatos_mantenimiento;
DROP TABLE IF EXISTS public.checklist_conceptos;
DROP TABLE IF EXISTS public.bitacoras_arco;
DROP TABLE IF EXISTS public.bitacora_checklist;
DROP TABLE IF EXISTS public.arcos_bajas_evidencias;
DROP TABLE IF EXISTS public.arcos_bajas;
DROP TABLE IF EXISTS public.arcos;
DROP TABLE IF EXISTS public.arco_material;
DROP TABLE IF EXISTS public.arco_infraestructura;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: arco_infraestructura; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.arco_infraestructura (
    id integer,
    arco_id integer NOT NULL,
    infraestructura_id integer NOT NULL,
    created_at timestamp without time zone
);


ALTER TABLE public.arco_infraestructura OWNER TO postgres;

--
-- Name: arco_material; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.arco_material (
    id integer NOT NULL,
    arco_id integer NOT NULL,
    material_id integer NOT NULL,
    cantidad numeric(12,2) DEFAULT 1 NOT NULL,
    foto character varying(255),
    serie character varying(120),
    creado_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.arco_material OWNER TO postgres;

--
-- Name: arco_material_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.arco_material ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.arco_material_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: arcos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.arcos (
    id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    ubicacion_id integer NOT NULL,
    fecha_instalacion timestamp without time zone,
    lat character varying(50),
    lng character varying(50),
    estado character varying(20) DEFAULT 'Activo'::character varying NOT NULL,
    fecha_baja timestamp without time zone
);


ALTER TABLE public.arcos OWNER TO postgres;

--
-- Name: arcos_bajas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.arcos_bajas (
    id integer NOT NULL,
    arco_id integer NOT NULL,
    fecha_baja timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    motivo character varying(180) NOT NULL,
    observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tecnico_id integer
);


ALTER TABLE public.arcos_bajas OWNER TO postgres;

--
-- Name: arcos_bajas_evidencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.arcos_bajas_evidencias (
    id integer NOT NULL,
    baja_id integer NOT NULL,
    filename character varying(255) NOT NULL,
    mimetype character varying(100),
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.arcos_bajas_evidencias OWNER TO postgres;

--
-- Name: arcos_bajas_evidencias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.arcos_bajas_evidencias ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.arcos_bajas_evidencias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: arcos_bajas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.arcos_bajas ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.arcos_bajas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: arcos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.arcos ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.arcos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bitacora_checklist; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacora_checklist (
    id integer NOT NULL,
    bitacora_id integer NOT NULL,
    concepto_id integer NOT NULL,
    realizado integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.bitacora_checklist OWNER TO postgres;

--
-- Name: bitacora_checklist_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.bitacora_checklist ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.bitacora_checklist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: bitacoras_arco; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bitacoras_arco (
    id integer NOT NULL,
    arco_id integer NOT NULL,
    observaciones text,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tecnico_id integer
);


ALTER TABLE public.bitacoras_arco OWNER TO postgres;

--
-- Name: bitacoras_arco_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.bitacoras_arco ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.bitacoras_arco_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: checklist_conceptos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.checklist_conceptos (
    id integer NOT NULL,
    nombre character varying(255) NOT NULL,
    activo integer DEFAULT 1 NOT NULL
);


ALTER TABLE public.checklist_conceptos OWNER TO postgres;

--
-- Name: checklist_conceptos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.checklist_conceptos ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.checklist_conceptos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: formatos_mantenimiento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.formatos_mantenimiento (
    id integer NOT NULL,
    revision_id integer,
    arco_id integer,
    tipo character varying(30) NOT NULL,
    datos jsonb DEFAULT '{}'::jsonb NOT NULL,
    creado_por character varying(120),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tecnico_id integer
);


ALTER TABLE public.formatos_mantenimiento OWNER TO postgres;

--
-- Name: formatos_mantenimiento_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.formatos_mantenimiento ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.formatos_mantenimiento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: infraestructura_material; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.infraestructura_material (
    id integer NOT NULL,
    infraestructura_id integer NOT NULL,
    material_id integer NOT NULL,
    cantidad numeric(12,2) DEFAULT 1 NOT NULL,
    serie character varying(120),
    fecha_instalacion timestamp without time zone
);


ALTER TABLE public.infraestructura_material OWNER TO postgres;

--
-- Name: infraestructura_material_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.infraestructura_material ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.infraestructura_material_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: infraestructura_nodos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.infraestructura_nodos (
    id integer NOT NULL,
    tipo character varying(40) DEFAULT 'Puente/Poste'::character varying NOT NULL,
    nombre character varying(150) NOT NULL,
    ubicacion_id integer,
    lat character varying(50),
    lng character varying(50),
    descripcion text,
    created_at timestamp without time zone,
    creado_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.infraestructura_nodos OWNER TO postgres;

--
-- Name: infraestructura_nodos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.infraestructura_nodos ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.infraestructura_nodos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: infraestructura_revision_evidencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.infraestructura_revision_evidencias (
    id integer NOT NULL,
    revision_id integer NOT NULL,
    filename character varying(255) NOT NULL,
    mimetype character varying(100),
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.infraestructura_revision_evidencias OWNER TO postgres;

--
-- Name: infraestructura_revision_evidencias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.infraestructura_revision_evidencias ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.infraestructura_revision_evidencias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: infraestructura_revision_material; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.infraestructura_revision_material (
    id integer NOT NULL,
    revision_id integer NOT NULL,
    material_id integer NOT NULL,
    cantidad numeric(12,2) DEFAULT 1 NOT NULL,
    serie character varying(120)
);


ALTER TABLE public.infraestructura_revision_material OWNER TO postgres;

--
-- Name: infraestructura_revision_material_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.infraestructura_revision_material ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.infraestructura_revision_material_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: infraestructura_revisiones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.infraestructura_revisiones (
    id integer NOT NULL,
    infraestructura_id integer NOT NULL,
    fecha_mantenimiento timestamp without time zone NOT NULL,
    tipo_mantenimiento character varying(20) DEFAULT 'Correctivo'::character varying NOT NULL,
    observaciones text,
    created_at timestamp without time zone,
    tecnico_id integer
);


ALTER TABLE public.infraestructura_revisiones OWNER TO postgres;

--
-- Name: infraestructura_revisiones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.infraestructura_revisiones ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.infraestructura_revisiones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: materiales; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.materiales (
    id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    medida character varying(30) DEFAULT 'pz'::character varying NOT NULL,
    foto character varying(255),
    serie character varying(120)
);


ALTER TABLE public.materiales OWNER TO postgres;

--
-- Name: materiales_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.materiales ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.materiales_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: revision_evidencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.revision_evidencias (
    id integer NOT NULL,
    revision_id integer NOT NULL,
    filename character varying(255) NOT NULL,
    mimetype character varying(100),
    created_at timestamp without time zone,
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.revision_evidencias OWNER TO postgres;

--
-- Name: revision_evidencias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.revision_evidencias ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.revision_evidencias_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: revision_material; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.revision_material (
    id integer NOT NULL,
    revision_id integer NOT NULL,
    arco_material_id integer,
    material_id integer NOT NULL,
    cantidad numeric(12,2) DEFAULT 1 NOT NULL,
    foto character varying(255),
    serie character varying(120),
    accion character varying(20) DEFAULT 'cambio'::character varying NOT NULL
);


ALTER TABLE public.revision_material OWNER TO postgres;

--
-- Name: revision_material_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.revision_material ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.revision_material_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: revisiones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.revisiones (
    id integer NOT NULL,
    arco_id integer NOT NULL,
    fecha_mantenimiento timestamp without time zone NOT NULL,
    tipo_mantenimiento character varying(20) DEFAULT 'Correctivo'::character varying NOT NULL,
    observaciones text,
    tecnico_id integer
);


ALTER TABLE public.revisiones OWNER TO postgres;

--
-- Name: revisiones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.revisiones ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.revisiones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: tecnicos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tecnicos (
    id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    telefono character varying(30),
    puesto character varying(80),
    activo integer DEFAULT 1 NOT NULL,
    eliminado integer DEFAULT 0 NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.tecnicos OWNER TO postgres;

--
-- Name: tecnicos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.tecnicos ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.tecnicos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: ubicaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ubicaciones (
    id integer NOT NULL,
    nombre character varying(120) NOT NULL,
    lat character varying(50),
    lng character varying(50)
);


ALTER TABLE public.ubicaciones OWNER TO postgres;

--
-- Name: ubicaciones_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.ubicaciones ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.ubicaciones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(100) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(30) DEFAULT 'user'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.users ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Data for Name: arco_infraestructura; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.arco_infraestructura (id, arco_id, infraestructura_id, created_at) FROM stdin;
2	33	1	2026-05-25 00:09:38
3	5	2	2026-05-27 01:17:10
4	31	2	2026-05-27 01:17:10
6	22	3	2026-05-31 03:33:53
\.


--
-- Data for Name: arco_material; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.arco_material (id, arco_id, material_id, cantidad, foto, serie, creado_at) FROM stdin;
20	6	11	2.00	\N	\N	2026-07-10 23:50:12.438669
21	6	13	2.00	\N	\N	2026-07-10 23:50:12.438669
22	6	12	2.00	\N	\N	2026-07-10 23:50:12.438669
23	6	9	1.00	\N	\N	2026-07-10 23:50:12.438669
24	6	10	1.00	\N	\N	2026-07-10 23:50:12.438669
25	6	8	1.00	\N	\N	2026-07-10 23:50:12.438669
26	6	16	10.00	\N	\N	2026-07-10 23:50:12.438669
27	6	15	5.00	\N	\N	2026-07-10 23:50:12.438669
28	6	14	5.00	\N	\N	2026-07-10 23:50:12.438669
29	7	11	2.00	\N	\N	2026-07-10 23:50:12.438669
30	7	13	2.00	\N	\N	2026-07-10 23:50:12.438669
31	7	15	4.00	\N	\N	2026-07-10 23:50:12.438669
32	7	14	4.00	\N	\N	2026-07-10 23:50:12.438669
33	7	16	14.00	\N	\N	2026-07-10 23:50:12.438669
34	7	8	1.00	\N	\N	2026-07-10 23:50:12.438669
35	7	12	2.00	\N	\N	2026-07-10 23:50:12.438669
36	7	10	1.00	\N	\N	2026-07-10 23:50:12.438669
37	7	9	1.00	\N	\N	2026-07-10 23:50:12.438669
239	3	7	2.00	\N	9E0F44CPAJFFE7B	2026-07-10 23:50:12.438669
240	3	10	1.00	\N	370-20-12-0287	2026-07-10 23:50:12.438669
241	3	7	1.00	\N	8B0159EAAJ3FAD7	2026-07-10 23:50:12.438669
242	3	10	1.00	\N	370-11-41-0260	2026-07-10 23:50:12.438669
243	3	8	1.00	\N		2026-07-10 23:50:12.438669
244	3	9	1.00	\N		2026-07-10 23:50:12.438669
245	3	11	1.00	\N		2026-07-10 23:50:12.438669
246	3	11	1.00	\N		2026-07-10 23:50:12.438669
247	3	11	1.00	\N		2026-07-10 23:50:12.438669
248	3	11	1.00	\N		2026-07-10 23:50:12.438669
261	31	11	1.00	\N	\N	2026-07-10 23:50:12.438669
262	31	11	1.00	\N	\N	2026-07-10 23:50:12.438669
263	31	15	4.00	\N	\N	2026-07-10 23:50:12.438669
264	31	14	4.00	\N	\N	2026-07-10 23:50:12.438669
265	31	8	1.00	\N	\N	2026-07-10 23:50:12.438669
266	31	10	1.00	\N	\N	2026-07-10 23:50:12.438669
267	31	13	1.00	\N	5661015500079	2026-07-10 23:50:12.438669
268	31	13	1.00	\N	5661015500080	2026-07-10 23:50:12.438669
269	31	9	1.00	\N	5661001000009	2026-07-10 23:50:12.438669
284	21	11	1.00	\N	\N	2026-07-10 23:50:12.438669
285	21	13	1.00	\N	\N	2026-07-10 23:50:12.438669
286	21	13	1.00	\N	\N	2026-07-10 23:50:12.438669
287	21	8	1.00	\N	\N	2026-07-10 23:50:12.438669
288	21	11	1.00	\N	\N	2026-07-10 23:50:12.438669
289	21	8	1.00	\N	\N	2026-07-10 23:50:12.438669
290	21	10	1.00	\N	\N	2026-07-10 23:50:12.438669
322	33	11	1.00	\N	\N	2026-07-10 23:50:12.438669
323	33	11	1.00	\N	\N	2026-07-10 23:50:12.438669
324	33	18	15.00	\N	\N	2026-07-10 23:50:12.438669
325	33	16	14.00	\N	\N	2026-07-10 23:50:12.438669
326	33	8	1.00	\N	\N	2026-07-10 23:50:12.438669
327	33	19	1.00	\N	\N	2026-07-10 23:50:12.438669
328	33	10	1.00	\N	370-14-22-0101	2026-07-10 23:50:12.438669
329	33	24	1.00	\N	\N	2026-07-10 23:50:12.438669
330	33	20	1.00	\N	\N	2026-07-10 23:50:12.438669
344	34	11	1.00	\N	\N	2026-07-10 23:50:12.438669
345	34	11	1.00	\N	\N	2026-07-10 23:50:12.438669
346	34	13	1.00	\N	\N	2026-07-10 23:50:12.438669
347	34	13	1.00	\N	\N	2026-07-10 23:50:12.438669
348	34	15	2.50	\N	\N	2026-07-10 23:50:12.438669
349	34	14	2.50	\N	\N	2026-07-10 23:50:12.438669
350	34	16	16.00	\N	\N	2026-07-10 23:50:12.438669
351	34	25	1.00	\N	\N	2026-07-10 23:50:12.438669
352	34	25	1.00	\N	\N	2026-07-10 23:50:12.438669
353	34	26	1.00	\N	\N	2026-07-10 23:50:12.438669
354	34	20	1.00	\N	\N	2026-07-10 23:50:12.438669
355	34	21	1.00	\N	\N	2026-07-10 23:50:12.438669
356	34	12	1.00	\N	\N	2026-07-10 23:50:12.438669
385	5	10	1.00	\N	\N	2026-07-10 23:50:12.438669
386	5	9	1.00	\N	PS306GF-UPS-15A2408MX024	2026-07-10 23:50:12.438669
387	5	11	1.00	\N	\N	2026-07-10 23:50:12.438669
388	5	11	1.00	\N	\N	2026-07-10 23:50:12.438669
389	5	8	1.00	\N	\N	2026-07-10 23:50:12.438669
390	5	16	18.00	\N	\N	2026-07-10 23:50:12.438669
391	5	11	1.00	\N	\N	2026-07-10 23:50:12.438669
392	5	11	1.00	\N	\N	2026-07-10 23:50:12.438669
393	5	13	1.00	\N	MX05452857	2026-07-10 23:50:12.438669
394	5	13	1.00	\N	\N	2026-07-10 23:50:12.438669
395	5	15	2.00	\N	\N	2026-07-10 23:50:12.438669
396	5	14	2.00	\N	\N	2026-07-10 23:50:12.438669
397	5	12	1.00	\N	\N	2026-07-10 23:50:12.438669
398	5	12	1.00	\N	\N	2026-07-10 23:50:12.438669
399	35	11	1.00	\N	\N	2026-07-10 23:50:12.438669
400	35	11	1.00	\N	\N	2026-07-10 23:50:12.438669
401	35	11	1.00	\N	\N	2026-07-10 23:50:12.438669
402	35	11	1.00	\N	\N	2026-07-10 23:50:12.438669
403	35	13	1.00	\N	\N	2026-07-10 23:50:12.438669
404	35	13	1.00	\N	\N	2026-07-10 23:50:12.438669
405	35	15	2.50	\N	\N	2026-07-10 23:50:12.438669
406	35	14	2.50	\N	\N	2026-07-10 23:50:12.438669
407	35	16	35.00	\N	\N	2026-07-10 23:50:12.438669
408	35	21	1.00	\N	\N	2026-07-10 23:50:12.438669
409	35	20	1.00	\N	\N	2026-07-10 23:50:12.438669
410	35	10	1.00	\N	\N	2026-07-10 23:50:12.438669
411	35	12	1.00	\N	\N	2026-07-10 23:50:12.438669
412	35	12	1.00	\N	\N	2026-07-10 23:50:12.438669
413	35	9	1.00	\N	\N	2026-07-10 23:50:12.438669
472	36	25	1.00	\N	\N	2026-07-10 23:50:12.438669
473	36	25	1.00	\N	\N	2026-07-10 23:50:12.438669
474	36	11	1.00	\N	\N	2026-07-10 23:50:12.438669
475	36	11	1.00	\N	\N	2026-07-10 23:50:12.438669
476	36	18	10.00	\N	\N	2026-07-10 23:50:12.438669
477	36	8	1.00	\N	\N	2026-07-10 23:50:12.438669
478	36	10	1.00	\N	\N	2026-07-10 23:50:12.438669
479	36	19	1.00	\N	\N	2026-07-10 23:50:12.438669
480	36	16	15.00	\N	\N	2026-07-10 23:50:12.438669
481	32	11	1.00	\N	\N	2026-07-10 23:50:12.438669
482	32	11	1.00	\N	\N	2026-07-10 23:50:12.438669
483	32	11	1.00	\N	\N	2026-07-10 23:50:12.438669
484	32	11	1.00	\N	\N	2026-07-10 23:50:12.438669
485	32	18	8.00	\N	\N	2026-07-10 23:50:12.438669
486	32	16	30.00	\N	\N	2026-07-10 23:50:12.438669
487	32	8	1.00	\N	\N	2026-07-10 23:50:12.438669
488	32	10	1.00	\N	370-12-38-0199	2026-07-10 23:50:12.438669
489	32	10	1.00	\N	370-19-49-0864	2026-07-10 23:50:12.438669
490	32	28	1.00	\N	\N	2026-07-10 23:50:12.438669
491	32	7	1.00	\N	8B0159EAAJ81B1A	2026-07-10 23:50:12.438669
492	32	7	1.00	\N	8B0159EAAJ3FF94	2026-07-10 23:50:12.438669
493	32	17	1.00	\N	\N	2026-07-10 23:50:12.438669
494	32	9	1.00	\N	\N	2026-07-10 23:50:12.438669
497	22	13	1.00	\N	\N	2026-07-10 23:50:12.438669
498	22	13	1.00	\N	\N	2026-07-10 23:50:12.438669
555	18	11	1.00	\N	\N	2026-07-10 23:50:12.438669
556	18	11	1.00	\N	\N	2026-07-10 23:50:12.438669
557	18	13	1.00	\N	\N	2026-07-10 23:50:12.438669
558	18	13	1.00	\N	\N	2026-07-10 23:50:12.438669
559	18	8	1.00	\N	\N	2026-07-10 23:50:12.438669
560	18	12	1.00	\N	\N	2026-07-10 23:50:12.438669
561	18	12	1.00	\N	\N	2026-07-10 23:50:12.438669
562	18	10	1.00	\N	\N	2026-07-10 23:50:12.438669
563	18	9	1.00	\N	\N	2026-07-10 23:50:12.438669
564	18	16	15.00	\N	\N	2026-07-10 23:50:12.438669
565	18	14	2.50	\N	\N	2026-07-10 23:50:12.438669
566	18	15	2.50	\N	\N	2026-07-10 23:50:12.438669
567	18	30	4.00	\N	\N	2026-07-10 23:50:12.438669
568	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
569	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
570	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
571	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
572	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
573	38	11	1.00	\N	\N	2026-07-10 23:50:12.438669
574	38	20	1.00	\N	\N	2026-07-10 23:50:12.438669
575	38	10	1.00	\N	\N	2026-07-10 23:50:12.438669
576	38	26	1.00	\N	\N	2026-07-10 23:50:12.438669
577	38	18	20.00	\N	\N	2026-07-10 23:50:12.438669
578	38	9	1.00	\N	\N	2026-07-10 23:50:12.438669
579	38	29	1.00	\N	\N	2026-07-10 23:50:12.438669
580	38	30	10.00	\N	\N	2026-07-10 23:50:12.438669
581	37	11	1.00	\N	\N	2026-07-10 23:50:12.438669
582	37	11	1.00	\N	\N	2026-07-10 23:50:12.438669
583	37	13	1.00	\N	\N	2026-07-10 23:50:12.438669
584	37	13	1.00	\N	\N	2026-07-10 23:50:12.438669
585	37	15	1.50	\N	\N	2026-07-10 23:50:12.438669
586	37	14	1.50	\N	\N	2026-07-10 23:50:12.438669
587	37	16	15.00	\N	\N	2026-07-10 23:50:12.438669
588	37	25	1.00	\N	\N	2026-07-10 23:50:12.438669
589	37	25	1.00	\N	\N	2026-07-10 23:50:12.438669
590	37	29	1.00	\N	\N	2026-07-10 23:50:12.438669
591	37	20	1.00	\N	\N	2026-07-10 23:50:12.438669
592	37	12	1.00	\N	\N	2026-07-10 23:50:12.438669
593	37	12	1.00	\N	\N	2026-07-10 23:50:12.438669
594	37	26	1.00	\N	\N	2026-07-10 23:50:12.438669
595	5	13	1.00	\N	5661015500076	2026-07-10 23:50:12.438669
596	5	13	1.00	\N	5661015500077	2026-07-10 23:50:12.438669
631	39	13	1.00	\N	\N	2026-07-10 23:50:12.438669
632	39	13	1.00	\N	\N	2026-07-10 23:50:12.438669
633	39	11	1.00	\N	\N	2026-07-10 23:50:12.438669
634	39	11	1.00	\N	\N	2026-07-10 23:50:12.438669
635	39	15	2.50	\N	\N	2026-07-10 23:50:12.438669
636	39	14	2.50	\N	\N	2026-07-10 23:50:12.438669
637	39	16	14.00	\N	\N	2026-07-10 23:50:12.438669
638	39	29	1.00	\N	\N	2026-07-10 23:50:12.438669
639	39	12	1.00	\N	\N	2026-07-10 23:50:12.438669
640	39	12	1.00	\N	\N	2026-07-10 23:50:12.438669
642	39	9	1.00	\N	\N	2026-07-10 23:50:12.438669
643	39	30	4.00	\N	\N	2026-07-10 23:50:12.438669
644	23	11	1.00	\N	\N	2026-07-10 23:50:12.438669
645	23	11	1.00	\N	\N	2026-07-10 23:50:12.438669
646	23	13	1.00	\N	\N	2026-07-10 23:50:12.438669
647	23	13	1.00	\N	\N	2026-07-10 23:50:12.438669
648	23	16	30.00	\N	\N	2026-07-10 23:50:12.438669
649	23	20	1.00	\N	\N	2026-07-10 23:50:12.438669
650	23	29	1.00	\N	\N	2026-07-10 23:50:12.438669
651	23	26	1.00	\N	\N	2026-07-10 23:50:12.438669
652	23	30	8.00	\N	\N	2026-07-10 23:50:12.438669
653	23	18	10.00	\N	\N	2026-07-10 23:50:12.438669
654	40	20	1.00	\N	\N	2026-07-10 23:50:12.438669
655	40	12	1.00	\N	\N	2026-07-10 23:50:12.438669
656	40	12	1.00	\N	\N	2026-07-10 23:50:12.438669
657	40	13	1.00	\N	\N	2026-07-10 23:50:12.438669
658	40	13	1.00	\N	\N	2026-07-10 23:50:12.438669
659	40	15	2.50	\N	\N	2026-07-10 23:50:12.438669
660	40	14	2.50	\N	\N	2026-07-10 23:50:12.438669
661	40	16	13.00	\N	\N	2026-07-10 23:50:12.438669
662	40	10	1.00	\N	\N	2026-07-10 23:50:12.438669
663	40	29	1.00	\N	\N	2026-07-10 23:50:12.438669
664	40	9	1.00	\N	\N	2026-07-10 23:50:12.438669
665	41	13	1.00	\N	\N	2026-07-10 23:50:12.438669
666	41	13	1.00	\N	\N	2026-07-10 23:50:12.438669
667	41	11	1.00	\N	\N	2026-07-10 23:50:12.438669
668	41	11	1.00	\N	\N	2026-07-10 23:50:12.438669
669	41	15	2.50	\N	\N	2026-07-10 23:50:12.438669
670	41	14	2.50	\N	\N	2026-07-10 23:50:12.438669
671	41	16	15.00	\N	\N	2026-07-10 23:50:12.438669
672	41	29	1.00	\N	\N	2026-07-10 23:50:12.438669
673	41	12	1.00	\N	\N	2026-07-10 23:50:12.438669
674	41	12	1.00	\N	\N	2026-07-10 23:50:12.438669
675	41	20	1.00	\N	\N	2026-07-10 23:50:12.438669
676	41	9	1.00	\N	\N	2026-07-10 23:50:12.438669
677	41	30	20.00	\N	\N	2026-07-10 23:50:12.438669
697	22	11	1.00	\N	\N	2026-07-10 23:50:12.438669
698	22	11	1.00	\N	\N	2026-07-10 23:50:12.438669
701	22	14	4.00	\N	\N	2026-07-10 23:50:12.438669
702	22	15	4.00	\N	\N	2026-07-10 23:50:12.438669
703	22	26	1.00	\N	\N	2026-07-10 23:50:12.438669
704	22	9	1.00	\N	\N	2026-07-10 23:50:12.438669
705	22	16	18.00	\N	\N	2026-07-10 23:50:12.438669
706	22	17	1.00	\N	\N	2026-07-10 23:50:12.438669
707	22	8	1.00	\N	\N	2026-07-10 23:50:12.438669
708	22	12	1.00	\N	\N	2026-07-10 23:50:12.438669
709	22	12	1.00	\N	\N	2026-07-10 23:50:12.438669
710	22	30	6.00	\N	\N	2026-07-10 23:50:12.438669
711	39	26	1.00	\N	\N	2026-07-10 23:50:12.438669
712	39	20	1.00	\N	\N	2026-07-10 23:50:12.438669
713	42	13	1.00	\N	\N	2026-07-10 23:50:12.438669
714	42	13	1.00	\N	\N	2026-07-10 23:50:12.438669
715	42	11	1.00	\N	\N	2026-07-10 23:50:12.438669
716	42	11	1.00	\N	\N	2026-07-10 23:50:12.438669
717	42	15	6.00	\N	\N	2026-07-10 23:50:12.438669
718	42	14	6.00	\N	\N	2026-07-10 23:50:12.438669
719	42	17	1.00	\N	\N	2026-07-10 23:50:12.438669
721	42	12	1.00	\N	\N	2026-07-10 23:50:12.438669
722	42	12	1.00	\N	\N	2026-07-10 23:50:12.438669
723	42	9	1.00	\N	\N	2026-07-10 23:50:12.438669
724	42	30	6.00	\N	\N	2026-07-10 23:50:12.438669
725	42	16	16.00	\N	\N	2026-07-10 23:50:12.438669
726	42	31	1.00	\N	\N	2026-07-10 23:50:12.438669
727	43	11	1.00	\N	\N	2026-07-10 23:50:12.438669
728	43	11	1.00	\N	\N	2026-07-10 23:50:12.438669
729	43	13	1.00	\N	\N	2026-07-10 23:50:12.438669
730	43	13	1.00	\N	\N	2026-07-10 23:50:12.438669
731	43	15	5.00	\N	\N	2026-07-10 23:50:12.438669
732	43	14	5.00	\N	\N	2026-07-10 23:50:12.438669
733	43	16	17.00	\N	\N	2026-07-10 23:50:12.438669
734	43	31	1.00	\N	\N	2026-07-10 23:50:12.438669
735	43	17	1.00	\N	\N	2026-07-10 23:50:12.438669
736	43	12	1.00	\N	\N	2026-07-10 23:50:12.438669
737	43	12	1.00	\N	\N	2026-07-10 23:50:12.438669
738	43	26	1.00	\N	\N	2026-07-10 23:50:12.438669
739	43	30	6.00	\N	\N	2026-07-10 23:50:12.438669
740	44	13	1.00	\N	\N	2026-07-10 23:50:12.438669
741	44	13	1.00	\N	\N	2026-07-10 23:50:12.438669
742	44	11	1.00	\N	\N	2026-07-10 23:50:12.438669
743	44	11	1.00	\N	\N	2026-07-10 23:50:12.438669
744	44	15	3.00	\N	\N	2026-07-10 23:50:12.438669
745	44	14	3.00	\N	\N	2026-07-10 23:50:12.438669
746	44	16	17.00	\N	\N	2026-07-10 23:50:12.438669
747	44	31	1.00	\N	\N	2026-07-10 23:50:12.438669
748	44	17	1.00	\N	\N	2026-07-10 23:50:12.438669
749	44	12	1.00	\N	\N	2026-07-10 23:50:12.438669
750	44	12	1.00	\N	\N	2026-07-10 23:50:12.438669
751	44	26	1.00	\N	\N	2026-07-10 23:50:12.438669
752	44	9	1.00	\N	\N	2026-07-10 23:50:12.438669
753	44	30	6.00	\N	\N	2026-07-10 23:50:12.438669
754	45	13	1.00	\N	\N	2026-07-11 00:22:11.508166
755	45	13	1.00	\N	\N	2026-07-11 00:22:11.508166
756	45	9	1.00	\N	PS306GF-UPS-15A2404MX053	2026-07-11 00:22:11.508166
757	45	26	1.00	\N	37023090291	2026-07-11 00:22:11.508166
758	45	11	1.00	\N	\N	2026-07-11 00:22:11.508166
759	45	11	1.00	\N	\N	2026-07-11 00:22:11.508166
760	45	15	4.00	\N	\N	2026-07-11 00:22:11.508166
761	45	14	4.00	\N	\N	2026-07-11 00:22:11.508166
762	45	16	18.00	\N	\N	2026-07-11 00:22:11.508166
763	45	31	1.00	\N	\N	2026-07-11 00:22:11.508166
764	45	12	1.00	\N	\N	2026-07-11 00:22:11.508166
765	45	12	1.00	\N	\N	2026-07-11 00:22:11.508166
766	45	30	7.00	\N	\N	2026-07-11 00:22:11.508166
767	45	17	1.00	\N	\N	2026-07-11 00:22:11.508166
508	11	9	1.00	\N	\N	2026-07-10 23:50:12.438669
509	11	10	1.00	\N	\N	2026-07-10 23:50:12.438669
510	11	8	1.00	\N	\N	2026-07-10 23:50:12.438669
511	11	16	18.00	\N	\N	2026-07-10 23:50:12.438669
512	11	17	1.00	\N	\N	2026-07-10 23:50:12.438669
513	11	11	1.00	\N	\N	2026-07-10 23:50:12.438669
514	11	11	1.00	\N	\N	2026-07-10 23:50:12.438669
515	11	13	1.00	\N	\N	2026-07-10 23:50:12.438669
516	11	13	1.00	\N	\N	2026-07-10 23:50:12.438669
517	11	12	1.00	\N	\N	2026-07-10 23:50:12.438669
518	11	12	1.00	\N	\N	2026-07-10 23:50:12.438669
519	11	17	1.00	\N	\N	2026-07-10 23:50:12.438669
520	11	14	2.50	\N	\N	2026-07-10 23:50:12.438669
521	11	15	2.50	\N	\N	2026-07-10 23:50:12.438669
769	11	30	6.00	\N	\N	2026-07-12 19:46:52.642533
\.


--
-- Data for Name: arcos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.arcos (id, nombre, ubicacion_id, fecha_instalacion, lat, lng, estado, fecha_baja) FROM stdin;
3	Cinca	3	2019-10-29 00:00:00	17.507284	-99.475129	Activo	\N
5	Bulevar de las Naciones	4	2025-12-20 04:00:00	16.791057	-99.802755	Activo	\N
6	Salida Taxco	5	2025-12-03 00:00:00	\N	\N	Activo	\N
7	Entrada Taxco	5	2025-12-02 00:00:00	\N	\N	Activo	\N
18	Entrada Iguala	6	2026-03-27 00:00:00	18.314115	-99.501801	Activo	\N
21	Libramiento Tixtla	3	2022-10-12 00:00:00	17.592812	-99.501286	Activo	\N
22	Horquetas	4	2026-03-21 00:00:00	16.755672	-99.574117	Activo	\N
23	San Juan de los llanos	9	2026-04-01 00:00:00	16.6832135	-98.446532,3a	Activo	\N
31	Cayaco - Las Cruces	4	2017-06-17 21:04:00	16.8651166	99.8103806,3a	Activo	\N
32	Lazaro Cardenas	3	2019-12-06 00:13:00	17.583102	-99.515489	Activo	\N
33	Hospital	3	2021-01-16 01:34:00	17.605768	-99.522409	Activo	\N
34	Tierras Pietras - Tixtla	3	2021-09-29 16:04:00	17.603447	-99.515833	Activo	\N
35	Autopista Chilpancingo - Acapulco	3	2021-11-11 16:30:00	17.513030	-99.481361	Activo	\N
36	Chichihualco	3	2023-11-16 15:40:00	17.5917693	-99.5192992	Activo	\N
37	Amojileca	3	2021-09-29 15:04:00	17.5551348	-99.525065	Activo	\N
38	Renacimiento	4	2024-04-30 02:35:00	16.8981042	-99.8306721	Activo	\N
39	OMETEPEC-IGUALAPA	9	2021-02-20 16:40:00	16.708102	98.428594	Activo	\N
40	OMETEPEC-LAS IGUANAS	9	2021-10-21 15:30:00	16.6809867	-98.4242755,3a	Activo	\N
41	OMETEPEC-XOCHIS	9	2021-10-22 14:40:00	16.6987012	-98.3869374,3a	Activo	\N
42	AEROPUERTO-ZIHUATANEJO	7	2004-10-04 15:00:00	17.647651	-101.52811409	Activo	\N
43	LAZARO CARDENAS-IXTAPA	7	2024-10-03 14:04:00	17.6593304	-101.5748369	Activo	\N
44	ZIHUATANEJO-MIRADOR	7	2024-10-03 15:04:00	17.6785773	101.6021398	Activo	\N
45	Entrada Coyuca	10	2024-06-11 03:14:00	16.993311	-100.070654	Baja	2026-07-11 08:23:00
11	Cima	4	2026-02-13 00:00:00	16.877297	 -99.859994	Activo	\N
\.


--
-- Data for Name: arcos_bajas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.arcos_bajas (id, arco_id, fecha_baja, motivo, observaciones, created_at, tecnico_id) FROM stdin;
1	45	2026-07-11 08:23:00	Sustitucion de arco	Un camión choco contra el poste doblando un poste que sirve como columna de carga del arco quedando inservible y el otro poste de columna tiene un ligero dobles en la base y la estrutura quedando hacia un lado de la hacera	2026-07-11 00:28:09.408493	3
\.


--
-- Data for Name: arcos_bajas_evidencias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.arcos_bajas_evidencias (id, baja_id, filename, mimetype, uploaded_at) FROM stdin;
1	1	1783751289_532bdb377ebb5432.jpg	image/jpeg	2026-07-11 00:28:09.408493
2	1	1783751289_5bdea42a1d241317.jpg	image/jpeg	2026-07-11 00:28:09.408493
3	1	1783751289_6f630bf20317ec03.jpg	image/jpeg	2026-07-11 00:28:09.408493
4	1	1783751289_2262f6eaf0928655.jpg	image/jpeg	2026-07-11 00:28:09.408493
\.


--
-- Data for Name: bitacora_checklist; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacora_checklist (id, bitacora_id, concepto_id, realizado) FROM stdin;
1	1	1	1
2	1	2	1
3	1	3	1
4	1	4	1
5	1	5	1
6	1	7	1
7	1	8	1
8	1	9	1
9	1	10	1
10	1	11	1
21	3	1	1
22	3	2	1
23	3	3	1
24	3	4	1
25	3	7	1
26	3	8	1
27	3	9	1
28	3	10	1
29	3	11	1
\.


--
-- Data for Name: bitacoras_arco; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bitacoras_arco (id, arco_id, observaciones, fecha_registro, tecnico_id) FROM stdin;
1	23	Se Realizo La instalación sin poblemas	2026-04-05 15:58:43	5
3	18		2026-04-05 20:11:39	6
\.


--
-- Data for Name: checklist_conceptos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.checklist_conceptos (id, nombre, activo) FROM stdin;
1	Instalación de Estructura del arco	1
2	Instalación de gabinete	1
3	Instalación de panel solar y batería	1
4	Antena RFID instalada y fijada	1
5	Cámara LPR instalada y enfocada	1
6	Conexión eléctrica con cable 1+1	1
7	Cableado UTP	1
8	Prueba de lectura RFID correcta	1
9	Prueba de captura de placas correcta	1
10	Servidor / envío a plataforma validado	1
11	Prueba de energía	1
12	Colocación de respaldo UPS	1
\.


--
-- Data for Name: formatos_mantenimiento; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.formatos_mantenimiento (id, revision_id, arco_id, tipo, datos, creado_por, created_at, tecnico_id) FROM stdin;
\.


--
-- Data for Name: infraestructura_material; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.infraestructura_material (id, infraestructura_id, material_id, cantidad, serie, fecha_instalacion) FROM stdin;
4	1	19	1.00	\N	2026-05-25 00:09:38
5	1	8	1.00	\N	2026-05-25 00:09:38
6	1	18	10.00	\N	2026-05-25 00:09:38
7	1	8	1.00	\N	2026-05-25 00:09:38
8	2	20	1.00	\N	2025-06-27 01:16:00
9	2	23	1.00	\N	2025-06-27 01:16:00
10	2	8	1.00	\N	2025-06-27 01:16:00
11	2	8	1.00	\N	2025-06-27 01:16:00
12	2	8	1.00	\N	2025-06-27 01:16:00
13	2	8	1.00	\N	2025-06-27 01:16:00
14	2	8	1.00	\N	2025-06-27 01:16:00
25	3	20	1.00	\N	2026-05-31 03:33:53
26	3	9	1.00	PS306GF-UPS-15A2408MX023	2026-05-31 03:33:53
27	3	13	1.00	\N	2026-05-31 03:33:53
28	3	13	1.00	\N	2026-05-31 03:33:53
29	3	15	2.50	\N	2026-05-31 03:33:53
30	3	14	2.50	\N	2026-05-31 03:33:53
31	3	29	1.00	\N	2026-05-31 03:33:53
32	3	12	1.00	\N	2026-05-31 03:33:53
33	3	12	1.00	\N	2026-05-31 03:33:53
34	3	30	5.00	\N	2026-05-31 03:33:53
\.


--
-- Data for Name: infraestructura_nodos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.infraestructura_nodos (id, tipo, nombre, ubicacion_id, lat, lng, descripcion, created_at, creado_at) FROM stdin;
1	Puente/Poste	Salto Hospital Poste 12	3	17.605125	-99.520715	\N	2026-05-24 17:53:14	2026-07-10 23:50:12.438669
2	Sitio/Torre	Cumbres	4	16.810472	-99.811859	\N	2026-05-27 01:17:10	2026-07-10 23:50:12.438669
3	Sitio/Torre	Cuartel de Policias	4	16.755646	-99.572439	\N	2026-05-31 03:29:47	2026-07-10 23:50:12.438669
\.


--
-- Data for Name: infraestructura_revision_evidencias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.infraestructura_revision_evidencias (id, revision_id, filename, mimetype, uploaded_at) FROM stdin;
1	4	1780250109_6a1c75fdd98917.13988036.jpg	image/jpeg	2026-05-31 11:55:09
2	4	1780250109_6a1c75fddf87c4.27906511.jpg	image/jpeg	2026-05-31 11:55:09
3	4	1780250109_6a1c75fde1f7a6.61719002.jpg	image/jpeg	2026-05-31 11:55:09
\.


--
-- Data for Name: infraestructura_revision_material; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.infraestructura_revision_material (id, revision_id, material_id, cantidad, serie) FROM stdin;
5	4	13	1.00	
6	4	13	1.00	
\.


--
-- Data for Name: infraestructura_revisiones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.infraestructura_revisiones (id, infraestructura_id, fecha_mantenimiento, tipo_mantenimiento, observaciones, created_at, tecnico_id) FROM stdin;
4	3	2026-04-25 03:54:00	Correctivo	Se cambio baterias por medio uso	2026-05-31 11:55:09	3
\.


--
-- Data for Name: materiales; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.materiales (id, nombre, medida, foto, serie) FROM stdin;
7	Camara LPR	pz	1764729971_camara lpr.png	\N
8	Enlace Ubiquiti M5	pz	1779659842_Enlace Ubiquiti M5.png	\N
9	Switch 5 Puertos Controntolador Solar Wiltek	pz	1764729996_switch 5 puertos panel solar.png	\N
10	SpeedWay 4 Conectores	pz	1764730013_speedway.jpg	\N
11	Antena Yagi RFID 5 Elementos	pz	1780038784_Antena Yagi de 5 Elementos.png	\N
12	Panel Solar Epcom 19V 150W	pz	1764730052_PANEL SOLAR.PNG	\N
13	Bateria Solar	pz	1764730062_bateria.png	\N
14	Cable FotoVoltaico Positivo	m	1764730460_cable fotovoltaico positivo.png	\N
15	Cable FotoVoltaico Negativo	m	1764730481_cable fotovoltaico negativo.png	\N
16	Cable TNC	m	1764731117_Cable TNC.png	\N
17	Gabinete Doble Cerradura	pz	1772599896_Gabinete doble Cerradura.jpg	\N
18	Cable 1+1	m	1772600756_cable 1+1.jpg	\N
19	Gabinete 1 nivel	pz	1779087212_gabinete 1 nivel.png	\N
20	Gabinete con soporte en forma L	pz	1779658323_Gabinete con soporte en forma de L.jpg	\N
21	Enlace Ubiquiti AC	pz	1779659869_Enlace Ubiquiti AC.png	\N
22	Switch 8 Puetos Controlador Solar Wiltek	pz	1779659937_switch 8 puertos.jpg	\N
23	Switch 16 Puertos Wiltek	pz	1779659957_switch 16 puertos.png	\N
24	Switch 5 Puertos TP Link	pz	1779661807_switch 5 puertos TP Link.jpg	\N
25	Controlador Solar POE	pz	1779663102_Controlador Solar POE.png	\N
26	SpeedWay 2 Conectores	pz	1779663580_Speedway 2 Conectores .jpg	\N
27	Switch 24 Puertos cisco	pz	1780024851_switch 24 puertos cisco.jpg	\N
28	Swich 5 puertos HikVision	pz	1780203061_switch 5 puertos Hikvision.jpg	\N
29	Enlace Ubiquiti AC ISO	pz	1780217319_enlace Ubiquiti AC ISO.png	\N
30	UTP Cat 5e Exterior	m	1780217657_Cable UTP Cat 5e Exterior.png	\N
31	Cambium Force 300	pz	1780271136_Enlace Cambium force 300.png	\N
32	Enlace Cambium Force 425	pz	1783896352_Enlace ePMP_Force_425.png	\N
\.


--
-- Data for Name: revision_evidencias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.revision_evidencias (id, revision_id, filename, mimetype, created_at, uploaded_at) FROM stdin;
78	65	1779077275_6a0a909b51b75.jpg	image/jpeg	2026-05-17 22:07:55	2026-05-17 22:07:55
79	65	1779077275_6a0a909b55be9.jpg	image/jpeg	2026-05-17 22:07:55	2026-05-17 22:07:55
81	67	1779087638_6a0ab9169530a.jpg	image/jpeg	2026-05-18 01:00:38	2026-05-18 01:00:38
86	70	1779865267_6a1696b3ab653.jpg	image/jpeg	2026-05-27 01:01:07	2026-05-27 01:01:07
87	70	1779865267_6a1696b3ae675.jpg	image/jpeg	2026-05-27 01:01:07	2026-05-27 01:01:07
88	70	1779865267_6a1696b3afed1.jpg	image/jpeg	2026-05-27 01:01:07	2026-05-27 01:01:07
89	70	1779865267_6a1696b3b165a.jpg	image/jpeg	2026-05-27 01:01:07	2026-05-27 01:01:07
90	70	1779865267_6a1696b3b2ab2.jpg	image/jpeg	2026-05-27 01:01:07	2026-05-27 01:01:07
96	76	1780211404_6a1bdecc1bf55.jpg	image/jpeg	2026-05-31 01:10:04	2026-05-31 01:10:04
97	76	1780211404_6a1bdecc1d9d9.jpg	image/jpeg	2026-05-31 01:10:04	2026-05-31 01:10:04
98	76	1780211404_6a1bdecc1e866.jpg	image/jpeg	2026-05-31 01:10:04	2026-05-31 01:10:04
99	76	1780211404_6a1bdecc20319.jpg	image/jpeg	2026-05-31 01:10:04	2026-05-31 01:10:04
100	76	1780211404_6a1bdecc21172.jpg	image/jpeg	2026-05-31 01:10:04	2026-05-31 01:10:04
101	79	1780216323_6a1bf20389942.jpg	image/jpeg	2026-05-31 02:32:03	2026-05-31 02:32:03
102	81	1780258497_6a1c96c14647d6.05367476.jpg	image/jpeg	2026-05-31 14:14:57	2026-05-31 14:14:57
103	81	1780258497_6a1c96c14a3063.86558227.jpg	image/jpeg	2026-05-31 14:14:57	2026-05-31 14:14:57
104	82	1780265572_6a1cb264770d82.14592795.jpg	image/jpeg	2026-05-31 16:12:52	2026-05-31 16:12:52
105	83	1780269150_6a1cc05e75e060.98323092.jpg	image/jpeg	2026-05-31 17:12:30	2026-05-31 17:12:30
106	83	1780269150_6a1cc05e780878.75085299.jpg	image/jpeg	2026-05-31 17:12:30	2026-05-31 17:12:30
107	84	1780272041_6a1ccba9d48162.76494754.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
108	84	1780272041_6a1ccba9d75215.26657019.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
109	84	1780272041_6a1ccba9d882e3.88109745.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
110	84	1780272041_6a1ccba9d9b516.97328373.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
111	84	1780272041_6a1ccba9dad495.19569479.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
112	84	1780272041_6a1ccba9dbfc54.38078989.jpg	image/jpeg	2026-05-31 18:00:41	2026-05-31 18:00:41
113	85	1780286343_6a1d03879a8a28.46617032.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
114	85	1780286343_6a1d03879e96e8.81994781.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
115	85	1780286343_6a1d0387a04f50.69801048.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
116	85	1780286343_6a1d0387a1c5c7.18320891.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
117	85	1780286343_6a1d0387a35df1.89074428.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
118	85	1780286343_6a1d0387a5e719.43446008.jpg	image/jpeg	2026-05-31 21:59:03	2026-05-31 21:59:03
119	86	1780293172_6a1d1e34112af1.58038565.jpg	image/jpeg	2026-05-31 23:52:52	2026-05-31 23:52:52
120	86	1780293172_6a1d1e3413cf88.39504541.jpg	image/jpeg	2026-05-31 23:52:52	2026-05-31 23:52:52
121	86	1780293172_6a1d1e34156c13.71653275.jpg	image/jpeg	2026-05-31 23:52:52	2026-05-31 23:52:52
122	86	1780293172_6a1d1e34174461.73483966.jpg	image/jpeg	2026-05-31 23:52:52	2026-05-31 23:52:52
123	86	1780293172_6a1d1e3418f0f5.09390810.jpg	image/jpeg	2026-05-31 23:52:52	2026-05-31 23:52:52
\.


--
-- Data for Name: revision_material; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.revision_material (id, revision_id, arco_material_id, material_id, cantidad, foto, serie, accion) FROM stdin;
78	65	267	13	1.00	\N	5661015500079	cambio
79	65	268	13	1.00	\N	5661015500080	cambio
81	67	491	7	1.00	\N	8B0159EAAJ81B1A	cambio
86	70	595	13	1.00	\N	5661015500076	cambio
87	70	596	13	1.00	\N	5661015500077	cambio
91	74	\N	9	1.00	\N	\N	cambio
93	76	487	8	1.00	\N	\N	cambio
96	78	497	13	1.00	\N	\N	cambio
97	78	498	13	1.00	\N	\N	cambio
98	79	515	13	1.00	\N	\N	cambio
99	79	516	13	1.00	\N	\N	cambio
100	80	580	30	10.00	\N	\N	cambio
101	81	657	13	1.00	\N	\N	cambio
102	81	658	13	1.00	\N	\N	cambio
103	82	665	13	1.00	\N	\N	cambio
104	82	666	13	1.00	\N	\N	cambio
105	83	631	13	1.00	\N	\N	cambio
106	83	632	13	1.00	\N	\N	cambio
107	83	712	17	1.00	\N	\N	cambio
108	83	711	10	1.00	\N	\N	cambio
109	83	640	12	1.00	\N	\N	cambio
110	83	639	12	1.00	\N	\N	cambio
111	83	635	15	2.50	\N	\N	cambio
112	83	636	14	2.50	\N	\N	cambio
113	84	713	13	1.00	\N	\N	cambio
114	84	714	13	1.00	\N	\N	cambio
115	85	740	13	1.00	\N	\N	cambio
116	85	741	13	1.00	\N	\N	cambio
117	86	729	13	1.00	\N	\N	cambio
118	86	730	13	1.00	\N	\N	cambio
\.


--
-- Data for Name: revisiones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.revisiones (id, arco_id, fecha_mantenimiento, tipo_mantenimiento, observaciones, tecnico_id) FROM stdin;
65	31	2026-05-12 13:03:00	Correctivo	Se cambiaron baterias por deterioro 	3
67	32	2026-05-07 15:00:00	Correctivo	Se reparo camara LPR Sur Norte y se reparo conecto de luz AC	3
70	5	2026-05-25 11:00:00	Correctivo	Se cambiaron las baterias por que ya no retienen carga, comprobado con el tester de baterias	3
74	32	2026-05-29 11:14:00	Correctivo	Se cambio el swich uno de la marca hikvision por un wiltek porque ya se bloqueo y dejo de pasar datos de red	3
76	32	2025-12-29 04:22:00	Correctivo	Cambio de enlace por daño	2
78	22	2026-04-25 02:19:00	Correctivo	Cambio de baterias por incendio lo que ocaciono que se inflaran las baterias	3
79	11	2026-04-25 02:29:00	Correctivo	Cambio de baterias medio uso	3
80	38	2026-04-25 02:57:00	Correctivo	Se cambio Cable UTP por que fue cortado	1
81	40	2026-03-31 14:11:00	Preventivo	Limpieza de paneles Solares y cambio de baterias 	3
82	41	2026-03-31 14:04:00	Preventivo	Cambio de baterias vida util proxima acabar	3
83	39	2026-03-30 16:11:00	Correctivo	vestido de arco por daño causado por huracan, 	3
84	42	2026-04-21 15:49:00	Correctivo	Cambio de baterias ya no retienen carga	3
85	44	2026-04-21 14:40:00	Preventivo	Se cambio baterias y se limpiaron los paneles solared	3
86	43	2026-04-21 16:04:00	Preventivo	Se limpiaron los paneles solares y se cambiaron las baterias	3
\.


--
-- Data for Name: tecnicos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tecnicos (id, nombre, telefono, puesto, activo, eliminado, created_at) FROM stdin;
1	Carlos Serafin Chavelas Gonzolez	\N	\N	0	1	2026-07-11 00:08:28.654509
7	luis	\N	\N	0	1	2026-07-11 00:08:28.701322
2	Luis Alberto Castro Garcia	\N	\N	0	1	2026-07-11 00:08:28.654509
6	Carlos Chavelaz Gonzalez	\N	\N	0	1	2026-07-11 00:08:28.701322
3	Carlos Serafin Chavelas Gonzalez	\N	Encargado de Arcos Repuve	1	0	2026-07-11 00:08:28.654509
5	Jose Luis Romero Palacios	\N	Auxiliar de Arcos Repuve	1	0	2026-07-11 00:08:28.701322
\.


--
-- Data for Name: ubicaciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.ubicaciones (id, nombre, lat, lng) FROM stdin;
3	Chilpancingo	17.460713	-99.497681
4	Acapulco	16.851862	-99.821777
5	Taxco	\N	\N
6	Iguala	\N	\N
7	Zihuatanejo	\N	\N
8	San Marcos	\N	\N
9	Ometepec	\N	\N
10	Coyuca	17.007186	-100084279
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, username, password, role, created_at) FROM stdin;
3	jlromero	$2y$10$CXwyF7DhaIufmCM6HOGqNeet4lRAkugpdXrdMfivyyIV6.FmUY9a2	admin	2026-07-10 23:50:12.438669
4	Denisse	$2y$10$OEqUvBhkl6.6L81Qdx4EhOWNE6BvlWH4Ux72hCT3bRCFLY7rB0lkO	admin	2026-07-11 00:42:49.332084
\.


--
-- Name: arco_material_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.arco_material_id_seq', 769, true);


--
-- Name: arcos_bajas_evidencias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.arcos_bajas_evidencias_id_seq', 4, true);


--
-- Name: arcos_bajas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.arcos_bajas_id_seq', 1, true);


--
-- Name: arcos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.arcos_id_seq', 45, true);


--
-- Name: bitacora_checklist_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacora_checklist_id_seq', 33, true);


--
-- Name: bitacoras_arco_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bitacoras_arco_id_seq', 4, true);


--
-- Name: checklist_conceptos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.checklist_conceptos_id_seq', 12, true);


--
-- Name: formatos_mantenimiento_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.formatos_mantenimiento_id_seq', 1, false);


--
-- Name: infraestructura_material_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.infraestructura_material_id_seq', 34, true);


--
-- Name: infraestructura_nodos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.infraestructura_nodos_id_seq', 3, true);


--
-- Name: infraestructura_revision_evidencias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.infraestructura_revision_evidencias_id_seq', 3, true);


--
-- Name: infraestructura_revision_material_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.infraestructura_revision_material_id_seq', 6, true);


--
-- Name: infraestructura_revisiones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.infraestructura_revisiones_id_seq', 4, true);


--
-- Name: materiales_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.materiales_id_seq', 32, true);


--
-- Name: revision_evidencias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.revision_evidencias_id_seq', 123, true);


--
-- Name: revision_material_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.revision_material_id_seq', 119, true);


--
-- Name: revisiones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.revisiones_id_seq', 87, true);


--
-- Name: tecnicos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tecnicos_id_seq', 210, true);


--
-- Name: ubicaciones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.ubicaciones_id_seq', 10, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 4, true);


--
-- Name: arco_infraestructura arco_infraestructura_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_infraestructura
    ADD CONSTRAINT arco_infraestructura_pkey PRIMARY KEY (arco_id, infraestructura_id);


--
-- Name: arco_material arco_material_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_material
    ADD CONSTRAINT arco_material_pkey PRIMARY KEY (id);


--
-- Name: arcos_bajas_evidencias arcos_bajas_evidencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos_bajas_evidencias
    ADD CONSTRAINT arcos_bajas_evidencias_pkey PRIMARY KEY (id);


--
-- Name: arcos_bajas arcos_bajas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos_bajas
    ADD CONSTRAINT arcos_bajas_pkey PRIMARY KEY (id);


--
-- Name: arcos arcos_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos
    ADD CONSTRAINT arcos_nombre_key UNIQUE (nombre);


--
-- Name: arcos arcos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos
    ADD CONSTRAINT arcos_pkey PRIMARY KEY (id);


--
-- Name: bitacora_checklist bitacora_checklist_bitacora_id_concepto_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_checklist
    ADD CONSTRAINT bitacora_checklist_bitacora_id_concepto_id_key UNIQUE (bitacora_id, concepto_id);


--
-- Name: bitacora_checklist bitacora_checklist_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_checklist
    ADD CONSTRAINT bitacora_checklist_pkey PRIMARY KEY (id);


--
-- Name: bitacoras_arco bitacoras_arco_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoras_arco
    ADD CONSTRAINT bitacoras_arco_pkey PRIMARY KEY (id);


--
-- Name: checklist_conceptos checklist_conceptos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checklist_conceptos
    ADD CONSTRAINT checklist_conceptos_pkey PRIMARY KEY (id);


--
-- Name: formatos_mantenimiento formatos_mantenimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formatos_mantenimiento
    ADD CONSTRAINT formatos_mantenimiento_pkey PRIMARY KEY (id);


--
-- Name: infraestructura_material infraestructura_material_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_material
    ADD CONSTRAINT infraestructura_material_pkey PRIMARY KEY (id);


--
-- Name: infraestructura_nodos infraestructura_nodos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_nodos
    ADD CONSTRAINT infraestructura_nodos_pkey PRIMARY KEY (id);


--
-- Name: infraestructura_nodos infraestructura_nodos_tipo_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_nodos
    ADD CONSTRAINT infraestructura_nodos_tipo_nombre_key UNIQUE (tipo, nombre);


--
-- Name: infraestructura_revision_evidencias infraestructura_revision_evidencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revision_evidencias
    ADD CONSTRAINT infraestructura_revision_evidencias_pkey PRIMARY KEY (id);


--
-- Name: infraestructura_revision_material infraestructura_revision_material_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revision_material
    ADD CONSTRAINT infraestructura_revision_material_pkey PRIMARY KEY (id);


--
-- Name: infraestructura_revisiones infraestructura_revisiones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revisiones
    ADD CONSTRAINT infraestructura_revisiones_pkey PRIMARY KEY (id);


--
-- Name: materiales materiales_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.materiales
    ADD CONSTRAINT materiales_pkey PRIMARY KEY (id);


--
-- Name: revision_evidencias revision_evidencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_evidencias
    ADD CONSTRAINT revision_evidencias_pkey PRIMARY KEY (id);


--
-- Name: revision_material revision_material_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_material
    ADD CONSTRAINT revision_material_pkey PRIMARY KEY (id);


--
-- Name: revisiones revisiones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revisiones
    ADD CONSTRAINT revisiones_pkey PRIMARY KEY (id);


--
-- Name: tecnicos tecnicos_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tecnicos
    ADD CONSTRAINT tecnicos_nombre_key UNIQUE (nombre);


--
-- Name: tecnicos tecnicos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tecnicos
    ADD CONSTRAINT tecnicos_pkey PRIMARY KEY (id);


--
-- Name: ubicaciones ubicaciones_nombre_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ubicaciones
    ADD CONSTRAINT ubicaciones_nombre_key UNIQUE (nombre);


--
-- Name: ubicaciones ubicaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ubicaciones
    ADD CONSTRAINT ubicaciones_pkey PRIMARY KEY (id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: idx_arco_material_arco_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arco_material_arco_id ON public.arco_material USING btree (arco_id);


--
-- Name: idx_arco_material_material_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arco_material_material_id ON public.arco_material USING btree (material_id);


--
-- Name: idx_arcos_bajas_arco_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_bajas_arco_id ON public.arcos_bajas USING btree (arco_id);


--
-- Name: idx_arcos_bajas_evidencias_baja_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_bajas_evidencias_baja_id ON public.arcos_bajas_evidencias USING btree (baja_id);


--
-- Name: idx_arcos_bajas_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_bajas_fecha ON public.arcos_bajas USING btree (fecha_baja);


--
-- Name: idx_arcos_bajas_tecnico_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_bajas_tecnico_id ON public.arcos_bajas USING btree (tecnico_id);


--
-- Name: idx_arcos_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_estado ON public.arcos USING btree (estado);


--
-- Name: idx_arcos_ubicacion_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_arcos_ubicacion_id ON public.arcos USING btree (ubicacion_id);


--
-- Name: idx_bitacoras_arco_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_bitacoras_arco_fecha ON public.bitacoras_arco USING btree (arco_id, fecha_registro DESC);


--
-- Name: idx_bitacoras_arco_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_bitacoras_arco_id ON public.bitacoras_arco USING btree (arco_id);


--
-- Name: idx_bitacoras_arco_tecnico_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_bitacoras_arco_tecnico_id ON public.bitacoras_arco USING btree (tecnico_id);


--
-- Name: idx_formatos_mantenimiento_arco; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_formatos_mantenimiento_arco ON public.formatos_mantenimiento USING btree (arco_id, created_at DESC);


--
-- Name: idx_formatos_mantenimiento_revision; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_formatos_mantenimiento_revision ON public.formatos_mantenimiento USING btree (revision_id, created_at DESC);


--
-- Name: idx_formatos_mantenimiento_tecnico_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_formatos_mantenimiento_tecnico_id ON public.formatos_mantenimiento USING btree (tecnico_id);


--
-- Name: idx_infra_revision_evidencias_revision_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infra_revision_evidencias_revision_id ON public.infraestructura_revision_evidencias USING btree (revision_id);


--
-- Name: idx_infra_revision_material_revision_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infra_revision_material_revision_id ON public.infraestructura_revision_material USING btree (revision_id);


--
-- Name: idx_infra_revisiones_infra_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infra_revisiones_infra_fecha ON public.infraestructura_revisiones USING btree (infraestructura_id, fecha_mantenimiento DESC);


--
-- Name: idx_infraestructura_material_infra_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infraestructura_material_infra_id ON public.infraestructura_material USING btree (infraestructura_id);


--
-- Name: idx_infraestructura_nodos_ubicacion_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infraestructura_nodos_ubicacion_id ON public.infraestructura_nodos USING btree (ubicacion_id);


--
-- Name: idx_infraestructura_revisiones_infra_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infraestructura_revisiones_infra_id ON public.infraestructura_revisiones USING btree (infraestructura_id);


--
-- Name: idx_infraestructura_revisiones_tecnico_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_infraestructura_revisiones_tecnico_id ON public.infraestructura_revisiones USING btree (tecnico_id);


--
-- Name: idx_revision_evidencias_revision_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revision_evidencias_revision_id ON public.revision_evidencias USING btree (revision_id);


--
-- Name: idx_revision_material_arco_material_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revision_material_arco_material_id ON public.revision_material USING btree (arco_material_id);


--
-- Name: idx_revision_material_revision_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revision_material_revision_id ON public.revision_material USING btree (revision_id);


--
-- Name: idx_revisiones_arco_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revisiones_arco_fecha ON public.revisiones USING btree (arco_id, fecha_mantenimiento DESC);


--
-- Name: idx_revisiones_arco_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revisiones_arco_id ON public.revisiones USING btree (arco_id);


--
-- Name: idx_revisiones_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revisiones_fecha ON public.revisiones USING btree (fecha_mantenimiento);


--
-- Name: idx_revisiones_tecnico_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_revisiones_tecnico_id ON public.revisiones USING btree (tecnico_id);


--
-- Name: idx_tecnicos_activo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_tecnicos_activo ON public.tecnicos USING btree (activo);


--
-- Name: arco_infraestructura arco_infraestructura_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_infraestructura
    ADD CONSTRAINT arco_infraestructura_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- Name: arco_infraestructura arco_infraestructura_infraestructura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_infraestructura
    ADD CONSTRAINT arco_infraestructura_infraestructura_id_fkey FOREIGN KEY (infraestructura_id) REFERENCES public.infraestructura_nodos(id) ON DELETE CASCADE;


--
-- Name: arco_material arco_material_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_material
    ADD CONSTRAINT arco_material_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- Name: arco_material arco_material_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arco_material
    ADD CONSTRAINT arco_material_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.materiales(id) ON DELETE RESTRICT;


--
-- Name: arcos_bajas arcos_bajas_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos_bajas
    ADD CONSTRAINT arcos_bajas_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- Name: arcos_bajas_evidencias arcos_bajas_evidencias_baja_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos_bajas_evidencias
    ADD CONSTRAINT arcos_bajas_evidencias_baja_id_fkey FOREIGN KEY (baja_id) REFERENCES public.arcos_bajas(id) ON DELETE CASCADE;


--
-- Name: arcos arcos_ubicacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos
    ADD CONSTRAINT arcos_ubicacion_id_fkey FOREIGN KEY (ubicacion_id) REFERENCES public.ubicaciones(id) ON DELETE RESTRICT;


--
-- Name: bitacora_checklist bitacora_checklist_bitacora_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_checklist
    ADD CONSTRAINT bitacora_checklist_bitacora_id_fkey FOREIGN KEY (bitacora_id) REFERENCES public.bitacoras_arco(id) ON DELETE CASCADE;


--
-- Name: bitacora_checklist bitacora_checklist_concepto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacora_checklist
    ADD CONSTRAINT bitacora_checklist_concepto_id_fkey FOREIGN KEY (concepto_id) REFERENCES public.checklist_conceptos(id) ON DELETE RESTRICT;


--
-- Name: bitacoras_arco bitacoras_arco_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoras_arco
    ADD CONSTRAINT bitacoras_arco_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- Name: arcos_bajas fk_arcos_bajas_tecnico; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.arcos_bajas
    ADD CONSTRAINT fk_arcos_bajas_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: bitacoras_arco fk_bitacoras_arco_tecnico; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bitacoras_arco
    ADD CONSTRAINT fk_bitacoras_arco_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: formatos_mantenimiento fk_formatos_mantenimiento_tecnico; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formatos_mantenimiento
    ADD CONSTRAINT fk_formatos_mantenimiento_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: infraestructura_revisiones fk_infra_revisiones_tecnico; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revisiones
    ADD CONSTRAINT fk_infra_revisiones_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: revisiones fk_revisiones_tecnico; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revisiones
    ADD CONSTRAINT fk_revisiones_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: formatos_mantenimiento formatos_mantenimiento_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formatos_mantenimiento
    ADD CONSTRAINT formatos_mantenimiento_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- Name: formatos_mantenimiento formatos_mantenimiento_revision_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formatos_mantenimiento
    ADD CONSTRAINT formatos_mantenimiento_revision_id_fkey FOREIGN KEY (revision_id) REFERENCES public.revisiones(id) ON DELETE CASCADE;


--
-- Name: formatos_mantenimiento formatos_mantenimiento_tecnico_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formatos_mantenimiento
    ADD CONSTRAINT formatos_mantenimiento_tecnico_id_fkey FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;


--
-- Name: infraestructura_material infraestructura_material_infraestructura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_material
    ADD CONSTRAINT infraestructura_material_infraestructura_id_fkey FOREIGN KEY (infraestructura_id) REFERENCES public.infraestructura_nodos(id) ON DELETE CASCADE;


--
-- Name: infraestructura_material infraestructura_material_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_material
    ADD CONSTRAINT infraestructura_material_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.materiales(id) ON DELETE RESTRICT;


--
-- Name: infraestructura_nodos infraestructura_nodos_ubicacion_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_nodos
    ADD CONSTRAINT infraestructura_nodos_ubicacion_id_fkey FOREIGN KEY (ubicacion_id) REFERENCES public.ubicaciones(id) ON DELETE SET NULL;


--
-- Name: infraestructura_revision_evidencias infraestructura_revision_evidencias_revision_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revision_evidencias
    ADD CONSTRAINT infraestructura_revision_evidencias_revision_id_fkey FOREIGN KEY (revision_id) REFERENCES public.infraestructura_revisiones(id) ON DELETE CASCADE;


--
-- Name: infraestructura_revision_material infraestructura_revision_material_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revision_material
    ADD CONSTRAINT infraestructura_revision_material_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.materiales(id) ON DELETE RESTRICT;


--
-- Name: infraestructura_revision_material infraestructura_revision_material_revision_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revision_material
    ADD CONSTRAINT infraestructura_revision_material_revision_id_fkey FOREIGN KEY (revision_id) REFERENCES public.infraestructura_revisiones(id) ON DELETE CASCADE;


--
-- Name: infraestructura_revisiones infraestructura_revisiones_infraestructura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.infraestructura_revisiones
    ADD CONSTRAINT infraestructura_revisiones_infraestructura_id_fkey FOREIGN KEY (infraestructura_id) REFERENCES public.infraestructura_nodos(id) ON DELETE CASCADE;


--
-- Name: revision_evidencias revision_evidencias_revision_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_evidencias
    ADD CONSTRAINT revision_evidencias_revision_id_fkey FOREIGN KEY (revision_id) REFERENCES public.revisiones(id) ON DELETE CASCADE;


--
-- Name: revision_material revision_material_arco_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_material
    ADD CONSTRAINT revision_material_arco_material_id_fkey FOREIGN KEY (arco_material_id) REFERENCES public.arco_material(id) ON DELETE SET NULL;


--
-- Name: revision_material revision_material_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_material
    ADD CONSTRAINT revision_material_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.materiales(id) ON DELETE RESTRICT;


--
-- Name: revision_material revision_material_revision_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revision_material
    ADD CONSTRAINT revision_material_revision_id_fkey FOREIGN KEY (revision_id) REFERENCES public.revisiones(id) ON DELETE CASCADE;


--
-- Name: revisiones revisiones_arco_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.revisiones
    ADD CONSTRAINT revisiones_arco_id_fkey FOREIGN KEY (arco_id) REFERENCES public.arcos(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict LM7eoFVlIHYb8ogFph55kZM3hr5IgF8T1E6Y34uxglSu6lyLVnROc0zG5BCnXML

