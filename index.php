<?php
/**
 * ECOGEST - Implementación Unificada de Clases (UML) en PHP.
 * * Este archivo integra todas las entidades de los diagramas:
 * 1. Usuarios, Reportes, Puntos Ecológicos (Herencia)
 * 2. Reciclador, Zona, TipoResiduo (Estructura)
 * 3. Alertas y Notificaciones (Sistema de comunicación)
 * 4. Recorridos y Vehículos (Logística)
 */

// =================================================================================
// 1. CLASES DE ESTRUCTURA BASE Y HERENCIA (Diagrama 1)
// =================================================================================

abstract class Usuario {
    protected $idUsuario;
    protected $nombre;
    protected $email;
    protected $password;
    protected $rol;

    public function __construct($nombre, $email, $password, $rol) {
        $this->idUsuario = uniqid('USR_');
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->rol = $rol;
    }

    public function getID() { return $this->idUsuario; }
    public function getEmail() { return $this->email; }
    
    /**
     * Relación: 1 Usuario recibe 1..* Notificacion.
     */
    public function recibirNotificacion(Notificacion $notificacion) {
        echo "{$this->rol} {$this->nombre} ha recibido una notificación.\n";
    }
}

class Ciudadano extends Usuario {
    public function __construct($nombre, $email, $password) {
        parent::__construct($nombre, $email, $password, 'Ciudadano');
    }
}

class Administrador extends Usuario {
    public function __construct($nombre, $email, $password) {
        parent::__construct($nombre, $email, $password, 'Administrador');
    }
    
    // Métodos de gestión...
}

// =================================================================================
// 2. CLASES DE LOGÍSTICA (Diagrama 2 y 4)
// =================================================================================

class TipoResiduo {
    public $idTipo;
    public $nombre; // Ejemplo: Plástico, Orgánico
    public $codigoColor; // Ejemplo: Blanco, Verde
    
    public function __construct(string $nombre, string $codigoColor) {
        $this->idTipo = uniqid('TR_');
        $this->nombre = $nombre;
        $this->codigoColor = $codigoColor;
    }
}

class PuntoEcologico {
    public $idPunto;
    public $lat;
    public $long;
    public $horario;
    public $tiposResiduo = []; // 0..* TipoResiduo

    public function __construct(float $lat, float $long, string $horario) {
        $this->idPunto = uniqid('PE_');
        $this->lat = $lat;
        $this->long = $long;
        $this->horario = $horario;
    }
}

class Zona {
    public $idZona;
    public $nombre;
    public $puntosEstrategicos = []; // 1..* PuntoEcologico

    public function __construct(string $nombre) {
        $this->idZona = uniqid('ZN_');
        $this->nombre = $nombre;
    }
}

class Vehiculo {
    public $idVehiculo;
    public $placa;
    public $tipo; // Camión, Camioneta
    public $capacidad; // En toneladas

    public function __construct(string $placa, string $tipo, float $capacidad) {
        $this->idVehiculo = uniqid('VH_');
        $this->placa = $placa;
        $this->tipo = $tipo;
        $this->capacidad = $capacidad;
    }
}

class Reciclador extends Usuario {
    public $rutasAsignadas = []; // 1..* Zona
    
    public function __construct($nombre, $email, $password) {
        parent::__construct($nombre, $email, $password, 'Reciclador');
    }

    /**
     * Relación: 1 Reciclador realiza 1..* Recorrido.
     */
    public function iniciarRecorrido(Vehiculo $vehiculo) {
        echo "Reciclador {$this->nombre} inicia nuevo recorrido.\n";
        return new Recorrido($this->idUsuario, $vehiculo);
    }
}

class Recorrido {
    public $idRecorrido;
    public $idReciclador;
    public $idVehiculo;
    public $fecha;
    public $horaInicio;
    public $horaFin;
    public $estado = 'Activo';

    /**
     * Relación: 1 Recorrido usa 1 Vehículo (Agregación/Composición).
     */
    public function __construct(string $idReciclador, Vehiculo $vehiculo) {
        $this->idRecorrido = uniqid('RC_');
        $this->idReciclador = $idReciclador;
        $this->idVehiculo = $vehiculo->idVehiculo;
        $this->fecha = date('Y-m-d');
        $this->horaInicio = date('H:i:s');
    }

    public function finalizar() {
        $this->horaFin = date('H:i:s');
        $this->estado = 'Finalizado';
        echo "Recorrido {$this->idRecorrido} finalizado.\n";
    }
}

// =================================================================================
// 3. CLASES DE ALERTAS Y REPORTES (Diagrama 1 y 3)
// =================================================================================

class TipoAlerta {
    public $idTipo;
    public $nombre; // Ej: RiesgoHidrico, AcumulacionCritica
    public $descripcion;
    
