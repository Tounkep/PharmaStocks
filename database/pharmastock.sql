--
-- PostgreSQL database dump
--

\restrict PONjU8rgS8WHJtVRjaXQ31ErTUfCCqMf1KcDrd512G6Yw6NfJsVZm7Wsuqvhr61

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

-- Started on 2026-09-20 21:31:09

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
-- TOC entry 242 (class 1259 OID 26688)
-- Name: alerte; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.alerte (
    id integer NOT NULL,
    type character varying(30) NOT NULL,
    message text NOT NULL,
    datecreation timestamp without time zone NOT NULL,
    statut character varying(20) NOT NULL,
    produit_id integer,
    lot_id integer,
    CONSTRAINT chk_statut_alerte CHECK (((statut)::text = ANY ((ARRAY['NOUVELLE'::character varying, 'TRAITEE'::character varying, 'IGNOREE'::character varying])::text[]))),
    CONSTRAINT chk_type_alerte CHECK (((type)::text = ANY ((ARRAY['STOCK_FAIBLE'::character varying, 'RUPTURE'::character varying, 'EXPIRATION_PROCHE'::character varying, 'EXPIRE'::character varying])::text[])))
);


ALTER TABLE public.alerte OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 26687)
-- Name: alerte_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.alerte ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.alerte_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 224 (class 1259 OID 26518)
-- Name: categorie; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categorie (
    id integer NOT NULL,
    nom character varying(100) NOT NULL,
    description text
);


ALTER TABLE public.categorie OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 26517)
-- Name: categorie_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.categorie ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.categorie_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 232 (class 1259 OID 26579)
-- Name: commande; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.commande (
    id integer NOT NULL,
    datecommande date NOT NULL,
    statut character varying(20) NOT NULL,
    montanttotal double precision NOT NULL,
    fournisseur_id integer NOT NULL,
    utilisateur_id integer NOT NULL,
    CONSTRAINT chk_statut_commande CHECK (((statut)::text = ANY ((ARRAY['EN_ATTENTE'::character varying, 'VALIDEE'::character varying, 'RECUE'::character varying, 'ANNULEE'::character varying])::text[])))
);


ALTER TABLE public.commande OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 26578)
-- Name: commande_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.commande ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.commande_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 234 (class 1259 OID 26602)
-- Name: detailcommande; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detailcommande (
    id integer NOT NULL,
    quantite integer NOT NULL,
    prixunitaire double precision NOT NULL,
    commande_id integer NOT NULL,
    produit_id integer NOT NULL
);


ALTER TABLE public.detailcommande OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 26601)
-- Name: detailcommande_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.detailcommande ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.detailcommande_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 238 (class 1259 OID 26638)
-- Name: detailvente; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.detailvente (
    id integer NOT NULL,
    quantite integer NOT NULL,
    prixunitaire double precision NOT NULL,
    vente_id integer NOT NULL,
    produit_id integer NOT NULL,
    lot_id integer NOT NULL
);


ALTER TABLE public.detailvente OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 26637)
-- Name: detailvente_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.detailvente ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.detailvente_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 230 (class 1259 OID 26569)
-- Name: fournisseur; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fournisseur (
    id integer NOT NULL,
    nom character varying(150) NOT NULL,
    adresse character varying(255),
    telephone character varying(30),
    email character varying(255)
);


ALTER TABLE public.fournisseur OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 26568)
-- Name: fournisseur_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.fournisseur ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.fournisseur_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 244 (class 1259 OID 26713)
-- Name: journalactivite; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.journalactivite (
    id integer NOT NULL,
    action character varying(150) NOT NULL,
    description text,
    dateheure timestamp without time zone NOT NULL,
    utilisateur_id integer NOT NULL
);


ALTER TABLE public.journalactivite OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 26712)
-- Name: journalactivite_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.journalactivite ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.journalactivite_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 228 (class 1259 OID 26550)
-- Name: lot; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lot (
    id integer NOT NULL,
    numerolot character varying(100) NOT NULL,
    quantite integer NOT NULL,
    datereception date NOT NULL,
    dateexpiration date NOT NULL,
    statut character varying(20) NOT NULL,
    produit_id integer NOT NULL,
    CONSTRAINT chk_statut_lot CHECK (((statut)::text = ANY ((ARRAY['DISPONIBLE'::character varying, 'EPUISE'::character varying, 'PERIME'::character varying])::text[])))
);


