**Informe de Simulación - Teorema CAP: Escenario 1**

---

### 1. Descripción del sistema y decisiones de diseño

El sistema desarrollado corresponde a una aplicación web cliente-servidor para la reserva de tickets de concierto. Su arquitectura se basa en un contenedor de Docker que ejecuta un servidor PHP con Apache y tres instancias de bases de datos MySQL también en contenedores separados, simulando un sistema distribuido.

Los usuarios pueden registrarse, iniciar sesión, visualizar los asientos disponibles y realizar reservas. La interfaz está desarrollada en HTML y JavaScript, y se comunica con scripts PHP del lado servidor que interactúan con los nodos de base de datos.

**Decisiones de diseño clave:**
- **Distribución de datos replicados**: cada reserva se replica en los tres nodos.
- **Lectura de disponibilidad mediante intersección**: los asientos disponibles son aquellos confirmados por todos los nodos activos.
- **Control de quorum**: si no están disponibles al menos 2 nodos (de 3), el sistema se bloquea para mantener consistencia.
- **Manejo de concurrencia**: se evita la sobreventa mediante verificación de disponibilidad en todos los nodos antes de insertar una nueva reserva.

---

### 2. Análisis de cómo se priorizó cada aspecto del Teorema CAP en cada escenario

**Escenario 1: Consistencia + Tolerancia a particiones**

En este escenario, el sistema fue diseñado para:
- **Rechazar reservas si no hay quorum (menos de 2 nodos disponibles)**.
- **Evitar inconsistencias** al prevenir operaciones cuando no se puede verificar disponibilidad global.
- **Tolerar particiones**: los nodos pueden caer, y el sistema detecta esta situación sin fallar completamente.

Este comportamiento está alineado con el principio de que, ante una partición, se debe elegir entre disponibilidad y consistencia. Nuestro sistema **sacrifica disponibilidad** para mantener la **consistencia**.

**Simulaciones realizadas:**
- Apagado de 2 nodos: el sistema rechazó la reserva con un mensaje de error.
- Reserva simultánea desde dos usuarios: sólo uno logró reservar; el otro recibió un mensaje de conflicto.

**Justificación de no elección de otros escenarios:**

- **Escenario 2: Disponibilidad + Tolerancia a particiones**  
  Este enfoque habría implicado permitir reservas locales incluso si no se tiene visión global de disponibilidad, lo que podía provocar sobreventa y resultados inconsistentes. Dado que la aplicación trata con tickets únicos por asiento, este comportamiento no es aceptable para un sistema confiable.

- **Escenario 3: Consistencia + Disponibilidad (sin particiones)**  
  Este escenario requiere una red completamente estable y sincronizada. Cualquier falla de comunicación o nodo inhabilita el sistema. No se considera realista ni escalable para entornos donde pueden existir caídas o fallos parciales.

El Escenario 1 fue elegido porque proporciona una experiencia coherente y segura para los usuarios, incluso en condiciones de fallo parcial, aceptando el compromiso de restringir reservas cuando no se puede garantizar la consistencia global.

---

### 3. Reflexión sobre las limitaciones y trade-offs encontrados

**Limitaciones:**
- El sistema se vuelve parcial o totalmente inaccesible si hay caída de más de un nodo, lo que afecta la experiencia del usuario.
- Las operaciones de escritura son más costosas debido a la necesidad de replicarlas en todos los nodos y verificar conflictos.

**Trade-offs:**
- **Disponibilidad vs Consistencia**: Se sacrificó la disponibilidad en favor de garantizar que los datos sean coherentes en todos los nodos.
- **Simplicidad vs Robustez**: Se implementó una lógica relativamente sencilla para quorum y replicación, pero sin gestión de sincronización automática posterior entre nodos.
- **Tolerancia a fallos parcial**: Si bien se tolera la caída de un nodo, la de dos o más detiene la funcionalidad principal del sistema.

En resumen, se logró cumplir con los objetivos del Escenario 1, validando la aplicabilidad del Teorema CAP en un entorno distribuido realista, con decisiones de diseño que reflejan el compromiso entre consistencia y disponibilidad ante fallos de red o nodos.
