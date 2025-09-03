<?php
/**
 * Sistema de Gestión de Bibliotecas - Versión Consola (corregido)
 */

// Clase base para entidades
class Entidad {
    protected $id;

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
}

// Clase Autor
class Autor extends Entidad {
    private $nombre;
    private $apellido;

    public function __construct($id, $nombre, $apellido) {
        $this->setId($id);
        $this->nombre = $nombre;
        $this->apellido = $apellido;
    }

    public function getNombreCompleto() { return $this->nombre . " " . $this->apellido; }
    public function getNombre() { return $this->nombre; }
    public function getApellido() { return $this->apellido; }
}

// Clase Categoria
class Categoria extends Entidad {
    private $nombre;

    public function __construct($id, $nombre) {
        $this->setId($id);
        $this->nombre = $nombre;
    }

    public function getNombre() { return $this->nombre; }
}

// Clase Libro
class Libro extends Entidad {
    private $titulo;
    private $autor;      // Autor
    private $categoria;  // Categoria
    private $isbn;
    private $disponible;

    public function __construct($id, $titulo, Autor $autor, Categoria $categoria, $isbn) {
        $this->setId($id);
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->isbn = $isbn;
        $this->disponible = true;
    }

    public function estaDisponible() { return $this->disponible; }
    public function prestar() { if ($this->disponible) { $this->disponible = false; return true; } return false; }
    public function devolver() { $this->disponible = true; }

    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getIsbn() { return $this->isbn; }
}

// Clase Usuario
class Usuario extends Entidad {
    private $nombre;
    private $email;

    public function __construct($id, $nombre, $email) {
        $this->setId($id);
        $this->nombre = $nombre;
        $this->email = $email;
    }

    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
}

// Clase Prestamo
class Prestamo extends Entidad {
    private $libro;
    private $usuario;
    private $fechaPrestamo;
    private $fechaDevolucion;

    public function __construct($id, Libro $libro, Usuario $usuario) {
        $this->setId($id);
        $this->libro = $libro;
        $this->usuario = $usuario;
        $this->fechaPrestamo = date('Y-m-d H:i:s');
        $this->fechaDevolucion = null;
    }

    public function devolverLibro() {
        $this->libro->devolver();
        $this->fechaDevolucion = date('Y-m-d H:i:s');
    }

    public function getLibro() { return $this->libro; }
    public function getUsuario() { return $this->usuario; }
    public function getFechaPrestamo() { return $this->fechaPrestamo; }
    public function getFechaDevolucion() { return $this->fechaDevolucion; }
}

// Clase principal del sistema
class Biblioteca {
    private $libros = [];
    private $autores = [];
    private $categorias = [];
    private $usuarios = [];
    private $prestamos = [];

    // --------- Helpers (getters públicos seguros) ----------
    public function getAutor($id)       { return $this->autores[$id] ?? null; }
    public function getCategoria($id)   { return $this->categorias[$id] ?? null; }
    public function getLibro($id)       { return $this->libros[$id] ?? null; }
    public function getUsuario($id)     { return $this->usuarios[$id] ?? null; }
    public function countLibros()       { return count($this->libros); }
    public function countPrestamos()    { return count($this->prestamos); }

    // --------- Agregar elementos ----------
    public function agregarLibro(Libro $libro) {
        $this->libros[$libro->getId()] = $libro;
        echo "Libro agregado: " . $libro->getTitulo() . "\n";
    }

    public function agregarAutor(Autor $autor) {
        $this->autores[$autor->getId()] = $autor;
        echo "Autor agregado: " . $autor->getNombreCompleto() . "\n";
    }

    public function agregarCategoria(Categoria $categoria) {
        $this->categorias[$categoria->getId()] = $categoria;
        echo "Categoría agregada: " . $categoria->getNombre() . "\n";
    }

    public function agregarUsuario(Usuario $usuario) {
        $this->usuarios[$usuario->getId()] = $usuario;
        echo "Usuario agregado: " . $usuario->getNombre() . "\n";
    }

