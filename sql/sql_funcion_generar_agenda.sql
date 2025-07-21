-- FUNCTION: public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer)

-- DROP FUNCTION IF EXISTS public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer);

CREATE OR REPLACE FUNCTION public.generar_turnos_cpu(
	p_id_medico bigint,
	p_area bigint,
	p_fecha_inicio date,
	p_fecha_fin date,
	p_hora_ini_atencion time without time zone,
	p_hora_ini_comida time without time zone,
	p_hora_fin_comida time without time zone,
	p_hora_fin_atencion time without time zone,
	p_minutos_atencion integer,
	p_hora_ini_valoracion time without time zone,
	p_hora_fin_valoracion time without time zone,
	p_minutos_valoracion integer)
    RETURNS json
    LANGUAGE 'plpgsql'
    COST 100
    VOLATILE PARALLEL UNSAFE
AS $BODY$
DECLARE
  f_actual DATE;
  h_inicio TIME;
  h_fin TIME;
  ts TIMESTAMPTZ;

  count_atencion INT := 0;
  count_valoracion INT := 0;
BEGIN
  f_actual := p_fecha_inicio;

  WHILE f_actual <= p_fecha_fin LOOP

    -- Turnos de valoración (tipo_atencion = 2)
    h_inicio := p_hora_ini_valoracion;
    WHILE h_inicio < p_hora_fin_valoracion LOOP
      h_fin := h_inicio + (p_minutos_valoracion || ' minutes')::interval;

      IF h_fin <= p_hora_fin_valoracion THEN
        -- Validar que NO se crucen con comida
        IF NOT (h_inicio < p_hora_fin_comida AND p_hora_ini_comida < h_fin) THEN
          ts := make_timestamptz(EXTRACT(YEAR FROM f_actual)::INT,
                                 EXTRACT(MONTH FROM f_actual)::INT,
                                 EXTRACT(DAY FROM f_actual)::INT,
                                 EXTRACT(HOUR FROM h_inicio)::INT,
                                 EXTRACT(MINUTE FROM h_inicio)::INT,
                                 0);

          INSERT INTO public.turnos (
            id_paciente, id_medico, fehca_turno, hora, estado, area, via_atencion, usr_date_creacion, tipo_atencion
          ) VALUES (
            NULL, p_id_medico, ts, h_inicio, 1, p_area, 2, now(), 2
          );
          count_valoracion := count_valoracion + 1;
        END IF;
      END IF;

      h_inicio := h_fin;
    END LOOP;

    -- Turnos de atención (tipo_atencion = 1)
    h_inicio := p_hora_ini_atencion;
    WHILE h_inicio < p_hora_fin_atencion LOOP
      h_fin := h_inicio + (p_minutos_atencion || ' minutes')::interval;

      IF h_fin <= p_hora_fin_atencion THEN
        -- Validar que NO se crucen con valoración NI comida
        IF NOT (h_inicio < p_hora_fin_valoracion AND p_hora_ini_valoracion < h_fin) AND
           NOT (h_inicio < p_hora_fin_comida AND p_hora_ini_comida < h_fin) THEN

          ts := make_timestamptz(EXTRACT(YEAR FROM f_actual)::INT,
                                 EXTRACT(MONTH FROM f_actual)::INT,
                                 EXTRACT(DAY FROM f_actual)::INT,
                                 EXTRACT(HOUR FROM h_inicio)::INT,
                                 EXTRACT(MINUTE FROM h_inicio)::INT,
                                 0);

          INSERT INTO public.turnos (
            id_paciente, id_medico, fehca_turno, hora, estado, area, via_atencion, usr_date_creacion, tipo_atencion
          ) VALUES (
            NULL, p_id_medico, ts, h_inicio, 1, p_area, 1, now(), 1
          );
          count_atencion := count_atencion + 1;
        END IF;
      END IF;

      h_inicio := h_fin;
    END LOOP;

    f_actual := f_actual + INTERVAL '1 day';
  END LOOP;

  RETURN json_build_object(
    'fecha_inicio', p_fecha_inicio,
    'fecha_fin', p_fecha_fin,
    'total_atencion', count_atencion,
    'total_valoracion', count_valoracion,
    'total_turnos', count_atencion + count_valoracion
  );
END;
$BODY$;

ALTER FUNCTION public.generar_turnos_cpu(bigint, bigint, date, date, time without time zone, time without time zone, time without time zone, time without time zone, integer, time without time zone, time without time zone, integer)
    OWNER TO desarrollo_dbanu;
