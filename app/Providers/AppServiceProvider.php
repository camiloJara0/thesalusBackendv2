<?php

namespace App\Providers;

use App\Models\Analisis;
use App\Models\Antecedente;
use App\Models\Descripcion_nota;
use App\Models\Diagnostico;
use App\Models\Diagnostico_relacionado;
use App\Models\Enfermedad;
use App\Models\Examen_fisico;
use App\Models\Nota;
use App\Models\Plan_manejo_equipo;
use App\Models\Plan_manejo_insumo;
use App\Models\Plan_manejo_medicamento;
use App\Models\Plan_manejo_procedimiento;
use App\Models\Terapia;
use App\Observers\PdfAlmacenObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Modelos cuyo contenido exportable afecta el PDF del análisis */
    private const MODELOS_PDF = [
        Diagnostico::class,
        Diagnostico_relacionado::class,
        Antecedente::class,
        Enfermedad::class,
        Examen_fisico::class,
        Plan_manejo_medicamento::class,
        Plan_manejo_procedimiento::class,
        Plan_manejo_insumo::class,
        Plan_manejo_equipo::class,
        Nota::class,
        Descripcion_nota::class,
        Terapia::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Repositorio de PDFs por análisis (S3): generación al guardar e
        // invalidación transversal ante cambios de contenido exportable
        Analisis::observe(PdfAlmacenObserver::class);
        foreach (self::MODELOS_PDF as $modelo) {
            $modelo::observe(PdfAlmacenObserver::class);
        }
    }
}
