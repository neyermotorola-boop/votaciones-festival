<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validación de plausibilidad de documentos colombianos según su tipo.
 *
 * No consulta a la Registraduría (no existe un servicio público y gratuito para hacerlo):
 * comprueba que el número tenga la estructura de un documento real y rechaza los
 * patrones inventados más comunes. Junto con el documento único por cuenta, frena la
 * creación de cuentas con números falsos. Si el Festival contrata un servicio oficial
 * de verificación, la consulta se agregaría aquí.
 *
 * Recibe el documento ya normalizado (sin espacios, puntos ni guiones, en mayúsculas).
 */
class DocumentoValido implements DataAwareRule, ValidationRule
{
    /** Formato por tipo: [regex, mensaje]. */
    public const FORMATOS = [
        'CC' => ['/^[1-9]\d{4,9}$/', 'La cédula de ciudadanía debe tener entre 5 y 10 dígitos, solo números y sin empezar por 0.'],
        'TI' => ['/^[1-9]\d{9,10}$/', 'La tarjeta de identidad debe tener 10 u 11 dígitos, solo números.'],
        'CE' => ['/^[1-9]\d{4,9}$/', 'La cédula de extranjería debe tener entre 5 y 10 dígitos, solo números.'],
        'PA' => ['/^(?=.*\d)[A-Z0-9]{5,15}$/', 'El pasaporte debe tener entre 5 y 15 letras o números, con al menos un número.'],
    ];

    private array $datos = [];

    public function setData(array $data): static
    {
        $this->datos = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $doc = (string) $value;
        $tipo = $this->datos['tipo_documento'] ?? null;

        if (! isset(self::FORMATOS[$tipo])) {
            return; // El tipo inválido lo reporta su propia regla.
        }

        [$regex, $mensaje] = self::FORMATOS[$tipo];
        if (! preg_match($regex, $doc)) {
            $fail($mensaje);

            return;
        }

        // Cédulas de 10 dígitos: se expiden desde 2003 y todas empiezan por 1 (1.000.000.000 en adelante).
        if ($tipo === 'CC' && strlen($doc) === 10 && $doc[0] !== '1') {
            $fail('Una cédula de 10 dígitos debe empezar por 1.');

            return;
        }

        if (self::esPatronInventado($doc)) {
            $fail('Ese número no corresponde a un documento real: no se aceptan dígitos repetidos ni secuencias.');
        }
    }

    /** Mismo carácter repetido (11111111) o secuencia ascendente/descendente (123456789, 987654321, 1234567890). */
    public static function esPatronInventado(string $doc): bool
    {
        if (strlen($doc) < 5) {
            return false;
        }
        if (count(array_unique(str_split($doc))) === 1) {
            return true;
        }
        if (! ctype_digit($doc)) {
            return false;
        }

        return str_contains('01234567890', $doc) || str_contains('09876543210', $doc);
    }
}
