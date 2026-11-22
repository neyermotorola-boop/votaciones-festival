<?php

namespace Tests\Feature;

use App\Rules\DocumentoValido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentoValidoTest extends TestCase
{
    use RefreshDatabase;

    private function registrar(string $tipo, string $doc, string $correo = 'persona@correo.test')
    {
        return $this->post('/registro', [
            'name' => 'Persona', 'email' => $correo, 'tipo_documento' => $tipo, 'documento' => $doc,
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345', 'acepta_datos' => '1',
        ]);
    }

    public static function invalidos(): array
    {
        return [
            'cédula con letras' => ['CC', '10654A2187'],
            'cédula muy corta' => ['CC', '1234'],
            'cédula muy larga' => ['CC', '10654321870'],
            'empieza por cero' => ['CC', '0654321870'],
            '10 dígitos sin empezar por 1' => ['CC', '2065432187'],
            'dígitos repetidos' => ['CC', '11111111'],
            'secuencia ascendente' => ['CC', '123456789'],
            'secuencia descendente' => ['CC', '987654321'],
            'secuencia con cero' => ['CC', '1234567890'],
            'TI de 9 dígitos' => ['TI', '106543218'],
            'TI que empieza por cero' => ['TI', '09032512345'],
            'CE con letras' => ['CE', 'AB12345'],
            'pasaporte sin números' => ['PA', 'ABCDEFG'],
            'pasaporte repetido' => ['PA', 'AAAAAAA'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidos')]
    public function test_rechaza_documentos_que_no_son_reales(string $tipo, string $doc): void
    {
        $this->registrar($tipo, $doc)->assertSessionHasErrors('documento');
        $this->assertDatabaseCount('users', 0);
    }

    public static function validos(): array
    {
        return [
            'cédula nueva (10 dígitos)' => ['CC', '1065432187'],
            'cédula antigua (8 dígitos)' => ['CC', '49765123'],
            'cédula antigua (6 dígitos)' => ['CC', '845213'],
            'con puntos y espacios' => ['CC', '49.765 123'],
            'tarjeta de identidad (NUIP)' => ['TI', '1065987412'],
            'tarjeta de identidad (11)' => ['TI', '10032512345'],
            'cédula de extranjería' => ['CE', '645213'],
            'pasaporte' => ['PA', 'AB123456'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validos')]
    public function test_acepta_documentos_con_estructura_real(string $tipo, string $doc): void
    {
        $this->registrar($tipo, $doc)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_detecta_patrones_inventados(): void
    {
        $this->assertTrue(DocumentoValido::esPatronInventado('7777777'));
        $this->assertTrue(DocumentoValido::esPatronInventado('3456789'));
        $this->assertFalse(DocumentoValido::esPatronInventado('321321321'));
        $this->assertFalse(DocumentoValido::esPatronInventado('1065432187'));
    }
}