    public function __construct(string $nombre, string $descripcion) {
        $this->idTipo = uniqid('TA_');
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
    }
}

class Alerta {
    public $idAlerta;
    public $fechaGeneracion;
    public $mensaje;
    public $idTipoAlerta; // FK a TipoAlerta

    public function __construct(TipoAlerta $tipo, string $mensaje) {
        $this->idAlerta = uniqid('AL_');
        $this->fechaGeneracion = new DateTime();
        $this->mensaje = $mensaje;
        $this->idTipoAlerta = $tipo->idTipo;
    }
    
    /**
     * Relación: 1 Alerta genera 1..* Notificación.
     */
    public function generarNotificacion(Usuario $usuarioDestino) {
        $mensaje = "ALERTA [{$this->idTipoAlerta}]: {$this->mensaje}";
        $notificacion = new Notificacion($this->idAlerta, $usuarioDestino->getID(), $mensaje);
        $usuarioDestino->recibirNotificacion($notificacion);
        return $notificacion;
    }
}

class Notificacion {
    public $idNotificacion;
    public $idAlertaOrigen;
    public $idUsuarioDestino;
    public $fechaEnvio;
    public $mensaje;
    public $leida = false;
    
    public function __construct(string $idAlerta, string $idUsuario, string $mensaje) {
        $this->idNotificacion = uniqid('NT_');
        $this->idAlertaOrigen = $idAlerta;
        $this->idUsuarioDestino = $idUsuario;
        $this->fechaEnvio = new DateTime();
        $this->mensaje = $mensaje;
    }
}

class Reporte {
    public $idReporte;
    public $idCiudadano;
    public $idTipoResiduo; // FK a TipoResiduo
    public $idTipoAlerta;  // FK a TipoAlerta (Relación 1 a 1)
    public $fechaHora;
    public $ubicacionLat;
    public $ubicacionLong;
    public $estado = 'Pendiente';
    public $fotoUrl;
    // ... otros atributos

    public function __construct(string $idCiudadano, float $lat, float $long, TipoResiduo $residuo, TipoAlerta $alerta) {
        $this->idReporte = uniqid('RP_');
        $this->idCiudadano = $idCiudadano;
        $this->ubicacionLat = $lat;
        $this->ubicacionLong = $long;
        $this->idTipoResiduo = $residuo->idTipo;
        $this->idTipoAlerta = $alerta->idTipo;
        $this->fechaHora = new DateTime();
    }
    
    /**
     * Método de gestión: Simula la generación de una Alerta por un reporte.
     */
    public function generarAlerta(Administrador $admin) {
        $tipo = new TipoAlerta($this->idTipoAlerta, "Reporte de Emergencia");
        $alerta = new Alerta($tipo, "Se requiere acción urgente para el Reporte {$this->idReporte}.");
        $alerta->generarNotificacion($admin);
        return $alerta;
    }
}

// =================================================================================
// 4. DEMOSTRACIÓN DEL FLUJO INTEGRADO
// =================================================================================

echo "--- DEMOSTRACIÓN DE FLUJOS ECOGEST ---\n\n";

// A. Inicialización
$admin = new Administrador("Diana Reyes", "diana@ecogest.com", "pass1");
$ciudadano = new Ciudadano("Carlos Perez", "carlos@mail.com", "pass2");
$reciclador = new Reciclador("Mario Gomez", "mario@recicla.com", "pass3");
$vehiculo = new Vehiculo("XYZ123", "Camión", 3.5);

$tipoResiduo = new TipoResiduo("Orgánicos", "Verde");
$tipoAlertaCritica = new TipoAlerta("AcumulacionCritica", "Acumulación que obstruye vía pública");

// B. Flujo de Reporte y Alerta (Diagramas 1 y 3)
$reporte1 = new Reporte($ciudadano->getID(), 4.67, -74.07, $tipoResiduo, $tipoAlertaCritica);
echo "1. Reporte generado por {$ciudadano->getNombre()}.\n";

$alertaGenerada = $reporte1->generarAlerta($admin);
echo "2. Alerta creada: '{$alertaGenerada->mensaje}'.\n"; // Se imprime Notificación recibida
echo "Estado Notificación: " . ($alertaGenerada->generarNotificacion($admin)->leida ? 'Leída' : 'No Leída') . "\n\n";

// C. Flujo Logístico (Diagramas 2 y 4)
$recorrido1 = $reciclador->iniciarRecorrido($vehiculo);
echo "3. Recorrido {$recorrido1->idRecorrido} iniciado por {$reciclador->getNombre()} a las {$recorrido1->horaInicio}.\n";
$recorrido1->finalizar();
echo "4. Recorrido finalizado. Estado: {$recorrido1->estado}.\n";

?>
