<?php

namespace Tests\Unit;

use App\Models\Opcion;
use PHPUnit\Framework\TestCase;

class OpcionTest extends TestCase
{
    public function test_inicial_del_participante(): void
    {
        $this->assertSame('Á', (new Opcion(['nombre' => 'ángel']))->inicial);
    }
}