ALTER TABLE public.lot OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 26549)
-- Name: lot_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.lot ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.lot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 240 (class 1259 OID 26665)
-- Name: mouvementstock; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mouvementstock (
    id integer NOT NULL,
    type character varying(20) NOT NULL,
    quantite integer NOT NULL,
    dateheure timestamp without time zone NOT NULL,
    motif character varying(255),
    lot_id integer NOT NULL,
    utilisateur_id integer NOT NULL,
    CONSTRAINT chk_type_mouvement CHECK (((type)::text = ANY ((ARRAY['ENTREE'::character varying, 'SORTIE'::character varying, 'PERTE'::character varying, 'RETOUR'::character varying, 'AJUSTEMENT'::character varying])::text[])))
);


ALTER TABLE public.mouvementstock OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 26664)
-- Name: mouvementstock_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.mouvementstock ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.mouvementstock_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 226 (class 1259 OID 26528)
-- Name: produit; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.produit (
    id integer NOT NULL,
    reference character varying(100) NOT NULL,
    nom character varying(150) NOT NULL,
    description text,
    forme character varying(100),
    dosage character varying(100),
    prixachat double precision NOT NULL,
    prixvente double precision NOT NULL,
    seuilminimum integer NOT NULL,
    dateexpiration date,
    categorie_id integer NOT NULL
);


ALTER TABLE public.produit OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 26527)
-- Name: produit_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.produit ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.produit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 220 (class 1259 OID 26488)
-- Name: role; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role (
    id integer NOT NULL,
    nom character varying(100) NOT NULL
);


ALTER TABLE public.role OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 26487)
-- Name: role_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.role ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.role_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 222 (class 1259 OID 26496)
-- Name: utilisateur; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.utilisateur (
    id integer NOT NULL,
    nom character varying(100) NOT NULL,
    prenom character varying(100) NOT NULL,
    email character varying(255) NOT NULL,
    motdepasse character varying(255) NOT NULL,
    datecreation date NOT NULL,
    role_id integer NOT NULL
);


ALTER TABLE public.utilisateur OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 26495)
-- Name: utilisateur_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.utilisateur ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.utilisateur_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 236 (class 1259 OID 26623)
-- Name: vente; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vente (
    id integer NOT NULL,
    datevente date NOT NULL,
    montanttotal double precision NOT NULL,
    utilisateur_id integer NOT NULL
);


ALTER TABLE public.vente OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 26622)
-- Name: vente_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

ALTER TABLE public.vente ALTER COLUMN id ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.vente_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- TOC entry 5137 (class 0 OID 26688)
-- Dependencies: 242
-- Data for Name: alerte; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.alerte (id, type, message, datecreation, statut, produit_id, lot_id) FROM stdin;
\.


--
-- TOC entry 5119 (class 0 OID 26518)
-- Dependencies: 224
-- Data for Name: categorie; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categorie (id, nom, description) FROM stdin;
\.


--
-- TOC entry 5127 (class 0 OID 26579)
-- Dependencies: 232
-- Data for Name: commande; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.commande (id, datecommande, statut, montanttotal, fournisseur_id, utilisateur_id) FROM stdin;
\.


--
-- TOC entry 5129 (class 0 OID 26602)
-- Dependencies: 234
-- Data for Name: detailcommande; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detailcommande (id, quantite, prixunitaire, commande_id, produit_id) FROM stdin;
\.


--
-- TOC entry 5133 (class 0 OID 26638)
-- Dependencies: 238
-- Data for Name: detailvente; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.detailvente (id, quantite, prixunitaire, vente_id, produit_id, lot_id) FROM stdin;
\.


--
-- TOC entry 5125 (class 0 OID 26569)
-- Dependencies: 230
-- Data for Name: fournisseur; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fournisseur (id, nom, adresse, telephone, email) FROM stdin;
\.


--
-- TOC entry 5139 (class 0 OID 26713)
-- Dependencies: 244
-- Data for Name: journalactivite; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.journalactivite (id, action, description, dateheure, utilisateur_id) FROM stdin;
\.


--
-- TOC entry 5123 (class 0 OID 26550)
-- Dependencies: 228
-- Data for Name: lot; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lot (id, numerolot, quantite, datereception, dateexpiration, statut, produit_id) FROM stdin;
\.


--
-- TOC entry 5135 (class 0 OID 26665)
-- Dependencies: 240
-- Data for Name: mouvementstock; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mouvementstock (id, type, quantite, dateheure, motif, lot_id, utilisateur_id) FROM stdin;
\.


--
-- TOC entry 5121 (class 0 OID 26528)
-- Dependencies: 226
-- Data for Name: produit; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.produit (id, reference, nom, description, forme, dosage, prixachat, prixvente, seuilminimum, dateexpiration, categorie_id) FROM stdin;
\.