    // --------- Búsquedas ----------
    public function buscarPorTitulo($titulo) {
        $resultados = [];
        foreach ($this->libros as $libro) {
            if (stripos($libro->getTitulo(), $titulo) !== false) {
                $resultados[] = $libro;
            }
        }
        return $resultados;
    }

    public function buscarPorAutor($autorNombre) {
        $resultados = [];
        foreach ($this->libros as $libro) {
            $autor = $libro->getAutor();
            if (stripos($autor->getNombreCompleto(), $autorNombre) !== false) {
                $resultados[] = $libro;
            }
        }
        return $resultados;
    }

    public function buscarPorCategoria($categoriaNombre) {
        $resultados = [];
        foreach ($this->libros as $libro) {
            $categoria = $libro->getCategoria();
            if (stripos($categoria->getNombre(), $categoriaNombre) !== false) {
                $resultados[] = $libro;
            }
        }
        return $resultados;
    }

    // --------- Gestión de préstamos ----------
    public function prestarLibro($libroId, $usuarioId) {
        $libro = $this->getLibro($libroId);
        if (!$libro) { echo "Error: Libro no encontrado.\n"; return false; }

        $usuario = $this->getUsuario($usuarioId);
        if (!$usuario) { echo "Error: Usuario no encontrado.\n"; return false; }

        if ($libro->estaDisponible()) {
            $prestamoId = $this->countPrestamos() + 1;
            $prestamo = new Prestamo($prestamoId, $libro, $usuario);

            if ($libro->prestar()) {
                $this->prestamos[$prestamoId] = $prestamo;
                echo "Préstamo realizado: " . $libro->getTitulo() . " a " . $usuario->getNombre() . "\n";
                return $prestamo;
            }
        } else {
            echo "Error: El libro no está disponible.\n";
        }
        return false;
    }

    public function devolverLibro($prestamoId) {
        if (isset($this->prestamos[$prestamoId])) {
            $prestamo = $this->prestamos[$prestamoId];
            if ($prestamo->getFechaDevolucion() !== null) {
                echo "Aviso: ese préstamo ya fue devuelto.\n";
                return true;
            }
            $prestamo->devolverLibro();
            echo "Libro devuelto: " . $prestamo->getLibro()->getTitulo() . "\n";
            return true;
        }
        echo "Error: Préstamo no encontrado.\n";
        return false;
    }

    // --------- Mostrar información ----------
    public function mostrarLibros() {
        echo "\n--- LISTA DE LIBROS ---\n";
        foreach ($this->libros as $libro) {
            $disponible = $libro->estaDisponible() ? "Sí" : "No";
            echo "ID: " . $libro->getId() .
                 " | Título: " . $libro->getTitulo() .
                 " | Autor: " . $libro->getAutor()->getNombreCompleto() .
                 " | Categoría: " . $libro->getCategoria()->getNombre() .
                 " | Disponible: " . $disponible . "\n";
        }
    }

    public function mostrarPrestamos() {
        echo "\n--- PRÉSTAMOS ACTIVOS ---\n";
        foreach ($this->prestamos as $prestamo) {
            if ($prestamo->getFechaDevolucion() === null) {
                echo "ID: " . $prestamo->getId() .
                     " | Libro: " . $prestamo->getLibro()->getTitulo() .
                     " | Usuario: " . $prestamo->getUsuario()->getNombre() .
                     " | Fecha: " . $prestamo->getFechaPrestamo() . "\n";
            }
        }
    }
}

// --------- Menú y programa principal ----------
function mostrarMenu() {
    echo "\n=== SISTEMA DE GESTIÓN DE BIBLIOTECA ===\n";
    echo "1. Agregar libro\n";
    echo "2. Buscar libros\n";
    echo "3. Realizar préstamo\n";
    echo "4. Devolver libro\n";
    echo "5. Mostrar todos los libros\n";
    echo "6. Mostrar préstamos activos\n";
    echo "7. Salir\n";
    echo "Seleccione una opción: ";
}

