
# 🧪 Simulaciones para Escenario 1 – Consistencia + Tolerancia a Particiones

Este documento describe dos simulaciones realizadas para validar que el sistema desarrollado cumple con el **Escenario 1 del Teorema CAP**, priorizando **consistencia** y **tolerancia a particiones**, sacrificando **disponibilidad** cuando sea necesario.

---

## ✅ Simulación 1: Apagar nodos para simular partición de red

### 🎯 Objetivo:
Demostrar que si se pierde conexión con más de un nodo, el sistema **bloquea la reserva de entradas** para mantener la consistencia.

### 🔧 Pasos:

1. Levantar el sistema:
   ```bash
   docker-compose up -d
   ```

2. Ingresar al sistema desde el navegador:
   ```
   http://localhost:81/reservas/reservar.php
   ```

3. Verificar que se pueden realizar reservas normalmente.

4. Simular una partición de red deteniendo dos nodos:
   ```bash
   docker stop mysql_node2
   docker stop mysql_node3
   ```

5. Recargar la página o intentar una nueva reserva.

6. ✅ Resultado esperado:
    - El sistema mostrará el mensaje:
      ```
      ❌ Sistema no disponible por partición de red.
      ```
    - La operación es cancelada automáticamente, preservando la **consistencia global**.

7. (Opcional) Capturar evidencia:
    - Captura de pantalla del error mostrado.
    - Comando `docker ps` mostrando los nodos apagados.

8. Reiniciar los nodos al finalizar la prueba:
   ```bash
   docker start mysql_node2 mysql_node3
   ```

---

## ✅ Simulación 2: Reservas simultáneas desde dos usuarios

### 🎯 Objetivo:
Demostrar que si dos usuarios intentan reservar **el mismo asiento en la misma fecha al mismo tiempo**, **solo uno logra la reserva**, manteniendo consistencia y evitando sobreventa.

### 🔧 Pasos:

1. Asegurarse de que el sistema está activo:
   ```bash
   docker-compose up -d
   ```

2. Obtener la IP local del servidor:
   En Windows, ejecutar:
   ```bash
   ipconfig
   ```
   Anotar la dirección IPv4 (ejemplo: `192.168.1.100`).

3. Permitir el acceso a través del firewall:
    - Abrir Firewall de Windows Defender.
    - Crear una nueva **Regla de entrada**:
        - Tipo: Puerto
        - Puerto: TCP 81
        - Permitir conexión en redes privadas

4. Compartir la URL con el otro usuario:
   ```
   http://192.168.1.100:81/reservas/reservar.php
   ```

5. Desde dos dispositivos diferentes:
    - Ingresar a la misma URL.
    - Elegir **la misma fecha y asiento**.
    - Escribir distintos nombres de cliente.
    - Presionar **“Reservar” al mismo tiempo**.

6. ✅ Resultado esperado:
    - Uno de los usuarios recibe confirmación de la reserva.
    - El otro ve el mensaje:
      ```
      ⚠️ El asiento ya está reservado en al menos un nodo.
      ```

7. (Opcional) Capturar evidencia:
    - Ambas pantallas (éxito y rechazo).
    - Código de reserva registrado en base de datos (si aplica).

---

## 📌 Conclusión

Ambas simulaciones validan que el sistema:
- Garantiza **consistencia fuerte**.
- Detecta y bloquea operaciones ante fallos de red (simulación 1).
- Gestiona correctamente condiciones de carrera en concurrencia (simulación 2).

Esto confirma el cumplimiento del **Escenario 1 del Teorema CAP: Consistencia + Tolerancia a particiones**.