--
-- TOC entry 5115 (class 0 OID 26488)
-- Dependencies: 220
-- Data for Name: role; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role (id, nom) FROM stdin;
\.


--
-- TOC entry 5117 (class 0 OID 26496)
-- Dependencies: 222
-- Data for Name: utilisateur; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.utilisateur (id, nom, prenom, email, motdepasse, datecreation, role_id) FROM stdin;
\.


--
-- TOC entry 5131 (class 0 OID 26623)
-- Dependencies: 236
-- Data for Name: vente; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vente (id, datevente, montanttotal, utilisateur_id) FROM stdin;
\.


--
-- TOC entry 5145 (class 0 OID 0)
-- Dependencies: 241
-- Name: alerte_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.alerte_id_seq', 1, false);


--
-- TOC entry 5146 (class 0 OID 0)
-- Dependencies: 223
-- Name: categorie_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categorie_id_seq', 1, false);


--
-- TOC entry 5147 (class 0 OID 0)
-- Dependencies: 231
-- Name: commande_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.commande_id_seq', 1, false);


--
-- TOC entry 5148 (class 0 OID 0)
-- Dependencies: 233
-- Name: detailcommande_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detailcommande_id_seq', 1, false);


--
-- TOC entry 5149 (class 0 OID 0)
-- Dependencies: 237
-- Name: detailvente_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.detailvente_id_seq', 1, false);


--
-- TOC entry 5150 (class 0 OID 0)
-- Dependencies: 229
-- Name: fournisseur_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fournisseur_id_seq', 1, false);


--
-- TOC entry 5151 (class 0 OID 0)
-- Dependencies: 243
-- Name: journalactivite_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.journalactivite_id_seq', 1, false);


--
-- TOC entry 5152 (class 0 OID 0)
-- Dependencies: 227
-- Name: lot_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lot_id_seq', 1, false);


--
-- TOC entry 5153 (class 0 OID 0)
-- Dependencies: 239
-- Name: mouvementstock_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mouvementstock_id_seq', 1, false);


--
-- TOC entry 5154 (class 0 OID 0)
-- Dependencies: 225
-- Name: produit_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.produit_id_seq', 1, false);


--
-- TOC entry 5155 (class 0 OID 0)
-- Dependencies: 219
-- Name: role_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.role_id_seq', 1, false);


--
-- TOC entry 5156 (class 0 OID 0)
-- Dependencies: 221
-- Name: utilisateur_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.utilisateur_id_seq', 1, false);


--
-- TOC entry 5157 (class 0 OID 0)
-- Dependencies: 235
-- Name: vente_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vente_id_seq', 1, false);


--
-- TOC entry 4948 (class 2606 OID 26701)
-- Name: alerte alerte_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alerte
    ADD CONSTRAINT alerte_pkey PRIMARY KEY (id);


--
-- TOC entry 4928 (class 2606 OID 26526)
-- Name: categorie categorie_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categorie
    ADD CONSTRAINT categorie_pkey PRIMARY KEY (id);


--
-- TOC entry 4938 (class 2606 OID 26590)
-- Name: commande commande_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.commande
    ADD CONSTRAINT commande_pkey PRIMARY KEY (id);


--
-- TOC entry 4940 (class 2606 OID 26611)
-- Name: detailcommande detailcommande_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailcommande
    ADD CONSTRAINT detailcommande_pkey PRIMARY KEY (id);


--
-- TOC entry 4944 (class 2606 OID 26648)
-- Name: detailvente detailvente_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailvente
    ADD CONSTRAINT detailvente_pkey PRIMARY KEY (id);


--
-- TOC entry 4936 (class 2606 OID 26577)
-- Name: fournisseur fournisseur_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fournisseur
    ADD CONSTRAINT fournisseur_pkey PRIMARY KEY (id);


--
-- TOC entry 4950 (class 2606 OID 26723)
-- Name: journalactivite journalactivite_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journalactivite
    ADD CONSTRAINT journalactivite_pkey PRIMARY KEY (id);


--
-- TOC entry 4934 (class 2606 OID 26562)
-- Name: lot lot_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lot
    ADD CONSTRAINT lot_pkey PRIMARY KEY (id);


--
-- TOC entry 4946 (class 2606 OID 26676)
-- Name: mouvementstock mouvementstock_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mouvementstock
    ADD CONSTRAINT mouvementstock_pkey PRIMARY KEY (id);


--
-- TOC entry 4930 (class 2606 OID 26541)
-- Name: produit produit_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.produit
    ADD CONSTRAINT produit_pkey PRIMARY KEY (id);


