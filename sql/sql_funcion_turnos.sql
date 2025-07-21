-- FUNCTION: public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer)

-- DROP FUNCTION IF EXISTS public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer);

CREATE OR REPLACE FUNCTION public.generar_turnos_cpu(
	p_id_medico bigint,
	p_area bigint,
	p_fecha_inicio date,
	p_fecha_fin date,
	p_hora_ini_atencion time without time zone,
	p_hora_fin_atencion time without time zone,
	p_minutos_atencion integer,
	p_hora_ini_valoracion time without time zone,
	p_hora_fin_valoracion time without time zone,
	p_minutos_valoracion integer)
    RETURNS void
    LANGUAGE 'plpgsql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
DECLARE
  f_actual DATE;
  h_inicio TIME;
  h_fin TIME;
  ts TIMESTAMPTZ;
BEGIN
  f_actual := p_fecha_inicio;
  WHILE f_actual <= p_fecha_fin LOOP

    -- Turnos de atención (via_atencion = 1)
    h_inicio := p_hora_ini_atencion;
    WHILE h_inicio < p_hora_fin_atencion LOOP
      h_fin := h_inicio + (p_minutos_atencion || ' minutes')::interval;
      IF h_fin <= p_hora_fin_atencion THEN
        ts := make_timestamptz(EXTRACT(YEAR FROM f_actual)::INT,
                               EXTRACT(MONTH FROM f_actual)::INT,
                               EXTRACT(DAY FROM f_actual)::INT,
                               EXTRACT(HOUR FROM h_inicio)::INT,
                               EXTRACT(MINUTE FROM h_inicio)::INT,
                               0);

        INSERT INTO public.turnos (
          id_paciente, id_medico, fehca_turno, hora, estado, area, via_atencion, usr_date_creacion
        ) VALUES (
          NULL, p_id_medico, ts, h_inicio, 1, p_area, 1, now()
        );
      END IF;
      h_inicio := h_fin;
    END LOOP;

    -- Turnos de valoración (via_atencion = 2)
    h_inicio := p_hora_ini_valoracion;
    WHILE h_inicio < p_hora_fin_valoracion LOOP
      h_fin := h_inicio + (p_minutos_valoracion || ' minutes')::interval;
      IF h_fin <= p_hora_fin_valoracion THEN
        ts := make_timestamptz(EXTRACT(YEAR FROM f_actual)::INT,
                               EXTRACT(MONTH FROM f_actual)::INT,
                               EXTRACT(DAY FROM f_actual)::INT,
                               EXTRACT(HOUR FROM h_inicio)::INT,
                               EXTRACT(MINUTE FROM h_inicio)::INT,
                               0);

        INSERT INTO public.turnos (
          id_paciente, id_medico, fehca_turno, hora, estado, area, via_atencion, usr_date_creacion
        ) VALUES (
          NULL, p_id_medico, ts, h_inicio, 1, p_area, 2, now()
        );
      END IF;
      h_inicio := h_fin;
    END LOOP;

    f_actual := f_actual + INTERVAL '1 day';
  END LOOP;
END;
$BODY$;

ALTER FUNCTION public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer)
    OWNER TO desarrollo_dbanu;


