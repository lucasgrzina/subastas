<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  @@page { margin: 22mm 18mm 22mm 18mm; }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #1a1a1a;
    background: #fff;
    line-height: 1.45;
    width: 100%;
  }

  /* ── Receta: salto ANTES (no después) para evitar página en blanco final) ── */
  .receta {
    width: 100%;
   }
  .receta + .receta { page-break-before: always; }

  /* ── Portada ─────────────────────────────────────────────────────────────── */
  .cover {
    page-break-after: always;
    text-align: center;
    padding: 60mm 20mm 30mm;
  }
  .cover-title {
    font-size: 30pt;
    font-weight: 700;
    color: #2d2d2d;
    margin-bottom: 6mm;
  }
  .cover-subtitle {
    font-size: 13pt;
    color: #777;
    margin-bottom: 12mm;
  }
  .cover-meta { font-size: 9pt; color: #aaa; margin-top: 10mm; }

  /* ── Encabezado de receta ─────────────────────────────────────────────── */
  .receta-header {
    background: #2d2d2d;
    color: #fff;
    padding: 6mm 7mm 5mm;
    margin-bottom: 4mm;
    width: 100%;
  }
  .receta-titulo { font-size: 16pt; font-weight: 700; line-height: 1.2; margin-bottom: 1.5mm; }
  .receta-descripcion { font-size: 9pt; color: #ccc; font-style: italic; }

  /* ── Badges de taxonomía ──────────────────────────────────────────────── */
  .badges { margin: 2mm 0 3mm; line-height: 2; }
  .badge {
    display: inline;
    background: #f0f0f0;
    color: #444;
    font-size: 7.5pt;
    padding: 1pt 5pt;
    border: 1px solid #ddd;
    margin-right: 5%;
    margin-left: 5%;
  }
  .badge-diet  { background: #e8f5e9; border-color: #a5d6a7; color: #2e7d32; }
  .badge-cuisine { background: #e3f2fd; border-color: #90caf9; color: #1565c0; }

  /* ── Ficha técnica ────────────────────────────────────────────────────── */
  .ficha { width: 90%; border-collapse: collapse; margin-bottom: 4mm; font-size: 8.5pt; margin-right: 5%;
    margin-left: 5%;}
  .ficha td { padding: 2pt 4pt; border: 1px solid #e0e0e0; vertical-align: top; }
  .ficha .lbl {
    color: #888; background: #fafafa;
    font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3pt;
    width: 22%; white-space: nowrap;
  }

  /* ── Layout dos columnas (tabla real para dompdf) ─────────────────────── */
  .cols { width: 90%; border-collapse: collapse; margin-right: 5%; margin-left: 5%;}
  .col-left  { width: 36%; vertical-align: top; padding-right: 5mm; }
  .col-right { width: 64%; vertical-align: top; }

  /* ── Sección título ───────────────────────────────────────────────────── */
  .section-title {
    font-size: 7pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.8pt; color: #888;
    border-bottom: 1.5px solid #e0e0e0;
    padding-bottom: 1mm; margin-bottom: 2.5mm;
  }
  .mt { margin-top: 3mm; }

  /* ── Ingredientes ─────────────────────────────────────────────────────── */
  .ing-table { width: 100%; border-collapse: collapse; }
  .ing-table td { padding: 1.5pt 0; border-bottom: 1px dotted #eee; font-size: 8.5pt; vertical-align: top; }
  .ing-nombre { color: #1a1a1a; }
  .ing-prep   { color: #888; font-size: 7.5pt; }
  .ing-opc    { color: #aaa; font-style: italic; font-size: 7.5pt; }
  .ing-cant   { color: #555; font-weight: 600; font-size: 8pt; text-align: right; white-space: nowrap; padding-left: 4pt; }

  /* ── Pasos ────────────────────────────────────────────────────────────── */
  .pasos-table { width: 100%; border-collapse: collapse; margin-bottom: 1mm; }
  .pasos-table td { vertical-align: top; padding-bottom: 3mm; }
  .paso-num {
    width: 14pt; min-width: 14pt;
    height: 14pt;
    background: #2d2d2d; color: #fff;
    font-size: 7pt; font-weight: 700; text-align: center;
    line-height: 14pt;
    padding: 0 3pt;
  }
  .paso-critico .paso-num { background: #c62828; }
  .paso-texto { font-size: 8.8pt; color: #2a2a2a; line-height: 1.5; padding: 0 3pt; }
  .paso-tiempo { font-size: 7pt; color: #aaa; white-space: nowrap; text-align: right; padding-left: 2pt; }

  /* ── Nota del chef ────────────────────────────────────────────────────── */
  .notas {
    background: #fffde7; border-left: 3pt solid #f9a825;
    padding: 3mm 4mm; font-size: 8.5pt; color: #555;
    font-style: italic; margin-top: 4mm;
  }
  .notas strong { color: #f57f17; font-style: normal; }

  /* ── Nutrición ────────────────────────────────────────────────────────── */
  .nutri-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-top: 2mm; }
  .nutri-table td { padding: 1.5pt 2pt; border-bottom: 1px solid #f0f0f0; }
  .nutri-table .lbl { color: #666; }
  .nutri-table .val { text-align: right; font-weight: 600; color: #333; }
  .nutri-kcal td   { font-size: 9pt; font-weight: 700; border-bottom: 2px solid #ddd; }
  .nutri-est { font-size: 6.5pt; color: #bbb; font-style: italic; margin-top: 1mm; }

  /* ── Alérgenos ────────────────────────────────────────────────────────── */
  .alergenos { margin-top: 3mm; font-size: 7.5pt; }
  .al-label  { color: #888; }
  .al-tiene  { color: #c62828; font-weight: 600; }
  .al-puede  { color: #e65100; }

  /* ── Pie de página (running element via @page) ────────────────────────── */
  .footer {
    text-align: center; font-size: 6.5pt; color: #ccc;
    border-top: 1px solid #eee; padding-top: 2mm;
    margin-top: 6mm;
  }
</style>
</head>
<body>

<div class="footer">
  la plataforma — generado el {{ now()->format('d/m/Y') }}
</div>

{{-- ─── PORTADA (solo si hay más de 1 receta) ─────────────────────────── --}}
@if(count($recetas) > 1)
<div class="cover">
  <div class="cover-title">{{ $titulo ?? 'Recetas' }}</div>
  <div class="cover-subtitle">{{ $subtitulo ?? '' }}</div>
  <div class="cover-meta">{{ count($recetas) }} receta{{ count($recetas) !== 1 ? 's' : '' }}</div>
</div>
@endif

{{-- ─── RECETAS ─────────────────────────────────────────────────────────── --}}
@foreach($recetas as $r)
<div class="receta">

  <div class="receta-header">
    <div class="receta-titulo">{{ $r->titulo }}</div>
    @if($r->descripcion)
    <div class="receta-descripcion">{{ $r->descripcion }}</div>
    @endif
  </div>

  {{-- Taxonomías --}}
  <div class="badges">
    @foreach($r->categories as $cat)<span class="badge">{{ $cat->nombre }}</span> @endforeach
    @foreach($r->cuisines  as $coc)<span class="badge badge-cuisine">{{ $coc->nombre }}</span> @endforeach
    @foreach($r->diets     as $d)  <span class="badge badge-diet">{{ $d->nombre }}</span> @endforeach
  </div>

  {{-- Ficha técnica --}}
  <table class="ficha">
    <tr>
      <td class="lbl">Rendimiento</td>
      <td>{{ $r->rendimiento_cantidad }} {{ $r->rendimientoUnidad?->abreviatura }}</td>
      <td class="lbl">Dificultad</td>
      <td>{{ ucfirst($r->dificultad ?? '—') }}</td>
    </tr>
    <tr>
      <td class="lbl">Preparación</td>
      <td>{{ $r->tiempo_preparacion_min ? $r->tiempo_preparacion_min . ' min' : '—' }}</td>
      <td class="lbl">Cocción</td>
      <td>{{ $r->tiempo_coccion_min ? $r->tiempo_coccion_min . ' min' : '—' }}</td>
    </tr>
    @if($r->tiempo_total_min)
    <tr>
      <td class="lbl">Tiempo total</td>
      <td>{{ $r->tiempo_total_min }} min</td>
      <td class="lbl">Servicio</td>
      <td>{{ $r->temperatura_servicio ?? '—' }}</td>
    </tr>
    @endif
    @if($r->temperatura_horno_valor)
    <tr>
      <td class="lbl">Horno</td>
      <td colspan="3">{{ $r->temperatura_horno_valor }}°{{ $r->temperatura_horno_unidad ?? 'C' }}</td>
    </tr>
    @endif
    @if($r->anio_origen)
    <tr>
      <td class="lbl">Origen</td>
      <td colspan="3">{{ $r->anio_origen }}</td>
    </tr>
    @endif
  </table>

  {{-- ── Cuerpo: tabla real dos columnas ── --}}
  <table class="cols">
  <tr>
    {{-- COLUMNA IZQUIERDA --}}
    <td class="col-left">

      @php
        $ingsSinSeccion  = $r->ingredients->where('section_id', null);
        $seccionesConIng = $r->sections->filter(fn($s) => $r->ingredients->where('section_id', $s->id)->isNotEmpty());
      @endphp

      @if($ingsSinSeccion->isNotEmpty())
        <div class="section-title">Ingredientes</div>
        @include('pdf._ingredientes', ['lista' => $ingsSinSeccion])
      @endif

      @foreach($seccionesConIng as $sec)
        <div class="section-title mt">{{ $sec->nombre }}</div>
        @include('pdf._ingredientes', ['lista' => $r->ingredients->where('section_id', $sec->id)])
      @endforeach

      {{-- Nutrición --}}
      @if($r->nutrition)
      <div class="section-title mt">Información nutricional</div>
      <table class="nutri-table">
        <tr class="nutri-kcal">
          <td class="lbl">Energía</td>
          <td class="val">{{ $r->nutrition->energia_kcal }} kcal</td>
        </tr>
        <tr><td class="lbl">Proteínas</td>          <td class="val">{{ $r->nutrition->proteinas_g }}g</td></tr>
        <tr><td class="lbl">Grasas totales</td>     <td class="val">{{ $r->nutrition->grasas_totales_g }}g</td></tr>
        @if($r->nutrition->grasas_saturadas_g !== null)
        <tr><td class="lbl">&nbsp;· Saturadas</td>  <td class="val">{{ $r->nutrition->grasas_saturadas_g }}g</td></tr>
        @endif
        @if($r->nutrition->grasas_trans_g)
        <tr><td class="lbl">&nbsp;· Trans</td>      <td class="val">{{ $r->nutrition->grasas_trans_g }}g</td></tr>
        @endif
        <tr><td class="lbl">Carbohidratos</td>      <td class="val">{{ $r->nutrition->carbohidratos_g }}g</td></tr>
        @if($r->nutrition->fibra_g !== null)
        <tr><td class="lbl">&nbsp;· Fibra</td>      <td class="val">{{ $r->nutrition->fibra_g }}g</td></tr>
        @endif
        @if($r->nutrition->sodio_mg !== null)
        <tr><td class="lbl">Sodio</td>              <td class="val">{{ $r->nutrition->sodio_mg }}mg</td></tr>
        @endif
        @if($r->nutrition->colesterol_mg !== null)
        <tr><td class="lbl">Colesterol</td>         <td class="val">{{ $r->nutrition->colesterol_mg }}mg</td></tr>
        @endif
      </table>
      @if($r->nutrition->estimado)
        <div class="nutri-est">* Valores estimados por porción</div>
      @endif
      @endif

      {{-- Alérgenos --}}
      @php
        $contiene = $r->allergens->where('pivot.relation_type', 'contiene');
        $puede    = $r->allergens->where('pivot.relation_type', 'puede_contener');
      @endphp
      @if($contiene->isNotEmpty() || $puede->isNotEmpty())
      <div class="alergenos">
        @if($contiene->isNotEmpty())
          <div><span class="al-label">Contiene: </span><span class="al-tiene">{{ $contiene->pluck('nombre')->implode(', ') }}</span></div>
        @endif
        @if($puede->isNotEmpty())
          <div><span class="al-label">Puede contener: </span><span class="al-puede">{{ $puede->pluck('nombre')->implode(', ') }}</span></div>
        @endif
      </div>
      @endif

    </td>

    {{-- COLUMNA DERECHA --}}
    <td class="col-right">

      @php $pasosSinSeccion = $r->steps->where('section_id', null); @endphp

      @if($pasosSinSeccion->isNotEmpty())
        <div class="section-title">Preparación</div>
        @include('pdf._pasos', ['pasos' => $pasosSinSeccion])
      @endif

      @foreach($r->sections as $sec)
        @php $pasosSec = $r->steps->where('section_id', $sec->id); @endphp
        @if($pasosSec->isNotEmpty())
          <div class="section-title mt">{{ $sec->nombre }}</div>
          @include('pdf._pasos', ['pasos' => $pasosSec])
        @endif
      @endforeach

      @if($r->notas_chef)
        <div class="notas"><strong>Nota del chef:</strong> {{ $r->notas_chef }}</div>
      @endif

    </td>
  </tr>
  </table>

</div>{{-- /receta --}}
@endforeach

</body>
</html>
