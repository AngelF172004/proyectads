--
-- PostgreSQL database dump
--

-- Dumped from database version 17.5
-- Dumped by pg_dump version 17.5

-- Started on 2025-09-17 20:36:47

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 220 (class 1259 OID 16533)
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id_usuario integer NOT NULL,
    nombre character varying(255),
    apellido_paterno character varying(255),
    apellido_materno character varying(255),
    correo character varying(255),
    sexo character varying(10),
    fecha_de_nacimiento character varying(255),
    telefono character varying(200),
    telefono_tipo character varying(200),
    tipo_domicilio character varying(255),
    calle character varying(255),
    no_exterior character varying(255),
    no_interior integer,
    colonia character varying(255),
    municipio character varying(255),
    ciudad character varying(255),
    estado character varying(255),
    codigo_postal integer,
    pais character varying(255)
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 16532)
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_usuario_seq OWNER TO postgres;

--
-- TOC entry 5092 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_usuario_seq OWNED BY public.usuarios.id_usuario;


--
-- TOC entry 4932 (class 2604 OID 16536)
-- Name: usuarios id_usuario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuarios_id_usuario_seq'::regclass);


--
-- TOC entry 5086 (class 0 OID 16533)
-- Dependencies: 220
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id_usuario, nombre, apellido_paterno, apellido_materno, correo, sexo, fecha_de_nacimiento, telefono, telefono_tipo, tipo_domicilio, calle, no_exterior, no_interior, colonia, municipio, ciudad, estado, codigo_postal, pais) FROM stdin;
\.


--
-- TOC entry 5093 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_usuario_seq', 35, true);


--
-- TOC entry 4934 (class 2606 OID 16871)
-- Name: usuarios app_user_correo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT app_user_correo_key UNIQUE (correo);


--
-- TOC entry 4936 (class 2606 OID 16540)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id_usuario);


-- Completed on 2025-09-17 20:36:47

--
-- PostgreSQL database dump complete
--

