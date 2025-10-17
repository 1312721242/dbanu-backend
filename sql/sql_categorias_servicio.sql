
/* Categoría: Entrenamiento físico */

WITH cat1 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Entrenamiento físico',
         'Rutinas de fuerza, resistencia, cardio y HIIT en el gimnasio',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Rutina de fuerza', 'Programa de fuerza con pesas y máquinas',
    'Fuerza', cat_id, 1, 1, now(), now()
FROM cat1
UNION ALL
SELECT
    'HIIT', 'Entrenamiento de alta intensidad en 20‑30 min',
    'HIIT', cat_id, 1, 1, now(), now()
FROM cat1
UNION ALL
SELECT
    'Circuitos de cardio', 'Combina cardio y fuerza en un solo circuito',
    'Circuito', cat_id, 1, 1, now(), now()
FROM cat1;

/* 
   Categoría: Entrenamiento funcional
*/
WITH cat2 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Entrenamiento funcional',
         'Movimientos que mejoran la movilidad y el core con TRX y pesas rusas',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'TRX', 'Entrenamiento con bandas de suspensión',
    'TRX', cat_id, 1, 1, now(), now()
FROM cat2
UNION ALL
SELECT
    'Pesas Rusas', 'Ejercicios funcionales con pesas rusas',
    'Pesas', cat_id, 1, 1, now(), now()
FROM cat2
UNION ALL
SELECT
    'Gymnastics', 'Ejercicios con calistenia y movimientos dinámicos',
    'Gymnastics', cat_id, 1, 1, now(), now()
FROM cat2;

/* 
   Categoría: Yoga
*/
WITH cat3 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Yoga',
         'Posturas, respiración y meditación para flexibilidad y calma',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Yoga Restaurador', 'Posturas suaves y respiración consciente',
    'Restaurador', cat_id, 1, 1, now(), now()
FROM cat3
UNION ALL
SELECT
    'Vinyasa', 'Secuencia fluida de posturas y movimiento',
    'Vinyasa', cat_id, 1, 1, now(), now()
FROM cat3
UNION ALL
SELECT
    'Power Yoga', 'Yoga de alta intensidad',
    'Power', cat_id, 1, 1, now(), now()
FROM cat3;

/* 
   Categoría: Pilates
*/
WITH cat4 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Pilates',
         'Ejercicios de core, alineación y respiración en colchoneta y aparatos',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Pilates Mat', 'Rutinas en colchoneta para fuerza y flexibilidad',
    'Mat', cat_id, 1, 1, now(), now()
FROM cat4
UNION ALL
SELECT
    'Pilates Reformer', 'Ejercicios con aparato Reformer',
    'Reformer', cat_id, 1, 1, now(), now()
FROM cat4
UNION ALL
SELECT
    'Pilates en suspensión', 'Pilates en barra de suspensión',
    'Suspensión', cat_id, 1, 1, now(), now()
FROM cat4;

/* 
   Categoría: Spinning
   
*/
WITH cat5 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Spinning',
         'Cardio en bicicleta estática con entrenamientos de intensidad variable',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Spinning 45', 'Sesión de 45 min a ritmo moderado‑intenso',
    '45', cat_id, 1, 1, now(), now()
FROM cat5
UNION ALL
SELECT
    'Spinning avanzado', 'Intensidad progresiva con resistencia alta',
    'Avanzado', cat_id, 1, 1, now(), now()
FROM cat5
UNION ALL
SELECT
    'Spinning + HIIT', 'Mezcla de ciclismo y entrenamientos cortos de fuerza',
    'Mix', cat_id, 1, 1, now(), now()
FROM cat5;

/* 
   Categoría: Zumba
*/
WITH cat6 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Zumba',
         'Baile‑fitness con música latina y ritmos de alta energía',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Zumba Básica', 'Clases para principiantes y nivel intermedio',
    'Básica', cat_id, 1, 1, now(), now()
FROM cat6
UNION ALL
SELECT
    'Zumba Cardio', 'Sesiones de alta energía con ritmo rápido',
    'Cardio', cat_id, 1, 1, now(), now()
FROM cat6
UNION ALL
SELECT
    'Zumba + HIIT', 'Mezcla de baile y entrenamientos cortos de fuerza',
    'HIIT', cat_id, 1, 1, now(), now()
FROM cat6;

/* 
   Categoría: CrossFit
   
*/
WITH cat7 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('CrossFit',
         'Entrenamiento funcional de alta intensidad con pesas, gimnasia y cardio',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'CrossFit', 'Rutina de CrossFit (WOD – workout of the day)',
    'WOD', cat_id, 1, 1, now(), now()
FROM cat7
UNION ALL
SELECT
    'CrossFit Básica', 'Entrenamiento de fuerza y cardio para principiantes',
    'Básica', cat_id, 1, 1, now(), now()
FROM cat7
UNION ALL
SELECT
    'CrossFit Avanzado', 'Rutina de alta intensidad para atletas',
    'Avanzado', cat_id, 1, 1, now(), now()
FROM cat7;

/* 
   Categoría: Nutrición
*/
WITH cat8 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Nutrición',
         'Asesoría dietética, planes de comidas y seguimiento de objetivos',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Plan de comidas', 'Plan personalizado de comidas diarias',
    'Plan', cat_id, 1, 1, now(), now()
FROM cat8
UNION ALL
SELECT
    'Seguimiento nutricional', 'Control de macronutrientes y calorías',
    'Seguimiento', cat_id, 1, 1, now(), now()
FROM cat8
UNION ALL
SELECT
    'Terapia nutricional', 'Apoyo para problemas de salud (diabetes, hipertensión…)',
    'Terapia', cat_id, 1, 1, now(), now()
FROM cat8;

/* 
   Categoría: Rehabilitación
*/
WITH cat9 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Rehabilitación',
         'Terapia física y movilización post‑operatoria y de lesiones',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Rehabilitación ortopédica', 'Terapia post‑operatoria de articulaciones',
    'Ortopédica', cat_id, 1, 1, now(), now()
FROM cat9
UNION ALL
SELECT
    'Terapia miofascial', 'Masaje y estiramientos para tejidos blandos',
    'Miofascial', cat_id, 1, 1, now(), now()
FROM cat9
UNION ALL
SELECT
    'Rehabilitación cardiovascular', 'Ejercicios para recuperar resistencia',
    'Cardio', cat_id, 1, 1, now(), now()
FROM cat9;

/*
   Categoría: Masajes
*/
WITH cat10 AS (
    INSERT INTO db_train_revive.cpu_categoria_servicios
        (cat_nombre, cat_descripcion, cat_created_at, cat_updated_at)
    VALUES
        ('Masajes',
         'Masajes terapéuticos, relajantes y deportivos',
         now(), now())
    RETURNING cat_id
)
INSERT INTO db_train_revive.cpu_tipos_servicios
    (ts_nombre, ts_descripcion, ts_breve_desc, ts_id_categoria,
     ts_id_estado, ts_id_user, ts_created_at, ts_updated_at)
SELECT
    'Masaje deportivo', 'Masaje de tejido profundo para atletas',
    'Deportivo', cat_id, 1, 1, now(), now()
FROM cat10
UNION ALL
SELECT
    'Masaje relajante', 'Técnicas de relajación profunda',
    'Relajante', cat_id, 1, 1, now(), now()
FROM cat10
UNION ALL
SELECT
    'Masaje con aromaterapia', 'Masaje con aceites esenciales',
    'Aromaterapia', cat_id, 1, 1, now(), now()
FROM cat10;



