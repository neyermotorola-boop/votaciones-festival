<?php

namespace App\Services;

use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Support\Carbon;

/** Datos reales (consultados a MySQL) para los gráficos del panel de administración. */
class EstadisticasVotos
{
    /** Votos por categoría dentro de una edición. */
    public static function porCategoria(?int $edicionId): array
    {
        $filas = Voto::query()
            ->when($edicionId, fn ($q) => $q->where('votos.edicion_id', $edicionId))
            ->join('categorias', 'categorias.id', '=', 'votos.categoria_id')
            ->selectRaw('categorias.nombre as nombre, COUNT(votos.id) as total')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')->get();

        return ['labels' => $filas->pluck('nombre')->all(), 'data' => $filas->pluck('total')->map(fn ($n) => (int) $n)->all()];
    }

    /** Votos por día en los últimos N días (incluye los días sin votos, con 0). */
    public static function porDia(?int $edicionId, int $dias = 14): array
    {
        $desde = Carbon::today()->subDays($dias - 1);
        $filas = Voto::query()
            ->when($edicionId, fn ($q) => $q->where('edicion_id', $edicionId))
            ->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')->pluck('total', 'dia');

        $labels = $data = [];
        for ($i = 0; $i < $dias; $i++) {
            $d = $desde->copy()->addDays($i);
            $labels[] = $d->format('d/m');
            $data[] = (int) ($filas[$d->toDateString()] ?? 0);
        }

        return compact('labels', 'data');
    }

    /** Votos por opción de una votación, con porcentaje. */
    public static function porOpcion(?Votacion $votacion): array
    {
        if (! $votacion) {
            return ['labels' => [], 'data' => [], 'porcentajes' => []];
        }
        $opciones = $votacion->opciones()->withCount('votos')->orderByDesc('votos_count')->get();
        $total = max(1, $opciones->sum('votos_count'));

        return [
            'labels' => $opciones->pluck('nombre')->all(),
            'data' => $opciones->pluck('votos_count')->all(),
            'porcentajes' => $opciones->map(fn ($o) => round($o->votos_count / $total * 100, 1))->all(),
        ];
    }

    /** Totales y participación (votantes que ya votaron frente a los registrados). */
    public static function resumen(?int $edicionId): array
    {
        $q = Voto::query()->when($edicionId, fn ($q) => $q->where('edicion_id', $edicionId));
        $votos = (clone $q)->count();
        $participantes = (clone $q)->distinct()->count('user_id');
        $votantes = User::where('rol', 'votante')->count();

        return [
            'votos' => $votos,
            'participantes' => $participantes,
            'votantes' => $votantes,
            'participacion' => $votantes > 0 ? round(min(100, $participantes / $votantes * 100), 1) : 0,
        ];
    }
}