--
-- TOC entry 4932 (class 2606 OID 26543)
-- Name: produit produit_reference_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.produit
    ADD CONSTRAINT produit_reference_key UNIQUE (reference);


--
-- TOC entry 4922 (class 2606 OID 26494)
-- Name: role role_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role
    ADD CONSTRAINT role_pkey PRIMARY KEY (id);


--
-- TOC entry 4924 (class 2606 OID 26511)
-- Name: utilisateur utilisateur_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateur
    ADD CONSTRAINT utilisateur_email_key UNIQUE (email);


--
-- TOC entry 4926 (class 2606 OID 26509)
-- Name: utilisateur utilisateur_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateur
    ADD CONSTRAINT utilisateur_pkey PRIMARY KEY (id);


--
-- TOC entry 4942 (class 2606 OID 26631)
-- Name: vente vente_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vente
    ADD CONSTRAINT vente_pkey PRIMARY KEY (id);


--
-- TOC entry 4964 (class 2606 OID 26707)
-- Name: alerte fk_alerte_lot; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alerte
    ADD CONSTRAINT fk_alerte_lot FOREIGN KEY (lot_id) REFERENCES public.lot(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4965 (class 2606 OID 26702)
-- Name: alerte fk_alerte_produit; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alerte
    ADD CONSTRAINT fk_alerte_produit FOREIGN KEY (produit_id) REFERENCES public.produit(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4954 (class 2606 OID 26591)
-- Name: commande fk_commande_fournisseur; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.commande
    ADD CONSTRAINT fk_commande_fournisseur FOREIGN KEY (fournisseur_id) REFERENCES public.fournisseur(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4955 (class 2606 OID 26596)
-- Name: commande fk_commande_utilisateur; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.commande
    ADD CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES public.utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4956 (class 2606 OID 26612)
-- Name: detailcommande fk_detailcommande_commande; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailcommande
    ADD CONSTRAINT fk_detailcommande_commande FOREIGN KEY (commande_id) REFERENCES public.commande(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4957 (class 2606 OID 26617)
-- Name: detailcommande fk_detailcommande_produit; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailcommande
    ADD CONSTRAINT fk_detailcommande_produit FOREIGN KEY (produit_id) REFERENCES public.produit(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4959 (class 2606 OID 26659)
-- Name: detailvente fk_detailvente_lot; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailvente
    ADD CONSTRAINT fk_detailvente_lot FOREIGN KEY (lot_id) REFERENCES public.lot(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4960 (class 2606 OID 26654)
-- Name: detailvente fk_detailvente_produit; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailvente
    ADD CONSTRAINT fk_detailvente_produit FOREIGN KEY (produit_id) REFERENCES public.produit(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4961 (class 2606 OID 26649)
-- Name: detailvente fk_detailvente_vente; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.detailvente
    ADD CONSTRAINT fk_detailvente_vente FOREIGN KEY (vente_id) REFERENCES public.vente(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4966 (class 2606 OID 26724)
-- Name: journalactivite fk_journal_utilisateur; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journalactivite
    ADD CONSTRAINT fk_journal_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES public.utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4953 (class 2606 OID 26563)
-- Name: lot fk_lot_produit; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lot
    ADD CONSTRAINT fk_lot_produit FOREIGN KEY (produit_id) REFERENCES public.produit(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4962 (class 2606 OID 26677)
-- Name: mouvementstock fk_mouvement_lot; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mouvementstock
    ADD CONSTRAINT fk_mouvement_lot FOREIGN KEY (lot_id) REFERENCES public.lot(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4963 (class 2606 OID 26682)
-- Name: mouvementstock fk_mouvement_utilisateur; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mouvementstock
    ADD CONSTRAINT fk_mouvement_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES public.utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4952 (class 2606 OID 26544)
-- Name: produit fk_produit_categorie; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.produit
    ADD CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id) REFERENCES public.categorie(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4951 (class 2606 OID 26512)
-- Name: utilisateur fk_utilisateur_role; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateur
    ADD CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES public.role(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- TOC entry 4958 (class 2606 OID 26632)
-- Name: vente fk_vente_utilisateur; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vente
    ADD CONSTRAINT fk_vente_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES public.utilisateur(id) ON UPDATE CASCADE ON DELETE RESTRICT;


-- Completed on 2026-09-20 21:31:09

--
-- PostgreSQL database dump complete
--

\unrestrict PONjU8rgS8WHJtVRjaXQ31ErTUfCCqMf1KcDrd512G6Yw6NfJsVZm7Wsuqvhr61