function main() {
    $biblioteca = new Biblioteca();

    // Datos de ejemplo
    $biblioteca->agregarAutor(new Autor(1, "Gabriel", "García Márquez"));
    $biblioteca->agregarAutor(new Autor(2, "Mario", "Vargas Llosa"));

    $biblioteca->agregarCategoria(new Categoria(1, "Realismo Mágico"));
    $biblioteca->agregarCategoria(new Categoria(2, "Literatura Contemporánea"));

    $biblioteca->agregarUsuario(new Usuario(1, "Juan Pérez", "juan@email.com"));
    $biblioteca->agregarUsuario(new Usuario(2, "María López", "maria@email.com"));

    // Agregar libros usando getters (NO accedemos a propiedades privadas)
    $biblioteca->agregarLibro(new Libro(
        1,
        "Cien años de soledad",
        $biblioteca->getAutor(1),
        $biblioteca->getCategoria(1),
        "978-8437604947"
    ));
    $biblioteca->agregarLibro(new Libro(
        2,
        "La ciudad y los perros",
        $biblioteca->getAutor(2),
        $biblioteca->getCategoria(2),
        "978-8466337877"
    ));

    // Menú interactivo
    while (true) {
        mostrarMenu();
        $opcion = trim(fgets(STDIN));

        switch ($opcion) {
            case '1':
                echo "Título: ";
                $titulo = trim(fgets(STDIN));
                echo "ID Autor: ";
                $autorId = (int) trim(fgets(STDIN));
                echo "ID Categoría: ";
                $categoriaId = (int) trim(fgets(STDIN));
                echo "ISBN: ";
                $isbn = trim(fgets(STDIN));

                $autor = $biblioteca->getAutor($autorId);
                $categoria = $biblioteca->getCategoria($categoriaId);

                if (!$autor) { echo "Error: Autor no existe.\n"; break; }
                if (!$categoria) { echo "Error: Categoría no existe.\n"; break; }

                $nuevoId = $biblioteca->countLibros() + 1;

                $biblioteca->agregarLibro(new Libro(
                    $nuevoId, $titulo, $autor, $categoria, $isbn
                ));
                break;

            case '2':
                echo "Buscar por (1) Título, (2) Autor, (3) Categoría: ";
                $tipoBusqueda = trim(fgets(STDIN));

                echo "Término de búsqueda: ";
                $termino = trim(fgets(STDIN));

                $resultados = [];
                if ($tipoBusqueda === '1')       $resultados = $biblioteca->buscarPorTitulo($termino);
                elseif ($tipoBusqueda === '2')   $resultados = $biblioteca->buscarPorAutor($termino);
                elseif ($tipoBusqueda === '3')   $resultados = $biblioteca->buscarPorCategoria($termino);
                else                              echo "Opción no válida.\n";

                if (!empty($resultados)) {
                    echo "\n--- RESULTADOS DE BÚSQUEDA ---\n";
                    foreach ($resultados as $libro) {
                        $disponible = $libro->estaDisponible() ? "Sí" : "No";
                        echo "ID: " . $libro->getId() .
                             " | Título: " . $libro->getTitulo() .
                             " | Autor: " . $libro->getAutor()->getNombreCompleto() .
                             " | Categoría: " . $libro->getCategoria()->getNombre() .
                             " | Disponible: " . $disponible . "\n";
                    }
                } else {
                    echo "No se encontraron resultados.\n";
                }
                break;

            case '3':
                echo "ID del libro: ";
                $libroId = (int) trim(fgets(STDIN));
                echo "ID del usuario: ";
                $usuarioId = (int) trim(fgets(STDIN));
                $biblioteca->prestarLibro($libroId, $usuarioId);
                break;

            case '4':
                echo "ID del préstamo: ";
                $prestamoId = (int) trim(fgets(STDIN));
                $biblioteca->devolverLibro($prestamoId);
                break;

            case '5':
                $biblioteca->mostrarLibros();
                break;

            case '6':
                $biblioteca->mostrarPrestamos();
                break;

            case '7':
                echo "¡Hasta luego!\n";
                exit(0);

            default:
                echo "Opción no válida. Intente nuevamente.\n";
                break;
        }
    }
}

main();
